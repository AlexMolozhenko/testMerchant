# Architecture

**Project:** uex-merchant-platform  
**Stack:** PHP 8.2+, Laravel, MariaDB, Redis, Docker  
**Author:** Molozhenko

---

## Overview

The project follows a strict layered architecture with three main layers:

```
┌─────────────────────────────────────────┐
│          Application (REST API)         │  ← Merchant-facing API endpoints
├─────────────────────────────────────────┤
│             Admin (Web panel)           │  ← Internal admin panel
├─────────────────────────────────────────┤
│          Module (Domain)                │  ← Business logic, isolated per feature
├─────────────────────────────────────────┤
│              Shared                     │  ← Contracts, base classes, utils
└─────────────────────────────────────────┘
```

---

## PHP Rules

- `declare(strict_types=1)` in every file
- All classes are `final`; use `readonly` when no setters or inheritance needed
- Every class has a docblock:
```php
/**
 * Class {ClassName}
 *
 * @author Molozhenko
 * @package src/app/{Namespace/To/File.php}
 * @time DD.MM.YYYY
 */
```

---

## Layer 1 — Module (Domain)

Namespace root: `App\Modules\{Feature}`

Self-contained domain module. Has no dependency on Application or Admin layers.
One module = one business entity (e.g. `Merchant`, `Transaction`, `Wallet`).

### Structure

```
Modules/{Feature}/
  Models/             {Feature}.php
  Enums/              {Feature}TypeEnum.php
  Entities/           {Feature}ItemEntity.php
  Entities/Factories/ {Feature}ItemEntityFactory.php
  Presenters/         {Feature}EntityPresenter.php
  Contracts/Repositories/  {Feature}Contract.php
  Repositories/       {Feature}Repository.php
  Criteria/           {Feature}ByIdCriteria.php
                      {Feature}ActiveOnlyCriteria.php
                      {Feature}TypeCriteria.php
                      {Feature}OrderByCriteria.php
  Traits/Commands/    {Feature}CriteriaTrait.php
  Commands/Transfers/ Store{Feature}Transfer.php
                      Update{Feature}Transfer.php
  Commands/Handlers/{Feature}/
                      Store{Feature}Handler.php
                      Update{Feature}Handler.php
                      Find{Feature}ByCriteriaHandler.php
                      Paginate{Feature}Handler.php
  Providers/          {Feature}ServiceProvider.php
```

### Components

**Model** — Eloquent model. Has `$table`, `$fillable`, `$casts`. Does not expose relations to other modules. Has `@property` docblock for each field.

**Enum** — backed enum (`: string`). Values in `snake_case`.

**Entity** — `final class` implementing `Arrayable`. All properties are `private`. `id` and timestamps are `readonly`. Other fields have fluent setters returning `$this`. `toArray()` delegates to `{Feature}EntityPresenter::toArray($this)`.

**EntityFactory** — `final readonly class`. Single static method `makeFromModel(Model $model): Entity`. Maps model fields to entity constructor.

**Presenter** — `final readonly class`. Single static method `toArray(Entity $entity): array`. Formats Carbon to ISO-8601, casts Enums to `.value`.

**Contract** — interface. Standard methods:
```php
getActive(): Collection
paginate(array $criteria = [], array $paginate = []): LengthAwarePaginator
findByCriteria(array $criteria): ?Entity
create(StoreTransfer $transfer): Entity
update(Entity $entity): Entity
delete(Entity $entity): void
```

**Repository** — implements Contract. Injects `SharedCriteriaApplierContract`. Never returns Model outside — always Entity. Private `makeEntity(Model): Entity` calls EntityFactory.

**Criteria** — `final readonly class` implementing `SharedCriterionContract`. One class = one filter condition. `apply(Builder $query): Builder`.

**CriteriaTrait** — `trait`. Uses `UtilsCriteria`. Fluent methods: `onlyActive()`, `byType()`, `orderBy()` — push Criteria into `$this->criteria[]`, return `static`.

**Command Transfers** — `final readonly class`. Constructor with typed public properties. Enum fields already converted (not raw strings).

**Handlers:**

| Handler | Injects | execute() |
|---|---|---|
| `Store{Feature}Handler` | Contract | `(StoreTransfer): Entity` → `contract->create()` |
| `Update{Feature}Handler` | Contract | `(Entity): Entity` → `contract->update()` |
| `Find{Feature}ByCriteriaHandler` | Contract | `(): ?Entity` → `contract->findByCriteria()`, fluent `byId(int $id): self` |
| `Paginate{Feature}Handler` | Contract + CriteriaTrait | `(array $paginate): LengthAwarePaginator` |

