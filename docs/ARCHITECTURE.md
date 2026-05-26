# Architecture Guide — UEX Backend

> Porto-inspired Laravel 12 architecture for a multi-currency fintech platform.  
> PHP 8.2+ · MySQL · JWT Auth · Kafka

---

## Table of Contents

1. [Overview](#1-overview)
2. [Directory Structure](#2-directory-structure)
3. [Layer: Module (Domain)](#3-layer-module-domain)
   - [Model](#31-model)
   - [Enum](#32-enum)
   - [Entity](#33-entity)
   - [EntityFactory](#34-entityfactory)
   - [Presenter](#35-presenter)
   - [Contract (Repository Interface)](#36-contract-repository-interface)
   - [Repository](#37-repository)
   - [Criteria](#38-criteria)
   - [CriteriaTrait](#39-criteriatrait)
   - [Command Transfers](#310-command-transfers)
   - [Handlers](#311-handlers)
   - [ServiceProvider](#312-serviceprovider)
4. [Layer: Application (REST API)](#4-layer-application-rest-api)
   - [RequestTransfer](#41-requesttransfer)
   - [Request](#42-request)
   - [Action](#43-action)
   - [Controller](#44-controller)
5. [Layer: Admin (Web Panel)](#5-layer-admin-web-panel)
   - [Admin Transfers & Requests](#51-admin-transfers--requests)
   - [Admin Actions](#52-admin-actions)
   - [Admin Controller](#53-admin-controller)
6. [Shared Infrastructure](#6-shared-infrastructure)
7. [Architecture Rules](#7-architecture-rules)
8. [Naming Conventions](#8-naming-conventions)
9. [Data Flows](#9-data-flows)
10. [New Feature Checklist](#10-new-feature-checklist)

---

## 1. Overview

The project is split into **three functional layers** on top of a **Module (domain) layer**:

| Layer | Namespace | Purpose |
|---|---|---|
| **Module** | `App\Modules\{Feature}` | Domain logic, fully self-contained |
| **Application** | `App\Application` | JSON REST API (one controller = one action) |
| **Admin** | `App\Admin` | AdminLTE web panel (one controller = full CRUD) |
| **Shared** | `App\Shared` | Global utilities, base classes, contracts |

**Golden rule:** dependencies flow **inward only**. Application and Admin call into Modules; Modules never call Application or Admin.

---

## 2. Directory Structure

```
src/
├── app/
│   ├── Modules/                   # 34+ domain modules
│   │   └── {Feature}/
│   │       ├── Commands/
│   │       │   ├── Handlers/      # Business logic
│   │       │   └── Transfers/     # DTOs for handlers
│   │       ├── Contracts/
│   │       │   └── Repositories/  # Repository interfaces
│   │       ├── Criteria/          # Query filter classes
│   │       ├── Data/
│   │       │   └── Migrations/    # Module-scoped migrations
│   │       ├── Entities/
│   │       │   └── Factories/     # Model → Entity mappers
│   │       ├── Enums/
│   │       ├── Exceptions/
│   │       ├── Models/            # Eloquent models
│   │       ├── Presenters/        # Entity → array serializers
│   │       ├── Providers/         # ServiceProvider (DI binding)
│   │       ├── Repositories/      # Contract implementations
│   │       └── Traits/
│   │           └── Commands/      # CriteriaTrait per handler group
│   │
│   ├── Application/               # REST API layer
│   │   ├── Actions/               # Orchestrate handlers
│   │   ├── Http/
│   │   │   ├── Controllers/       # Invokable, one per endpoint
│   │   │   └── Requests/          # FormRequest + getTransfer()
│   │   ├── Shared/                # Cross-module utilities (Criteria, Files, etc.)
│   │   └── Transfers/
│   │       └── Requests/          # API-level DTOs
│   │
│   ├── Admin/                     # Web panel layer
│   │   ├── Actions/               # Admin business logic
│   │   ├── Http/
│   │   │   ├── Controllers/       # CRUD controllers
│   │   │   └── Requests/          # Validated admin requests
│   │   ├── Models/                # Admin-specific Eloquent models
│   │   └── Transfers/
│   │       └── Requests/          # Admin DTOs
│   │
│   └── Shared/                    # Global utilities
│       ├── Contracts/
│       │   └── Criteria/          # SharedCriterionContract etc.
│       ├── Controllers/           # AbstractApiController
│       └── Responses/             # ApiResponses
│
├── routes/
│   ├── api.php                    # JSON API routes
│   └── admin.php                  # Admin web routes
│
└── tests/
    ├── Unit/
    ├── Feature/
    └── Architecture/              # PHPat architecture tests
```

---

## 3. Layer: Module (Domain)

Modules are self-contained. A module **must not** depend on other modules (except their Enums).

### 3.1 Model

**Location:** `Modules/{Feature}/Models/{ModelName}.php`  
**Namespace:** `App\Modules\{Feature}\Models`

- `final class` extending `Illuminate\Database\Eloquent\Model`
- `$table`, `$fillable`, `$casts()` always defined
- `@property` docblock for every column
- No Eloquent relations to other modules (isolation rule)

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Models;

use App\Modules\Blog\Enums\BlogStatusEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property BlogStatusEnum $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = ['title', 'slug', 'content', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'title'        => 'string',
            'slug'         => 'string',
            'status'       => BlogStatusEnum::class,
            'published_at' => 'datetime',
        ];
    }
}
```

---

### 3.2 Enum

**Location:** `Modules/{Feature}/Enums/{Feature}StatusEnum.php`

- Backed enum (always `string` or `int`)
- Values in `snake_case` / `lowercase`
- May include helper methods (`label()`, `color()`, etc.)

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Enums;

enum BlogStatusEnum: string
{
    case DRAFT     = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED  => 'Archived',
        };
    }
}
```

---

### 3.3 Entity

**Location:** `Modules/{Feature}/Entities/{Feature}ItemEntity.php`

- `final readonly class` when all properties are immutable
- `final class` (with fluent setters returning `$this`) when mutation is needed
- Implements `Illuminate\Contracts\Support\Arrayable`
- `toArray()` delegates to `{Feature}EntityPresenter::toArray($this)`
- Only getters (and optional fluent setters) — no public mutable properties

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Entities;

use App\Modules\Blog\Enums\BlogStatusEnum;
use App\Modules\Blog\Presenters\BlogPostEntityPresenter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/** @implements Arrayable<string, mixed> */
final readonly class BlogPostEntity implements Arrayable
{
    public function __construct(
        private int $id,
        private string $title,
        private string $slug,
        private BlogStatusEnum $status,
        private ?Carbon $publishedAt,
        private Carbon $createdAt,
        private Carbon $updatedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return BlogPostEntityPresenter::toArray($this);
    }

    public function getId(): int        { return $this->id; }
    public function getTitle(): string  { return $this->title; }
    public function getSlug(): string   { return $this->slug; }
    public function getStatus(): BlogStatusEnum { return $this->status; }
    public function getPublishedAt(): ?Carbon    { return $this->publishedAt; }
    public function getCreatedAt(): Carbon       { return $this->createdAt; }
    public function getUpdatedAt(): Carbon       { return $this->updatedAt; }
}
```

**Mutable Entity** (when fluent setters are needed in Admin/Update flows):

```php
final class NewsItemEntity implements Arrayable
{
    private array $images = [];

    public function __construct(private int $id, private string $title, ...) {}

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setImages(array $images): static
    {
        $this->images = $images;
        return $this;
    }
}
```

---

### 3.4 EntityFactory

**Location:** `Modules/{Feature}/Entities/Factories/{Feature}ItemEntityFactory.php`

- `final readonly class`
- Single static method: `makeFromModel(Model $model): Entity`
- Handles all mapping from raw model columns to typed Entity properties

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Entities\Factories;

use App\Modules\Blog\Entities\BlogPostEntity;
use App\Modules\Blog\Models\BlogPost;

final readonly class BlogPostEntityFactory
{
    public static function makeFromModel(BlogPost $model): BlogPostEntity
    {
        return new BlogPostEntity(
            id:          $model->id,
            title:       $model->title,
            slug:        $model->slug,
            status:      $model->status,
            publishedAt: $model->published_at,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at,
        );
    }
}
```

---

### 3.5 Presenter

**Location:** `Modules/{Feature}/Presenters/{Feature}EntityPresenter.php`

- `final readonly class`
- Static method: `toArray(Entity $entity): array`
- Formats Carbon → string (`->toDateTimeString()` or `->toIso8601String()`)
- Enums → `.value`
- May have additional static methods like `toListArray()` for lighter payloads

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Presenters;

use App\Modules\Blog\Entities\BlogPostEntity;

final readonly class BlogPostEntityPresenter
{
    /** @return array<string, mixed> */
    public static function toArray(BlogPostEntity $entity): array
    {
        return [
            'id'           => $entity->getId(),
            'title'        => $entity->getTitle(),
            'slug'         => $entity->getSlug(),
            'status'       => $entity->getStatus()->value,
            'published_at' => $entity->getPublishedAt()?->toDateTimeString(),
            'created_at'   => $entity->getCreatedAt()->toDateTimeString(),
            'updated_at'   => $entity->getUpdatedAt()->toDateTimeString(),
        ];
    }
}
```

---

### 3.6 Contract (Repository Interface)

**Location:** `Modules/{Feature}/Contracts/Repositories/{Feature}Contract.php`  
**Namespace:** `App\Modules\{Feature}\Contracts\Repositories`

- PHP `interface`
- All public methods typed with Entities (never Models)
- Methods receive Transfers or primitives, return Entities or collections

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Contracts\Repositories;

use App\Modules\Blog\Commands\Transfers\CreateBlogPostTransfer;
use App\Modules\Blog\Commands\Transfers\UpdateBlogPostTransfer;
use App\Modules\Blog\Entities\BlogPostEntity;
use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlogPostRepositoryContract
{
    /** @param SharedCriterionContract[] $criteria */
    public function paginate(array $criteria = [], array $paginate = []): LengthAwarePaginator;

    /** @param SharedCriterionContract[] $criteria */
    public function first(array $criteria): ?BlogPostEntity;

    public function create(CreateBlogPostTransfer $transfer): BlogPostEntity;

    public function update(UpdateBlogPostTransfer $transfer): BlogPostEntity;

    public function delete(int $id): void;
}
```

---

### 3.7 Repository

**Location:** `Modules/{Feature}/Repositories/{Feature}Repository.php`

- `final readonly class` implementing Contract
- Injects `SharedCriteriaApplierContract $applier` for criteria
- **Never returns Model** — always converts via `EntityFactory::makeFromModel()`
- `paginate()` uses `tap()` to replace raw models with entities in the collection

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Repositories;

use App\Modules\Blog\Contracts\Repositories\BlogPostRepositoryContract;
use App\Modules\Blog\Entities\BlogPostEntity;
use App\Modules\Blog\Entities\Factories\BlogPostEntityFactory;
use App\Modules\Blog\Models\BlogPost;
use App\Shared\Contracts\Criteria\SharedCriteriaApplierContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/** @psalm-suppress PossiblyUnusedMethod */
final readonly class BlogPostRepository implements BlogPostRepositoryContract
{
    public function __construct(
        private SharedCriteriaApplierContract $applier,
    ) {
    }

    public function paginate(array $criteria = [], array $paginate = []): LengthAwarePaginator
    {
        $perPage = max(min((int) Arr::get($paginate, 'per_page'), 50), 10);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->applier->apply(BlogPost::query(), ...$criteria)->paginate($perPage);

        /** @psalm-suppress InvalidArgument */
        return tap($paginator, static function (LengthAwarePaginator $paginator) {
            $paginator->setCollection(
                $paginator->getCollection()->map(
                    static fn (BlogPost $model) => BlogPostEntityFactory::makeFromModel($model)
                )
            );
        });
    }

    public function first(array $criteria): ?BlogPostEntity
    {
        /** @var BlogPost|null $model */
        $model = $this->applier->apply(BlogPost::query(), ...$criteria)->first();

        return $model ? BlogPostEntityFactory::makeFromModel($model) : null;
    }

    public function create(CreateBlogPostTransfer $transfer): BlogPostEntity
    {
        $model = new BlogPost();
        $model->title  = $transfer->title;
        $model->slug   = $transfer->slug;
        $model->status = $transfer->status;
        $model->save();

        return BlogPostEntityFactory::makeFromModel($model);
    }

    public function update(UpdateBlogPostTransfer $transfer): BlogPostEntity
    {
        /** @var BlogPost $model */
        $model = BlogPost::query()->findOrFail($transfer->id);
        $model->title  = $transfer->title;
        $model->status = $transfer->status;
        $model->save();

        return BlogPostEntityFactory::makeFromModel($model);
    }

    public function delete(int $id): void
    {
        BlogPost::query()->findOrFail($id)->delete();
    }
}
```

---

### 3.8 Criteria

**Location:** `Modules/{Feature}/Criteria/{Feature}{Filter}Criteria.php`

- `final readonly class` implementing `SharedCriterionContract`
- One class = one query condition
- Constructor receives filter parameters
- `apply(Builder $query): Builder` adds the condition

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Criteria;

use App\Shared\Contracts\Criteria\SharedCriterionContract;
use Illuminate\Database\Eloquent\Builder;

final readonly class ByIdCriteria implements SharedCriterionContract
{
    public function __construct(private int $id) {}

    public function apply(Builder $query): Builder
    {
        return $query->where('id', $this->id);
    }
}
```

**Typical criteria set for a module:**

| Class | Purpose |
|---|---|
| `{Feature}ByIdCriteria` | `where('id', $id)` |
| `{Feature}ActiveOnlyCriteria` | `where('is_active', true)` |
| `{Feature}ByStatusCriteria` | `where('status', $status)` |
| `{Feature}ByTypeCriteria` | `where('type', $type)` |
| `{Feature}OrderByCriteria` | `orderBy($column, $direction)` |
| `{Feature}BySearchCriteria` | `where('title', 'LIKE', "%{$q}%")` |

---

### 3.9 CriteriaTrait

**Location:** `Modules/{Feature}/Traits/{Feature}CriteriaTrait.php`  
(or `Modules/{Feature}/Traits/Commands/{Feature}CriteriaTrait.php`)

- `trait` (not a class)
- `use UtilsCriteria;` — provides `$criteria[]` array and `getAndResetCriteria()`
- Fluent methods add Criteria objects and return `static`
- Used by Paginate and Find handlers

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Traits;

use App\Application\Shared\Criteria\UtilsCriteria;
use App\Modules\Blog\Criteria\ByIdCriteria;
use App\Modules\Blog\Criteria\ByStatusCriteria;
use App\Modules\Blog\Criteria\OrderByPublishedAtCriteria;
use App\Modules\Blog\Enums\BlogStatusEnum;

trait BlogPostCriteriaTrait
{
    use UtilsCriteria;

    final public function byId(int $id): static
    {
        $this->criteria[] = new ByIdCriteria($id);
        return $this;
    }

    final public function byStatus(BlogStatusEnum $status): static
    {
        $this->criteria[] = new ByStatusCriteria($status);
        return $this;
    }

    final public function orderByPublishedAt(string $direction = 'desc'): static
    {
        $this->criteria[] = new OrderByPublishedAtCriteria($direction);
        return $this;
    }
}
```

---

### 3.10 Command Transfers

**Location:** `Modules/{Feature}/Commands/Transfers/{Action}{Feature}Transfer.php`

- `final readonly class`
- Constructor with typed public properties
- Enums are already converted (by the Action layer) — Transfer receives typed values
- No validation logic inside

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Commands\Transfers;

use App\Modules\Blog\Enums\BlogStatusEnum;

final readonly class CreateBlogPostTransfer
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt,
        public BlogStatusEnum $status,
        public ?string $publishedAt,
    ) {
    }
}
```

---

### 3.11 Handlers

**Location:** `Modules/{Feature}/Commands/Handlers/{Action}{Feature}Handler.php`

- `final readonly class`
- Injects **Contract** (not Repository directly)
- Single `execute()` method

**Store Handler:**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Commands\Handlers;

use App\Modules\Blog\Commands\Transfers\CreateBlogPostTransfer;
use App\Modules\Blog\Contracts\Repositories\BlogPostRepositoryContract;
use App\Modules\Blog\Entities\BlogPostEntity;

final readonly class CreateBlogPostHandler
{
    public function __construct(
        private BlogPostRepositoryContract $contract,
    ) {
    }

    public function execute(CreateBlogPostTransfer $transfer): BlogPostEntity
    {
        return $this->contract->create($transfer);
    }
}
```

**Find Handler** (uses CriteriaTrait for fluent API):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Commands\Handlers;

use App\Modules\Blog\Contracts\Repositories\BlogPostRepositoryContract;
use App\Modules\Blog\Entities\BlogPostEntity;
use App\Modules\Blog\Traits\BlogPostCriteriaTrait;

final class FindBlogPostHandler
{
    use BlogPostCriteriaTrait;

    public function __construct(
        private readonly BlogPostRepositoryContract $contract,
    ) {
    }

    public function execute(): ?BlogPostEntity
    {
        return $this->contract->first($this->getAndResetCriteria());
    }
}
```

Usage: `$handler->byId($id)->execute() ?? throw new NotFoundHttpException()`

**Paginate Handler** (uses CriteriaTrait + passes paginate options):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Commands\Handlers;

use App\Modules\Blog\Contracts\Repositories\BlogPostRepositoryContract;
use App\Modules\Blog\Traits\BlogPostCriteriaTrait;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginateBlogPostsHandler
{
    use BlogPostCriteriaTrait;

    public function __construct(
        private readonly BlogPostRepositoryContract $contract,
    ) {
    }

    public function execute(array $paginate = []): LengthAwarePaginator
    {
        return $this->contract->paginate($this->getAndResetCriteria(), $paginate);
    }
}
```

Usage:
```php
$handler
    ->byStatus(BlogStatusEnum::PUBLISHED)
    ->orderByPublishedAt()
    ->execute(['per_page' => 10]);
```

---

### 3.12 ServiceProvider

**Location:** `Modules/{Feature}/Providers/{Feature}ServiceProvider.php`

- `final class extends ServiceProvider`
- `register()` binds Contract → Repository
- `boot()` loads module migrations (when migrations live inside the module)
- Register in `config/app.php` under `providers`

```php
<?php

declare(strict_types=1);

namespace App\Modules\Blog\Providers;

use App\Modules\Blog\Contracts\Repositories\BlogPostRepositoryContract;
use App\Modules\Blog\Repositories\BlogPostRepository;
use Illuminate\Support\ServiceProvider;

final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlogPostRepositoryContract::class, BlogPostRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Data/Migrations');
    }
}
```

---

## 4. Layer: Application (REST API)

One controller = one HTTP action. No business logic in controllers.

**Request flow:**
```
HTTP → Route → Controller::__invoke(Request) → Action::execute(RequestTransfer) → Handler(s) → Contract → Repository
```

### 4.1 RequestTransfer

**Location:** `Application/Transfers/Requests/{Feature}/{Action}{Feature}RequestTransfer.php`

- `final readonly class`
- Public constructor properties
- Types are primitives (`string|null`, `int`, `bool`) — **not** Enums (Action converts)

```php
<?php

declare(strict_types=1);

namespace App\Application\Transfers\Requests\Blog;

final readonly class BlogPaginateRequestTransfer
{
    /** @param array<int> $categoryIds */
    public function __construct(
        public int $perPage = 10,
        public array $categoryIds = [],
        public ?string $search = null,
    ) {
    }
}
```

---

### 4.2 Request

**Location:** `Application/Http/Requests/{Feature}/{Action}{Feature}Request.php`

- `final class extends FormRequest`
- `rules(): array` — validation rules
- `getTransfer(): {Action}RequestTransfer` — maps validated input to Transfer

```php
<?php

declare(strict_types=1);

namespace App\Application\Http\Requests\Blog;

use App\Application\Transfers\Requests\Blog\BlogPaginateRequestTransfer;
use Illuminate\Foundation\Http\FormRequest;

final class GetBlogPostsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page'        => ['nullable', 'integer', 'min:10', 'max:50'],
            'filter'          => ['nullable', 'array'],
            'filter.category' => ['nullable', 'string'],
            'filter.search'   => ['nullable', 'string', 'min:2', 'max:100'],
        ];
    }

    public function getTransfer(): BlogPaginateRequestTransfer
    {
        $categoryIds = [];
        $raw = $this->input('filter.category');

        if ($raw !== null && $raw !== '') {
            $categoryIds = array_filter(
                array_map('intval', explode(',', $raw)),
                static fn (int $id) => $id > 0,
            );
        }

        return new BlogPaginateRequestTransfer(
            perPage:     (int) $this->input('per_page', 10),
            categoryIds: $categoryIds,
            search:      $this->input('filter.search') ?: null,
        );
    }
}
```

---

### 4.3 Action

**Location:** `Application/Actions/{Feature}/{Action}{Feature}Action.php`

- `final readonly class`
- Injects Handler(s)
- `execute(RequestTransfer): Result`
- Converts string input to Enums: `BlogStatusEnum::from($transfer->status)`
- May enrich result via `tap()` (e.g., add image URLs, transform paginator collection)
- **No** direct Repository access

```php
<?php

declare(strict_types=1);

namespace App\Application\Actions\Blog;

use App\Application\Transfers\Requests\Blog\BlogPaginateRequestTransfer;
use App\Modules\Blog\Commands\Handlers\PaginateBlogPostsHandler;
use App\Modules\Blog\Entities\BlogPostEntity;
use App\Modules\Blog\Enums\BlogStatusEnum;
use App\Modules\Blog\Presenters\BlogPostEntityPresenter;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class GetBlogPostsAction
{
    public function __construct(
        private PaginateBlogPostsHandler $handler,
    ) {
    }

    public function execute(BlogPaginateRequestTransfer $transfer): LengthAwarePaginator
    {
        $this->handler
            ->byStatus(BlogStatusEnum::PUBLISHED)
            ->orderByPublishedAt();

        if ($transfer->categoryIds !== []) {
            $this->handler->byCategory($transfer->categoryIds);
        }

        $paginator = $this->handler->execute(['per_page' => $transfer->perPage]);

        // Enrich: replace Entity objects with serialized arrays
        return tap($paginator, static function (LengthAwarePaginator $paginator) {
            $paginator->setCollection(
                $paginator->getCollection()->map(
                    static fn (BlogPostEntity $entity) => BlogPostEntityPresenter::toListArray($entity)
                )
            );
        });
    }
}
```

---

### 4.4 Controller

**Location:** `Application/Http/Controllers/{Feature}/{Action}{Feature}Controller.php`

- `final class extends AbstractApiController` (cannot be `readonly` because of inheritance)
- Single action injected in constructor
- `__invoke(Request): JsonResponse`
- Returns `$this->success(...)`, `$this->create()`, or `$this->noContent()`
- **No** try/catch — exceptions handled globally

```php
<?php

declare(strict_types=1);

namespace App\Application\Http\Controllers\Blog;

use App\Application\Actions\Blog\GetBlogPostsAction;
use App\Application\Http\Requests\Blog\GetBlogPostsRequest;
use App\Shared\Controllers\AbstractApiController;
use Illuminate\Http\JsonResponse;

final class GetBlogPostsController extends AbstractApiController
{
    public function __construct(
        private readonly GetBlogPostsAction $action,
    ) {
    }

    public function __invoke(GetBlogPostsRequest $request): JsonResponse
    {
        $pagination = $this->action->execute($request->getTransfer());

        return $this->success($pagination->toArray());
    }
}
```

**Route registration** (`routes/api.php`):
```php
Route::get('/blog', GetBlogPostsController::class);
Route::get('/blog/{slug}', GetBlogPostBySlugController::class);
```

---

## 5. Layer: Admin (Web Panel)

One controller per resource handles all CRUD. Uses Blade views + DataTables AJAX.

### 5.1 Admin Transfers & Requests

**Transfer Location:** `Admin/Transfers/Requests/{Feature}/{Feature}{Action}RequestTransfer.php`

```php
final readonly class BlogPostStoreRequestTransfer
{
    /** @param array<int> $categoryIds */
    public function __construct(
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt,
        public string $status,          // string — Admin converts to Enum in Action
        public ?string $publishedAt,
        public array $categoryIds = [],
        public ?UploadedFile $coverImage = null,
    ) {
    }
}
```

**Request Location:** `Admin/Http/Requests/{Feature}/{Feature}{Action}Request.php`

```php
final class BlogPostStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'slug'         => ['required', 'string', 'max:255', 'unique:blog_posts,slug'],
            'content'      => ['required', 'string'],
            'status'       => ['required', Rule::enum(BlogStatusEnum::class)],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:blog_categories,id'],
            'cover_image'  => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function getTransfer(): BlogPostStoreRequestTransfer
    {
        return new BlogPostStoreRequestTransfer(
            title:       $this->input('title'),
            slug:        $this->input('slug'),
            content:     $this->input('content'),
            excerpt:     $this->input('excerpt'),
            status:      $this->input('status'),
            publishedAt: $this->input('published_at'),
            categoryIds: $this->input('category_ids', []),
            coverImage:  $this->file('cover_image'),
        );
    }
}
```

---

### 5.2 Admin Actions

**Location:** `Admin/Actions/{Feature}/{Feature}{Action}Action.php`

Four action types:

**ViewAction** — DataTables data endpoint:
```php
final class BlogPostViewAction
{
    public function data(): JsonResponse
    {
        return DataTables::of(BlogPost::query()->latest())
            ->addColumn('status', fn (BlogPost $m) => $m->status->label())
            ->addColumn('actions', function (BlogPost $m) {
                $edit = route('admin.blog.edit', $m->id);
                return "<a href=\"{$edit}\" class=\"btn btn-sm btn-info\">Edit</a>
                        <button class=\"btn btn-sm btn-danger delete-post\" data-id=\"{$m->id}\">Delete</button>";
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
```

**StoreAction** — creates entity, uploads files:
```php
final readonly class BlogPostStoreAction
{
    public function __construct(
        private CreateBlogPostHandler $createHandler,
    ) {
    }

    public function execute(BlogPostStoreRequestTransfer $transfer): BlogPostEntity
    {
        $coverImage = null;
        if ($transfer->coverImage !== null) {
            $coverImage = $transfer->coverImage->store('blog-images', 'public');
        }

        return $this->createHandler->execute(new CreateBlogPostTransfer(
            title:       $transfer->title,
            slug:        $transfer->slug,
            content:     $transfer->content,
            excerpt:     $transfer->excerpt,
            coverImage:  $coverImage,
            status:      BlogStatusEnum::from($transfer->status),
            publishedAt: $transfer->publishedAt,
        ));
    }
}
```

**UpdateAction** — finds entity, merges, mutates, saves:
```php
public function execute(BlogPostUpdateRequestTransfer $transfer): BlogPostEntity
{
    $entity = $this->findHandler->byId($transfer->id)->execute()
        ?? throw new NotFoundHttpException();

    // upload new cover if provided, otherwise keep existing
    $coverImage = $entity->getCoverImage();
    if ($transfer->coverImage !== null) {
        $coverImage = $transfer->coverImage->store('blog-images', 'public');
    }

    return $this->updateHandler->execute(new UpdateBlogPostTransfer(
        id:          $entity->getId(),
        title:       $transfer->title,
        slug:        $transfer->slug,
        content:     $transfer->content,
        excerpt:     $transfer->excerpt,
        coverImage:  $coverImage,
        status:      BlogStatusEnum::from($transfer->status),
        publishedAt: $transfer->publishedAt,
    ));
}
```

**DeleteAction:**
```php
public function execute(int $id): void
{
    $entity = $this->findHandler->byId($id)->execute()
        ?? throw new NotFoundHttpException();

    // delete file if present
    if ($entity->getCoverImage() !== null) {
        Storage::disk('public')->delete($entity->getCoverImage());
    }

    $this->contract->delete($id);
}
```

---

### 5.3 Admin Controller

**Location:** `Admin/Http/Controllers/{Feature}Controller.php`

- `final readonly class` (no inheritance in Admin)
- Injects all required Actions + Handlers
- All mutating methods (store/update/destroy) use try/catch with flash messages
- DataTables: `index()` checks `request()->ajax() && request()->has('draw')`

```php
<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers;

use App\Admin\Actions\Blog\BlogPostStoreAction;
use App\Admin\Actions\Blog\BlogPostUpdateAction;
use App\Admin\Actions\Blog\BlogPostDeleteAction;
use App\Admin\Actions\Blog\BlogPostViewAction;
use App\Admin\Http\Requests\Blog\BlogPostStoreRequest;
use App\Admin\Http\Requests\Blog\BlogPostUpdateRequest;
use App\Modules\Blog\Commands\Handlers\FindBlogPostHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class BlogPostController
{
    public function __construct(
        private BlogPostViewAction $viewAction,
        private BlogPostStoreAction $storeAction,
        private BlogPostUpdateAction $updateAction,
        private BlogPostDeleteAction $deleteAction,
        private FindBlogPostHandler $findHandler,
    ) {
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax() && request()->has('draw')) {
            return $this->viewAction->data();
        }

        ['heads' => $heads, 'config' => $config] = $this->viewAction->execute();

        return view('admins.blog.index', compact('heads', 'config'));
    }

    public function create(): View
    {
        return view('admins.blog.create');
    }

    public function store(BlogPostStoreRequest $request): RedirectResponse
    {
        try {
            $this->storeAction->execute($request->getTransfer());

            return redirect()->route('admin.blog.index')->with('success', 'Post created');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $id): View
    {
        $entity = $this->findHandler->byId($id)->execute()
            ?? throw new NotFoundHttpException();

        return view('admins.blog.edit', compact('entity'));
    }

    public function update(BlogPostUpdateRequest $request): RedirectResponse
    {
        try {
            $this->updateAction->execute($request->getTransfer());

            return redirect()->route('admin.blog.index')->with('success', 'Post updated');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteAction->execute($id);

            return response()->json(['success' => true, 'message' => 'Deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
```

**Routes** (`routes/admin.php`):
```php
Route::resource('blog', BlogPostController::class)->except(['show']);
Route::get('blog/data', [BlogPostController::class, 'data'])->name('admin.blog.data');
```

---

## 6. Shared Infrastructure

### AbstractApiController

**Location:** `Shared/Controllers/AbstractApiController.php`

```php
abstract class AbstractApiController
{
    protected function success(array $data): JsonResponse       // 200 + data
    protected function create(string $message = 'Created'): JsonResponse  // 201
    protected function noContent(?string $message = null): JsonResponse   // 204
    protected function error(string $message, ?array $errors = null, int $status = 422): JsonResponse
}
```

### SharedCriterionContract

**Location:** `Shared/Contracts/Criteria/SharedCriterionContract.php`

```php
interface SharedCriterionContract
{
    public function apply(Builder $query): Builder;
}
```

### SharedCriteriaApplierContract

**Location:** `Shared/Contracts/Criteria/SharedCriteriaApplierContract.php`

```php
interface SharedCriteriaApplierContract
{
    public function apply(Builder $query, SharedCriterionContract ...$criteria): Builder;
}
```

### UtilsCriteria (trait)

**Location:** `Application/Shared/Criteria/UtilsCriteria.php`

Provides the `$criteria[]` array and two key methods:
- `getAndResetCriteria(): array` — returns and clears the criteria list (call in Handler's `execute()`)
- `append(SharedCriterionContract): static` — adds a criterion (can be used directly or via CriteriaTrait methods)
- `lockForUpdate(): static` — adds a `SELECT FOR UPDATE` criterion

---

## 7. Architecture Rules

These rules are enforced by `tests/Architecture/ModulesTest.php` (PHPat tests).

| Rule | Detail |
|---|---|
| **Module isolation** | A module must not depend on another module, except its `Enums` |
| **No models in commands** | Handlers and Transfers must not use any Eloquent models |
| **Module internals** | Code outside a module cannot access its `Repositories`, `Providers`, or `Models` |
| **Admin exception** | `App\Admin\` is exempt from the module-internals rule — it may access Models directly |
| **Public module API** | From outside: `Commands`, `Enums`, `Entities`, `Exceptions`, `Criteria`, `Contracts` are accessible |

---

## 8. Naming Conventions

### Module layer

| Class type | Pattern | Example |
|---|---|---|
| Model | `{Feature}` | `BlogPost` |
| Enum | `{Feature}{Kind}Enum` | `BlogStatusEnum` |
| Entity | `{Feature}ItemEntity` | `BlogPostItemEntity` |
| EntityFactory | `{Feature}ItemEntityFactory` | `BlogPostItemEntityFactory` |
| Presenter | `{Feature}EntityPresenter` | `BlogPostEntityPresenter` |
| Contract | `{Feature}RepositoryContract` | `BlogPostRepositoryContract` |
| Repository | `{Feature}Repository` | `BlogPostRepository` |
| ServiceProvider | `{Feature}ServiceProvider` | `BlogServiceProvider` |
| CriteriaTrait | `{Feature}CriteriaTrait` | `BlogPostCriteriaTrait` |
| Criteria | `{Filter}Criteria` | `ByIdCriteria`, `ByStatusCriteria` |
| Command Transfer | `{Action}{Feature}Transfer` | `CreateBlogPostTransfer` |
| Handler (store) | `{Action}{Feature}Handler` | `CreateBlogPostHandler` |
| Handler (find) | `Find{Feature}Handler` | `FindBlogPostHandler` |
| Handler (paginate) | `Paginate{Feature}Handler` | `PaginateBlogPostsHandler` |

### Application layer

| Class type | Pattern | Example |
|---|---|---|
| Controller | `{Action}{Feature}Controller` | `GetBlogPostsController` |
| Request | `{Action}{Feature}Request` | `GetBlogPostsRequest` |
| RequestTransfer | `{Feature}{Purpose}RequestTransfer` | `BlogPaginateRequestTransfer` |
| Action | `{Action}{Feature}Action` | `GetBlogPostsAction` |

### Admin layer

| Class type | Pattern | Example |
|---|---|---|
| Controller | `{Feature}Controller` (one per resource) | `BlogPostController` |
| Request | `{Feature}{Action}Request` | `BlogPostStoreRequest` |
| Transfer | `{Feature}{Action}RequestTransfer` | `BlogPostStoreRequestTransfer` |
| Action | `{Feature}{Action}Action` | `BlogPostStoreAction`, `BlogPostViewAction` |

---

## 9. Data Flows

### GET list (REST API)

```
GET /api/blog?per_page=10&filter[category]=1,2

GetBlogPostsRequest::rules()       → validates input
GetBlogPostsRequest::getTransfer() → BlogPaginateRequestTransfer
GetBlogPostsController::__invoke() → calls action
GetBlogPostsAction::execute()
    PaginateBlogPostsHandler
        ->byStatus(PUBLISHED)
        ->orderByPublishedAt()
        ->execute(['per_page' => 10])
    BlogPostRepositoryContract::paginate(criteria, paginate)
    BlogPostRepository applies criteria via SharedCriteriaApplierContract
    tap(paginator) → maps BlogPost models to BlogPostEntity via Factory
    tap(paginator) → maps entities to arrays via Presenter (in Action)
AbstractApiController::success($paginator->toArray())
→ { status: true, data: { current_page: 1, data: [...], total: 42, ... } }
```

### POST create (Admin)

```
POST /admin/blog

BlogPostStoreRequest::rules()       → validates
BlogPostStoreRequest::getTransfer() → BlogPostStoreRequestTransfer
BlogPostController::store()
    try {
        BlogPostStoreAction::execute(transfer)
            Upload file if present
            CreateBlogPostHandler::execute(CreateBlogPostTransfer)
            BlogPostRepositoryContract::create(transfer)
            BlogPostEntityFactory::makeFromModel(saved model)
        redirect->route('admin.blog.index')->with('success', ...)
    } catch (\Exception $e) {
        back()->withInput()->with('error', $e->getMessage())
    }
```

### PUT update (Admin)

```
PUT /admin/blog/{id}

BlogPostUpdateRequest::getTransfer() → BlogPostUpdateRequestTransfer (includes id from route)
BlogPostController::update()
    BlogPostUpdateAction::execute(transfer)
        FindBlogPostHandler->byId(id)->execute() → Entity (or 404)
        Upload new cover if provided, otherwise keep existing
        UpdateBlogPostHandler::execute(UpdateBlogPostTransfer)
        BlogPostRepositoryContract::update(transfer) → Entity
    redirect with success flash
```

---

## 10. New Feature Checklist

### Module

- [ ] Migration: `Modules/{Feature}/Data/Migrations/`
- [ ] Enum: `{Feature}StatusEnum` (if needed)
- [ ] Model: `$table`, `$fillable`, `casts()`, `@property` for each column
- [ ] Entity: getters, `toArray()` → Presenter
- [ ] EntityFactory: `makeFromModel(Model): Entity`
- [ ] Presenter: `toArray(Entity): array`
- [ ] Contract (interface): all repository methods returning Entities
- [ ] Repository: implements Contract, uses `SharedCriteriaApplierContract`, never returns Model
- [ ] Criteria classes: `ByIdCriteria`, `ByStatusCriteria`, `OrderByCriteria`, others as needed
- [ ] CriteriaTrait: fluent methods wrapping Criteria
- [ ] Command Transfers: `Create{Feature}Transfer`, `Update{Feature}Transfer`
- [ ] Handlers: `Create`, `Update`, `Find`, `Paginate`
- [ ] ServiceProvider: bind Contract → Repository, load migrations
- [ ] Register ServiceProvider in `config/app.php`

### Application API

- [ ] RequestTransfer: primitive typed properties
- [ ] Request: `rules()` + `getTransfer()`
- [ ] Action: orchestrates handlers, converts strings → Enums
- [ ] Controller: invokable, `$this->success(...)` or `$this->create()`
- [ ] Route in `routes/api.php`

### Admin Panel

- [ ] `{Feature}StoreRequestTransfer`, `{Feature}UpdateRequestTransfer`
- [ ] `{Feature}StoreRequest`, `{Feature}UpdateRequest`
- [ ] `{Feature}ViewAction` (DataTables), `{Feature}StoreAction`, `{Feature}UpdateAction`, `{Feature}DeleteAction`
- [ ] `{Feature}Controller` (index, create, store, edit, update, destroy)
- [ ] Blade views: `resources/views/admins/{feature}/index.blade.php`, `create.blade.php`, `edit.blade.php`
- [ ] Routes in `routes/admin.php`

---

## Code Style Reminders

- `declare(strict_types=1);` in every PHP file
- All classes are `final` (use `readonly` when no setters/inheritance)
- Array alignment: `'key' => 'value'` with arrow alignment
- String concatenation: `'string' . $var` (spaces around `.`)
- No try/catch in Application controllers — exceptions are handled globally
- `@psalm-suppress PossiblyUnusedMethod` on Repository classes (Laravel DI does not call methods statically)
- Docblock format:
  ```php
  /**
   * Class {ClassName}
   *
   * @author Molozhenko
   * @package src/app/{Namespace/To/File.php}
   * @time DD.MM.YYYY
   */
  ```
