# Merchant Platform — Module Logic & Structure

**Project:** uex-merchant-platform  
**Stack:** PHP 8.2+, Laravel, MariaDB, Redis, Docker  
**Author:** Molozhenko  
**Purpose:** REST API for merchant cabinet — connected to uexapp-backend via shared database

---

## Stack & Integration

```
[Merchant Frontend]  →  [uex-merchant-platform API]  →  [Shared DB]  ←  [uexapp-backend]
                                                                               ↑
                                                                         [Admin Panel]
```

| Layer | Technology |
|---|---|
| Framework | Laravel 11+ |
| Language | PHP 8.2+ (`declare(strict_types=1)` everywhere) |
| Auth | JWT (tymon/jwt-auth) — merchant-specific tokens |
| Database | MariaDB — shared with uexapp-backend |
| Cache / Queue | Redis |
| 2FA | pragmarx/google2fa |
| KYB | SumSub SDK (company-level verification) |

### Connection to uexapp-backend

- **Shared DB**: both projects connect to the same MariaDB instance
- **Shared tables** (read-only, no migrations): `users`, `merchants`, `merchant_groups`, `merchant_apps`, `merchant_payments`, `currencies`, `fees_limits`, `user_details`, `wallet_histories`
- **Own tables** (merchant-platform owns migrations): `merchant_payment_addresses`, `merchant_enabled_currencies`, `merchant_address_assignments`, `merchant_balances`, `merchant_transactions`, `merchant_webhooks`, `merchant_webhook_logs`, `merchant_fee_rates`, `merchant_kyb_verifications`, `merchant_business_profiles`, `merchant_ip_whitelist`, `merchant_withdrawal_destinations`, `merchant_auto_settlement_rules`, `merchant_payment_links`, `merchant_auto_convert_settings`
- **Internal HTTP calls** to uexapp-backend: exchange rate (`/api/exchanges/rate`), withdrawal create (`/api/internal/merchant/withdrawal/crypto/create`)
- **Admin panel** for merchant management: implemented in `uexapp-backend/app/Admin`

---

## Architecture Layers

```
Application (REST API)     ← merchant-facing endpoints
Module (Domain)            ← business logic, isolated per feature
Shared                     ← contracts, base classes, utils
```

> Admin layer (Layer 3) is NOT in this project — all admin functionality lives in uexapp-backend/app/Admin.

---

## Enum Classes

All string/int constants use Enums. Never use raw strings in code.

### `App\Modules\MerchantApp\Enums\MerchantAppModeEnum`
```php
enum MerchantAppModeEnum: string {
    case LIVE = 'live';
    case TEST = 'test';
}
```

### `App\Modules\MerchantApp\Enums\MerchantAppStatusEnum`
```php
enum MerchantAppStatusEnum: string {
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';
}
```

### `App\Modules\MerchantPayment\Enums\MerchantPaymentStatusEnum`
```php
enum MerchantPaymentStatusEnum: string {
    case PENDING = 'Pending';
    case SUCCESS = 'Success';
    case EXPIRED = 'Expired';
    case BLOCKED = 'Blocked';
    case REFUND  = 'Refund';
}
```

### `App\Modules\MerchantTransaction\Enums\MerchantTransactionTypeEnum`
```php
enum MerchantTransactionTypeEnum: int {
    case PAYMENT_RECEIVED  = 1;
    case WITHDRAWAL        = 2;
    case REFUND            = 3;
    case ADJUSTMENT_CREDIT = 4;
    case ADJUSTMENT_DEBIT  = 5;
    case FEE               = 6;
    case CONVERSION        = 7;
}
```

### `App\Modules\MerchantTransaction\Enums\MerchantTransactionStatusEnum`
```php
enum MerchantTransactionStatusEnum: string {
    case PENDING   = 'Pending';
    case SUCCESS   = 'Success';
    case FAILED    = 'Failed';
    case APPROVED  = 'Approved';
    case REJECTED  = 'Rejected';
    case COMPLETED = 'Completed';
}
```

### `App\Modules\MerchantWebhook\Enums\MerchantWebhookStatusEnum`
```php
enum MerchantWebhookStatusEnum: string {
    case ACTIVE   = 'active';
    case DEGRADED = 'degraded';
    case DISABLED = 'disabled';
}
```

### `App\Modules\MerchantWebhook\Enums\MerchantWebhookEventEnum`
```php
enum MerchantWebhookEventEnum: string {
    case PAYMENT_SETTLED    = 'payment.settled';
    case PAYMENT_CONFIRMING = 'payment.confirming';
    case PAYMENT_FAILED     = 'payment.failed';
    case REFUND_CREATED     = 'refund.created';
    case PAYOUT_COMPLETED   = 'payout.completed';
    case PAYOUT_FAILED      = 'payout.failed';
}
```