**ServiceProvider** — binds `{Feature}Contract::class → {Feature}Repository::class` in `register()`.

---

## Layer 2 — Application (REST API)

Namespace root: `App\Application`

JSON REST API. One controller = one action (invokable). No try/catch — exceptions handled globally.

### Data flow

```
HTTP Request
  → {Action}{Feature}Request::rules()        validation
  → {Action}{Feature}Request::getTransfer()  mapping to Transfer
  → {Action}{Feature}Controller::__invoke()
  → {Action}{Feature}Action::execute(transfer)
  → Handler(s) from Module layer
  → AbstractApiController::success($result->toArray())
```

### Structure

```
Application/
  Http/Controllers/Dashboard/{Feature}/   {Action}{Feature}Controller.php
  Http/Requests/Dashboard/{Feature}/      {Action}{Feature}Request.php
  Transfers/Requests/Dashboard/{Feature}/ {Action}{Feature}RequestTransfer.php
  Actions/Dashboard/{Feature}/            {Action}{Feature}Action.php
```

### Controller pattern

```php
final class GetMerchantController extends AbstractApiController
{
    public function __construct(private readonly GetMerchantAction $action) {}

    public function __invoke(GetMerchantRequest $request): JsonResponse
    {
        $result = $this->action->execute($request->getTransfer());
        return $this->success($result->toArray());
    }
}
```

### Response methods (AbstractApiController)

| Method | HTTP | Usage |
|---|---|---|
| `success(array $data)` | 200 | GET, list, update |
| `create(string $message)` | 201 | POST store |
| `noContent(?string $message)` | 204 | DELETE |
| `error(string $message, ?array $errors, int $status)` | 4xx/5xx | global handler |

### Request

```php
public function rules(): array { ... }
public function getTransfer(): CreateMerchantRequestTransfer { ... }
```

### Transfer (Request-level)

```php
final readonly class CreateMerchantRequestTransfer
{
    public function __construct(
        public string $name,
        public string $email,
        public string|null $siteUrl,
    ) {}
}
```
Fields are raw types (`string|null`, `int`, `bool`). Action converts strings to Enums.

### Action

```php
final readonly class CreateMerchantAction
{
    public function __construct(private StoreMerchantHandler $handler) {}

    public function execute(CreateMerchantRequestTransfer $transfer): MerchantItemEntity
    {
        return $this->handler->execute(new StoreMerchantTransfer(
            name: $transfer->name,
            status: MerchantStatusEnum::from($transfer->status),
        ));
    }
}
```

---

## Layer 3 — Admin (Web panel)

Namespace root: `App\Admin`

Blade templates + DataTables AJAX. One controller = full CRUD for a resource.

### Structure

```
Admin/
  Http/Controllers/             {Feature}Controller.php
  Http/Requests/{Feature}/      {Feature}StoreRequest.php
                                {Feature}UpdateRequest.php
  Transfers/Requests/{Feature}/ {Feature}StoreRequestTransfer.php
                                {Feature}UpdateRequestTransfer.php
  Actions/{Feature}/            {Feature}ViewAction.php
                                {Feature}StoreAction.php
                                {Feature}UpdateAction.php
                                {Feature}DeleteAction.php
```

### Controller methods

```
index()   → View|JsonResponse   checks request()->ajax() && has('draw')
data()    → JsonResponse        DataTables endpoint
create()  → View
store()   → RedirectResponse    try/catch + redirect with flash
edit()    → View                FindByCriteriaHandler->byId() or throw NotFoundHttpException
update()  → RedirectResponse    try/catch
destroy() → JsonResponse        try/catch
```

### Actions

| Action | execute() |
|---|---|
| `ViewAction` | returns `[$heads, $config]` for DataTables |
| `StoreAction` | creates Entity via StoreHandler, uploads files, updates Entity |
| `UpdateAction` | finds Entity, merges old/new files, mutates via setters, saves |
| `DeleteAction` | finds Entity, deletes files, calls `Contract->delete()` |

---

## Layer 4 — Shared

```
Shared/
  Controllers/AbstractApiController.php
  Contracts/Criteria/SharedCriterionContract.php      apply(Builder): Builder
  Contracts/Criteria/SharedCriteriaApplierContract.php
  Contracts/Files/FileUploaderContract.php
Application/Shared/Criteria/UtilsCriteria.php         trait, $criteria[], getAndResetCriteria()
```

---

## Naming Conventions