### `App\Modules\MerchantWebhook\Enums\MerchantWebhookModeEnum`
```php
enum MerchantWebhookModeEnum: string {
    case LIVE = 'live';
    case TEST = 'test';
}
```

### `App\Modules\MerchantFeeRate\Enums\FeeLimitMetadataTypeEnum`
```php
enum FeeLimitMetadataTypeEnum: string {
    case MERCHANT_WITHDRAWAL = 'merchant_withdrawal';
}
```

### `App\Modules\MerchantKybVerification\Enums\MerchantKybStatusEnum`
```php
enum MerchantKybStatusEnum: string {
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ON_HOLD  = 'on_hold';
}
```

### `App\Modules\MerchantWithdrawalDestination\Enums\WithdrawalDestinationStatusEnum`
```php
enum WithdrawalDestinationStatusEnum: string {
    case ACTIVE      = 'active';
    case WHITELISTED = 'whitelisted';
}
```

### `App\Modules\MerchantPaymentLink\Enums\MerchantPaymentLinkStatusEnum`
```php
enum MerchantPaymentLinkStatusEnum: string {
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case SCHEDULED = 'scheduled';
    case ARCHIVED  = 'archived';
}
```

### `App\Modules\MerchantAutoSettlement\Enums\AutoSettlementTriggerEnum`
```php
enum AutoSettlementTriggerEnum: string {
    case SCHEDULE  = 'schedule';
    case THRESHOLD = 'threshold';
    case BOTH      = 'both';
}
```

---

## Module Structure

> Filled progressively as roadmap phases are implemented.

---

### Module: User *(Phase 1.2)*

**Namespace:** `App\Modules\User`  
**Table:** `users` (shared, read-only, no migration)  
**Purpose:** Identify merchant by JWT user_id, read verification status.

#### Files

| File | Purpose |
|---|---|
| `Models/User.php` | Eloquent model → `users` table |
| `Entities/UserItemEntity.php` | Immutable domain object |
| `Entities/Factories/UserItemEntityFactory.php` | `makeFromModel(User): UserItemEntity` |
| `Presenters/UserEntityPresenter.php` | `toArray(UserItemEntity): array` |
| `Contracts/Repositories/UserContract.php` | `findByCriteria(array): ?UserItemEntity` |
| `Repositories/UserRepository.php` | Implements UserContract |
| `Criteria/UserByIdCriteria.php` | `WHERE id = ?` |
| `Criteria/UserByEmailCriteria.php` | `WHERE email = ?` |
| `Commands/Handlers/FindUserByCriteriaHandler.php` | Fluent find: `->byId()->execute()` |
| `Providers/UserServiceProvider.php` | Binds UserContract → UserRepository |

---

### Module: Merchant *(Phase 1.3)*

**Namespace:** `App\Modules\Merchant`  
**Table:** `merchants` (shared, read-only, no migration)  
**Purpose:** Read merchant profile (business_name, site_url, fee, status).

#### Files

| File | Purpose |
|---|---|
| `Models/Merchant.php` | Eloquent model → `merchants` table |
| `Entities/MerchantItemEntity.php` | Domain object |
| `Entities/Factories/MerchantItemEntityFactory.php` | `makeFromModel(Merchant): MerchantItemEntity` |
| `Presenters/MerchantEntityPresenter.php` | `toArray(MerchantItemEntity): array` |
| `Contracts/Repositories/MerchantContract.php` | Standard contract methods |
| `Repositories/MerchantRepository.php` | Implements MerchantContract |
| `Criteria/MerchantByIdCriteria.php` | `WHERE id = ?` |
| `Criteria/MerchantByUserIdCriteria.php` | `WHERE user_id = ?` |
| `Commands/Handlers/FindMerchantByCriteriaHandler.php` | `->byId()->byUserId()->execute()` |
| `Providers/MerchantServiceProvider.php` | Binds MerchantContract → MerchantRepository |

---

### Module: MerchantApp *(Phase 1.4)*

**Namespace:** `App\Modules\MerchantApp`  
**Table:** `merchant_apps` (shared, we add columns via migration)  
**Purpose:** OAuth2 API credentials. Each app has mode (live/test), permissions scopes, rate limit, status.

#### Added columns (ALTER TABLE via migration)
```sql
ADD COLUMN name                varchar(255)        NOT NULL DEFAULT ''
ADD COLUMN mode                ENUM('live','test') NOT NULL DEFAULT 'live'
ADD COLUMN permissions         JSON                NOT NULL DEFAULT '["payments"]'
ADD COLUMN rate_limit_per_minute int               NULL
ADD COLUMN status              ENUM('active','suspended') NOT NULL DEFAULT 'active'
ADD COLUMN last_used_at        timestamp           NULL
```

#### Files

| File | Purpose |
|---|---|
| `Models/MerchantApp.php` | Eloquent model → `merchant_apps` |
| `Enums/MerchantAppModeEnum.php` | `LIVE = 'live'`, `TEST = 'test'` |
| `Enums/MerchantAppStatusEnum.php` | `ACTIVE = 'active'`, `SUSPENDED = 'suspended'` |
| `Entities/MerchantAppItemEntity.php` | Domain object with `getMode(): MerchantAppModeEnum`, `getPermissions(): array` |
| `Entities/Factories/MerchantAppItemEntityFactory.php` | `makeFromModel(MerchantApp): MerchantAppItemEntity` |
| `Presenters/MerchantAppEntityPresenter.php` | `toArray(MerchantAppItemEntity): array` |
| `Contracts/Repositories/MerchantAppContract.php` | Standard contract |
| `Repositories/MerchantAppRepository.php` | Implements MerchantAppContract |
| `Criteria/MerchantAppByClientIdCriteria.php` | `WHERE client_id = ?` |
| `Criteria/MerchantAppByMerchantIdCriteria.php` | `WHERE merchant_id = ?` |
| `Criteria/MerchantAppByIdCriteria.php` | `WHERE id = ?` |
| `Commands/Handlers/FindMerchantAppByCriteriaHandler.php` | Fluent find |
| `Commands/Handlers/StoreMerchantAppHandler.php` | Creates new API key |
| `Commands/Handlers/DeleteMerchantAppHandler.php` | Deletes API key |
| `Providers/MerchantAppServiceProvider.php` | Bind Contract → Repository |

---

### Module: MerchantFeeRate *(Phase 3.9)*

**Namespace:** `App\Modules\MerchantFeeRate`  
**Table:** `merchant_fee_rates` (own)  
**Purpose:** Per-merchant fee percentages with per-currency overrides. Resolution chain: per-currency → global per-merchant → config default.

#### Table schema
```sql
CREATE TABLE merchant_fee_rates (
    id          bigint PRIMARY KEY AUTO_INCREMENT,
    merchant_id bigint NOT NULL,
    currency_id int    NULL,         -- NULL = global rate for this merchant
    percent     decimal(5,2) NOT NULL,
    created_at  timestamp,
    updated_at  timestamp,
    UNIQUE KEY (merchant_id, currency_id)
)
```

#### Files

| File | Purpose |
|---|---|
| `Models/MerchantFeeRate.php` | Eloquent model |
| `Enums/FeeLimitMetadataTypeEnum.php` | `MERCHANT_WITHDRAWAL = 'merchant_withdrawal'` |
| `Entities/MerchantFeeRateItemEntity.php` | `calculateFee(string $amount): string` — bcmath; `calculateNetAmount(string $gross): string` |
| `Entities/Factories/MerchantFeeRateItemEntityFactory.php` | `makeFromModel(MerchantFeeRate): MerchantFeeRateItemEntity` |
| `Presenters/MerchantFeeRateEntityPresenter.php` | `toArray(MerchantFeeRateItemEntity): array` |
| `Contracts/Repositories/MerchantFeeRateRepositoryContract.php` | `findByCriteria()`, `upsert(merchantId, currencyId, percent)` |
| `Repositories/MerchantFeeRateRepository.php` | Implements contract |
| `Criteria/FeeRateByMerchantIdCriteria.php` | `WHERE merchant_id = ?` |
| `Criteria/FeeRateByCurrencyIdCriteria.php` | `WHERE currency_id = ?` |
| `Criteria/FeeRateGlobalOnlyCriteria.php` | `WHERE currency_id IS NULL` |
| `Traits/Commands/MerchantFeeRateCriteriaTrait.php` | `byMerchantId()`, `byCurrencyId()`, `globalOnly()` |
| `Commands/Handlers/ResolveMerchantFeeRateHandler.php` | Resolution chain → returns `string` percent |
| `Commands/Handlers/StoreMerchantFeeRateHandler.php` | Create fee rate row |
| `Commands/Handlers/UpdateMerchantFeeRateHandler.php` | Update percent |
| `Commands/Handlers/FindMerchantFeeRateByCriteriaHandler.php` | Fluent find |
| `Providers/MerchantFeeRateServiceProvider.php` | Bind Contract → Repository |

#### `ResolveMerchantFeeRateHandler::execute(int $merchantId, int $currencyId): string`

```
1. merchant_fee_rates WHERE merchant_id=$merchantId AND currency_id=$currencyId  → return percent
2. merchant_fee_rates WHERE merchant_id=$merchantId AND currency_id IS NULL      → return percent
3. config('merchant-config.default_fee_percent')                                → return default
```

---

*Document updated as phases are implemented. Add new modules below following the same pattern.*