| Type | Pattern | Example |
|---|---|---|
| Module feature | PascalCase singular | `Merchant` |
| DB table | snake_case plural | `merchants` |
| Entity | `{Feature}ItemEntity` | `MerchantItemEntity` |
| EntityFactory | `{Feature}ItemEntityFactory` | `MerchantItemEntityFactory` |
| Presenter | `{Feature}EntityPresenter` | `MerchantEntityPresenter` |
| Contract | `{Feature}Contract` | `MerchantContract` |
| Repository | `{Feature}Repository` | `MerchantRepository` |
| ServiceProvider | `{Feature}ServiceProvider` | `MerchantServiceProvider` |
| Store Handler | `Store{Feature}Handler` | `StoreMerchantHandler` |
| Update Handler | `Update{Feature}Handler` | `UpdateMerchantHandler` |
| Find Handler | `Find{Feature}ByCriteriaHandler` | `FindMerchantByCriteriaHandler` |
| Paginate Handler | `Paginate{Feature}Handler` | `PaginateMerchantHandler` |
| API Controller | `{Action}{Feature}Controller` | `CreateMerchantController` |
| API Request | `{Action}{Feature}Request` | `CreateMerchantRequest` |
| API Transfer | `{Action}{Feature}RequestTransfer` | `CreateMerchantRequestTransfer` |
| API Action | `{Action}{Feature}Action` | `CreateMerchantAction` |
| Admin Controller | `{Feature}Controller` | `MerchantController` |
| Admin Action | `{Feature}{Action}Action` | `MerchantStoreAction` |
| Criteria | `{Feature}{Filter}Criteria` | `MerchantByIdCriteria` |
| CriteriaTrait | `{Feature}CriteriaTrait` | `MerchantCriteriaTrait` |
| Command Transfer | `{Action}{Feature}Transfer` | `StoreMerchantTransfer` |

---

## Key Patterns

### Find or 404
```php
$entity = $this->handler->byId($id)->execute() ?? throw new NotFoundHttpException();
```

### Fluent criteria
```php
$this->handler
     ->onlyActive()
     ->byStatus(MerchantStatusEnum::from($transfer->status))
     ->orderBy('created_at', 'desc')
     ->execute(['per_page' => $transfer->perPage]);
```

### Admin try/catch
```php
try {
    $this->action->execute($request->getTransfer());
    return redirect()->route('admin.merchants.index')->with('success', 'Created successfully');
} catch (\Exception $e) {
    return back()->withInput()->with('error', $e->getMessage());
}
```

### Paginator enrichment
```php
return tap($paginator, static function (LengthAwarePaginator $paginator) {
    $paginator->setCollection(
        $paginator->getCollection()->map(fn($entity) => $entity->toArray())
    );
});
```

---

## New Feature Checklist

### Module
- [ ] Migration: create table
- [ ] Enum: `{Feature}TypeEnum` (if needed)
- [ ] Model: `$table`, `$fillable`, `$casts`, `@property` docblock
- [ ] Entity: getters + fluent setters + `toArray()` → Presenter
- [ ] EntityFactory: `makeFromModel(Model): Entity`
- [ ] Presenter: `toArray(Entity): array`
- [ ] Contract: interface with standard methods
- [ ] Repository: implements Contract
- [ ] Criteria: ByIdCriteria, ActiveOnlyCriteria, TypeCriteria, OrderByCriteria
- [ ] CriteriaTrait: `onlyActive()`, `byType()`, `orderBy()`
- [ ] Command Transfers: `Store{Feature}Transfer`, `Update{Feature}Transfer`
- [ ] Handlers: Store, Update, FindByCriteria, Paginate
- [ ] ServiceProvider: bind Contract → Repository
- [ ] Register ServiceProvider in `config/app.php`

### Application API
- [ ] `{Action}{Feature}RequestTransfer`
- [ ] `{Action}{Feature}Request` (rules + getTransfer)
- [ ] `{Action}{Feature}Action` (execute with Handler)
- [ ] `{Action}{Feature}Controller` (invokable, extends AbstractApiController)
- [ ] Route in `routes/api.php`

### Admin Panel
- [ ] `{Feature}StoreRequestTransfer`, `{Feature}UpdateRequestTransfer`
- [ ] `{Feature}StoreRequest`, `{Feature}UpdateRequest`
- [ ] `{Feature}ViewAction`, `{Feature}StoreAction`, `{Feature}UpdateAction`, `{Feature}DeleteAction`
- [ ] `{Feature}Controller` (index, data, create, store, edit, update, destroy)
- [ ] Views: `admins/{feature}/index.blade.php`, `create.blade.php`, `edit.blade.php`
- [ ] Routes in `routes/web.php`
