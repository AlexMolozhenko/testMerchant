# Development Roadmap

**Project:** uex-merchant-platform (API only, no frontend)  
**Author:** Molozhenko  
**Goal:** REST API for merchant cabinet — connected to uexapp-backend via shared database

---

## Architecture Principle

```
[Merchant Frontend]  →  [uex-merchant-platform API]  →  [Shared DB]  ←  [uexapp-backend]
                                                                               ↑
                                                                         [Admin Panel]
```

- uex-merchant-platform и uexapp-backend работают с **одной БД**
- Мерчант-платформа **читает** shared-таблицы (users, currencies, merchants)
- Мерчант-платформа **создаёт** свои таблицы (webhooks, балансы мерчантов, payout requests, enabled currencies)
- Изменения в shared-таблицах (например, верификация пользователя) — через API uexapp-backend или напрямую в БД согласованно
- **Вся административная часть** (merchant withdrawals, fee rates, KYB review, настройки мерчантов) — реализуется в `uexapp-backend/app/Admin`, **не** в merchant-platform

---

## Shared Tables (read, no ownership)

Таблицы из uexapp-backend — без миграций, только читаем/используем:

| Table | Usage |
|---|---|
| `users` | Идентификация мерчанта. Поля: `id`, `email`, `sub_verified`, `identity_verified`, `applicant_id` |
| `merchants` | Профиль мерчанта. Поля: `user_id`, `merchant_uuid`, `business_name`, `site_url`, `fee`, `status` (Moderation/Disapproved/Approved), `logo` |
| `merchant_groups` | Группы мерчантов (read-only, для отображения). Поля: `name`, `is_default` |
| `merchant_apps` | API credentials мерчанта. Поля: `merchant_id`, `client_id`, `client_secret` — используем для OAuth2 |
| `merchant_payments` | Платёжные сессии + транзакции. Поля: `order_no`, `uuid`, `amount`, `total`, `fee`, `status` (Pending/Success/Refund/Blocked), `currency_id`, `user_id` |
| `currencies` | Список доступных монет. Поля: `id`, `symbol`, `code`, `name`, `logo`, `status`, `rate` |

---

## Own Tables (merchant-platform owns)

Таблицы которых нет в shared DB — создаём своими миграциями:

| Table | Purpose |
|---|---|
| `merchant_payment_addresses` | глобальный пул крипто-адресов для приёма платежей |
| `merchant_enabled_currencies` | крипто-валюты которые мерчант принимает к оплате |
| `merchant_address_assignments` | активные назначения адреса под конкретный платёж мерчанта |
| `merchant_balances` | балансы мерчанта по каждой валюте (amount, frozen_amount) |
| `merchant_transactions` | **унифицированный леджер** всех движений по балансу мерчанта (зачисления, выводы, рефанды, корректировки). Полиморфная ссылка `transaction_reference_id` на источник (merchant_payments.id для PAYMENT_RECEIVED, NULL для WITHDRAWAL). Структура mirror-ит `transactions` в uexapp-backend. |
| `merchant_webhooks` | настроенные webhook endpoints мерчанта |
| `merchant_webhook_logs` | лог доставки webhook событий |

---

## Enum Reference

Все строковые константы используются **только через Enum** — никаких сырых строк в коде.

### Модуль MerchantApp

```php
// App\Modules\MerchantApp\Enums\MerchantAppModeEnum
enum MerchantAppModeEnum: string {
    case LIVE = 'live';
    case TEST = 'test';
}

// App\Modules\MerchantApp\Enums\MerchantAppStatusEnum
enum MerchantAppStatusEnum: string {
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';
}
```

### Модуль MerchantPayment

```php
// App\Modules\MerchantPayment\Enums\MerchantPaymentStatusEnum
enum MerchantPaymentStatusEnum: string {
    case PENDING = 'Pending';
    case SUCCESS = 'Success';
    case EXPIRED = 'Expired';
    case BLOCKED = 'Blocked';
    case REFUND  = 'Refund';
}
```

### Модуль MerchantTransaction

```php
// App\Modules\MerchantTransaction\Enums\MerchantTransactionTypeEnum
enum MerchantTransactionTypeEnum: int {
    case PAYMENT_RECEIVED    = 1;
    case WITHDRAWAL          = 2;
    case REFUND              = 3;
    case ADJUSTMENT_CREDIT   = 4;
    case ADJUSTMENT_DEBIT    = 5;
    case FEE                 = 6;
    case CONVERSION          = 7;
}

// App\Modules\MerchantTransaction\Enums\MerchantTransactionStatusEnum
enum MerchantTransactionStatusEnum: string {
    case PENDING   = 'Pending';
    case SUCCESS   = 'Success';
    case FAILED    = 'Failed';
    case APPROVED  = 'Approved';
    case REJECTED  = 'Rejected';
    case COMPLETED = 'Completed';
}
```

### Модуль MerchantWebhook

```php
// App\Modules\MerchantWebhook\Enums\MerchantWebhookStatusEnum
enum MerchantWebhookStatusEnum: string {
    case ACTIVE   = 'active';
    case DEGRADED = 'degraded';
    case DISABLED = 'disabled';
}

// App\Modules\MerchantWebhook\Enums\MerchantWebhookEventEnum
enum MerchantWebhookEventEnum: string {
    case PAYMENT_SETTLED    = 'payment.settled';
    case PAYMENT_CONFIRMING = 'payment.confirming';
    case PAYMENT_FAILED     = 'payment.failed';
    case REFUND_CREATED     = 'refund.created';
    case PAYOUT_COMPLETED   = 'payout.completed';
    case PAYOUT_FAILED      = 'payout.failed';
}

// App\Modules\MerchantWebhook\Enums\MerchantWebhookModeEnum
enum MerchantWebhookModeEnum: string {
    case LIVE = 'live';
    case TEST = 'test';
}
```

### Модуль MerchantFeeRate / FeeLimit

```php
// App\Modules\MerchantFeeRate\Enums\FeeLimitMetadataTypeEnum
enum FeeLimitMetadataTypeEnum: string {
    case MERCHANT_WITHDRAWAL = 'merchant_withdrawal';
}
```

### Модуль MerchantKybVerification

```php
// App\Modules\MerchantKybVerification\Enums\MerchantKybStatusEnum
enum MerchantKybStatusEnum: string {
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ON_HOLD  = 'on_hold';
}
```

### Модуль MerchantWithdrawalDestination

```php
// App\Modules\MerchantWithdrawalDestination\Enums\WithdrawalDestinationStatusEnum
enum WithdrawalDestinationStatusEnum: string {
    case ACTIVE      = 'active';
    case WHITELISTED = 'whitelisted';
}
```

### Модуль MerchantPaymentLink

```php
// App\Modules\MerchantPaymentLink\Enums\MerchantPaymentLinkStatusEnum
enum MerchantPaymentLinkStatusEnum: string {
    case DRAFT     = 'draft';
    case ACTIVE    = 'active';
    case SCHEDULED = 'scheduled';
    case ARCHIVED  = 'archived';
}
```

### Модуль MerchantAutoSettlement

```php
// App\Modules\MerchantAutoSettlement\Enums\AutoSettlementTriggerEnum
enum AutoSettlementTriggerEnum: string {
    case SCHEDULE  = 'schedule';
    case THRESHOLD = 'threshold';
    case BOTH      = 'both';
}
```

---

## Phases

---

### Phase 1 — Foundation & Auth

**Goal:** Проект поднимается, подключается к shared DB, базовая OAuth2 аутентификация работает.

#### 1.1 Project Setup
- [ ] Настроить `.env` — подключение к shared DB (uexapp-backend database)
- [ ] Удалить стандартные Laravel миграции (users, sessions и т.д.) — они уже есть в shared DB
- [ ] Настроить `config/database.php` — единственное подключение к shared DB
- [ ] Установить зависимости: `tymon/jwt-auth` для выдачи merchant JWT
- [ ] Настроить `Shared` слой: `AbstractApiController`, `SharedCriterionContract`, `SharedCriteriaApplierContract`
- [ ] Настроить глобальный exception handler (JSON-ответы для всех ошибок)

#### 1.2 Module: User (read-only, shared)
- [ ] Model `User` → таблица `users` (только чтение, без миграции)
- [ ] Entity `UserItemEntity`
- [ ] EntityFactory, Presenter
- [ ] Contract + Repository (только `findByCriteria`, `findById`)
- [ ] Criteria: `UserByIdCriteria`, `UserByEmailCriteria`
- [ ] Handler: `FindUserByCriteriaHandler`

#### 1.3 Module: Merchant (read shared + own logic)
- [ ] Model `Merchant` → таблица `merchants` (без миграции, читаем существующую)
- [ ] Entity, EntityFactory, Presenter
- [ ] Contract + Repository

#### 1.4 Module: MerchantApp (shared table)
- [ ] Model `MerchantApp` → таблица `merchant_apps` (без миграции)
- [ ] Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `MerchantAppByClientIdCriteria`, `MerchantAppByMerchantIdCriteria`, `MerchantAppByIdCriteria`
- [ ] Handlers: `FindMerchantAppByCriteriaHandler`, `StoreMerchantAppHandler`, `DeleteMerchantAppHandler`

#### 1.5 Application: Auth Endpoints

**Auth strategy: Shared Secret (HS256)**
- uexapp-backend и uex-merchant-platform используют один `JWT_SECRET` (HS256)
- Merchant platform декодирует backend JWT, верифицирует подпись, извлекает `user_id`
- Выдаёт собственный merchant JWT с другим payload и временем жизни
- Backend JWT дропается — в merchant platform не используется

`.env` uex-merchant-platform:
```
BACKEND_JWT_SECRET=   # тот же секрет что в uexapp-backend
MERCHANT_JWT_SECRET=  # свой секрет для merchant токенов
MERCHANT_JWT_TTL=     # время жизни merchant токена в минутах
```

- [ ] `POST /merchant/auth/exchange`
  - Input: `{ token: "<backend_jwt>" }`
  - Декодирует backend JWT через `BACKEND_JWT_SECRET`
  - Находит user в `users`, проверяет наличие записи в `merchants` со статусом `active`
  - Выдаёт merchant JWT: `{ user_id, merchant_id, merchant_uuid }`
  - Return: `{ access_token, token_type, expires_in }`
- [ ] `POST /merchant/oauth2/token`
  - Input: `{ client_id, client_secret, grant_type: "client_credentials" }`
  - Для server-to-server интеграции (сервер мерчанта → наш API)
  - Validate credentials against `merchant_api_credentials`
  - Issue merchant JWT: `{ merchant_id, scope }`
  - Return: `{ access_token, token_type, expires_in }`
- [ ] Middleware: `MerchantAuthMiddleware` — верифицирует merchant JWT на всех защищённых роутах

#### 1.6 Application: Merchant Profile
- [ ] `GET /merchant/profile` — данные мерчанта (business_name, site_url, logo, fee, status)

---

### Phase 2 — Payment Flow (Core)

**Goal:** Мерчант создаёт ссылку на оплату с конвертацией валюты, покупатель платит, мерчант получает webhook.

#### 2.1 Module: Currency (read-only, shared)
- [ ] Model `Currency` → таблица `currencies`
- [ ] Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `CurrencyByIdCriteria`, `CurrencyByCodeCriteria`, `CurrencyBySearchCriteria` (name/symbol/code LIKE), `CurrencyByNetworkCriteria`
- [ ] `GET /merchant/currencies` — полный список валют (фиат + крипто) для выбора валюты цены
- [ ] `GET /merchant/currencies/crypto` — только крипто-валюты (для выбора валют оплаты)

##### Страница "Валюты и сети" — обогащённый список

`GET /merchant/currencies/manage` — грид валют с карточками (mer-5.2/5.3).
Поддерживает фильтры и обогащение данными мерчанта.

**Query params:**
- `filter[tab]=all|enabled|disabled` — вкладки
- `filter[chain]=ERC-20` — фильтр по сети
- `search=BTC` — поиск по name/symbol/code

**Data Enrichment (batch-fetch паттерн):**
```
currencies list (из shared DB)
  → batch: merchant_enabled_currencies → Set<currency_id> для флага is_enabled
  → batch: merchant_fee_rates WHERE merchant_id = $merchantId → fee_percent (через ResolveMerchantFeeRateHandler per currency)
  → batch: merchant_transactions WHERE type=PAYMENT_RECEIVED AND status=Success
           AND created_at >= now()-7d GROUP BY currency_id → volume_7d (native amount + USD)
  → tap() → обогатить каждую карточку
```

**Response (карточка валюты):**
```json
{
  "id": 1,
  "symbol": "BTC",
  "code": "btc",
  "name": "Bitcoin",
  "logo": "...",
  "networks": ["Bitcoin", "Lightning"],
  "confirmations": 3,
  "min_fee": "1.10",
  "min_fee_currency": "USD",
  "volume_7d": "0.18420000",
  "volume_7d_usd": "108420.00",
  "is_enabled": true
}
```

> `confirmations` берётся из `config('chains.{network}.confirmations')` — конфиг по сети.
> `volume_7d` — объём конкретного мерчанта (не платформы) за 7 дней. Те же данные что Phase 5.4.

**`GET /merchant/currencies/stats`** — счётчики шапки страницы:
```json
{
  "enabled_count": 10,
  "available_count": 16,
  "total_in_catalog": 350,
  "supported_networks": 14,
  "auto_convert_enabled": true,
  "auto_convert_to": { "id": 2, "symbol": "USDT", "network": "ERC-20" }
}
```

**Application checklist (currencies/manage):**
- [ ] `GetMerchantCurrenciesManageRequestTransfer` — filters + search + per_page
- [ ] `GetMerchantCurrenciesManageRequest`
- [ ] `GetMerchantCurrenciesManageAction` — batch-fetch enrichment
- [ ] `GetMerchantCurrenciesManageController`
- [ ] `GetMerchantCurrenciesStatsAction`, `GetMerchantCurrenciesStatsController`

#### 2.2 Module: MerchantPaymentAddress (own) — глобальный пул адресов

Адрес не принадлежит мерчанту постоянно — он арендуется пока валюта активна у мерчанта.

- [ ] Migration: `merchant_payment_addresses`
  - `id`, `chain_id`, `address`, `status` (free/occupied), `created_at`
  - Без `merchant_id` и без `currency_id` — адрес привязан к **chain**, один адрес покрывает все токены на сети (USDT ERC-20, USDC, ETH — всё на одном ETH-адресе)
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `AddressByChainIdCriteria`, `AddressByStatusCriteria`, `AddressByAddressCriteria`
- [ ] Handlers: `FindFreeAddressHandler`, `StoreAddressHandler`, `UpdateAddressStatusHandler`
- [ ] Service: `CryptoGatewayService` — вызов Crypto Gateway для генерации нового адреса
  - Конфиг: `CRYPTO_GATEWAY_URL`, `CRYPTO_GATEWAY_SECRET` (те же что в uexapp-backend)
  - Метод: `createWallet(string $chain): string`

#### 2.3 Module: MerchantEnabledCurrency (own)
Хранит только список крипто-валют которые мерчант принимает. Без привязки к адресам — адреса управляются отдельно через `merchant_address_assignments`.

- [ ] Migration: `merchant_enabled_currencies`
  - `id`, `merchant_id`, `currency_id`, `created_at`
  - Уникальный индекс: `(merchant_id, currency_id)`
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `EnabledCurrencyByMerchantIdCriteria`, `EnabledCurrencyByCurrencyIdCriteria`, `EnabledCurrencyByIdCriteria`
- [ ] Handlers: `StoreMerchantEnabledCurrencyHandler`, `DeleteMerchantEnabledCurrencyHandler`, `FindMerchantEnabledCurrencyByCriteriaHandler`
- [ ] `GET /merchant/currencies/enabled`
- [ ] `POST /merchant/currencies/enable` — просто добавляет валюту в список
- [ ] `DELETE /merchant/currencies/{id}/disable` — удаляет валюту из списка (только если нет активных платежей)

#### 2.3b Module: MerchantAutoConvert (own)

Настройка авто-конвертации входящих платежей. Независима от `MerchantAutoSettlement` (Phase 3.10) — та про вывод, эта про входящие зачисления.

**Migration: `merchant_auto_convert_settings`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint unique | один мерчант — одна настройка |
| `is_enabled` | bool default false | глобальный toggle |
| `to_currency_id` | int nullable | FK → currencies (USDT ERC-20) |
| `created_at` / `updated_at` | | |

**Module checklist:**
- [ ] Migration `merchant_auto_convert_settings`
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `AutoConvertByMerchantIdCriteria`
- [ ] Transfers: `StoreAutoConvertTransfer`, `UpdateAutoConvertTransfer`
- [ ] Handlers: `StoreAutoConvertHandler`, `UpdateAutoConvertHandler`, `FindAutoConvertByCriteriaHandler`
- [ ] ServiceProvider `AutoConvertServiceProvider`

**Application API:**
- [ ] `GET /merchant/currencies/auto-convert` — получить текущую настройку
- [ ] `PATCH /merchant/currencies/auto-convert` — обновить (`is_enabled`, `to_currency_id`)

#### 2.4 Module: MerchantAddressAssignment (own)
Таблица активных назначений: какой адрес используется под какой платёж какого мерчанта прямо сейчас.

- [ ] Migration: `merchant_address_assignments`
  - `id`, `merchant_id`, `currency_id`, `address_id` (FK → merchant_payment_addresses), `merchant_payment_id` (FK → merchant_payments), `assigned_at`
  - Уникальный индекс: `(address_id)` — один адрес не может быть назначен дважды одновременно
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `AssignmentByMerchantIdCriteria`, `AssignmentByCurrencyIdCriteria`, `AssignmentByAddressIdCriteria`, `AssignmentByPaymentIdCriteria`
- [ ] Handlers: `StoreAddressAssignmentHandler`, `DeleteAddressAssignmentHandler`, `FindAddressAssignmentByCriteriaHandler`

**Флоу создания платежа (назначение адреса):**
```
POST /merchant/generate-payment-url
  → Получаем currency (crypto_currency_id) → берём currency.chain_id
  → Ищем free адрес в merchant_payment_addresses WHERE chain_id = currency.chain_id
  → Если нет → CryptoGatewayService->createWallet(chain) → новый адрес в пул
  → INSERT wallet_histories (shared DB): user_id = merchant.user_id, chain_id, address
  → address: status → occupied
  → INSERT merchant_address_assignments (merchant_id, currency_id, address_id, payment_id)
  // currency_id хранится в assignments — нужен чтобы знать какой именно токен ожидаем
```

**Флоу завершения / истечения платежа (освобождение адреса):**
```
Платёж → Success / Expired
  → DELETE merchant_address_assignments
  → DELETE wallet_histories WHERE address = ? AND user_id = ?
  → address: status → free
  (адрес возвращается в глобальный пул, готов для любого мерчанта)
```

> **Запись в shared DB:** `wallet_histories` пишем напрямую из merchant platform.
> `INSERT` при назначении адреса, `DELETE` при освобождении.

#### 2.5 Module: MerchantPayment (shared table)
- [ ] Model `MerchantPayment` → таблица `merchant_payments` (без миграции)
- [ ] Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: ByMerchantId, ByStatus, ByCurrency, ByDateRange, OrderBy
- [ ] Handlers: Store, FindByCriteria, Paginate, Update (status)

**Поля платежа (фиксируются при создании):**
- `amount` — сумма в оригинальной валюте цены (например, 100 USD)
- `currency_id` — валюта цены (может быть фиат, например USD)
- `total` — точная крипто-сумма (например, 100.00 USDT)
- `payment_method_id` → currency_id выбранной крипто-валюты (ETH, USDT и т.д.)
- `percentage` — курс конвертации на момент создания (например, 0.9998 USD/USDT)
- `uuid` — идентификатор сессии для payment_url
- `gateway_reference` — адрес для оплаты (из `merchant_payment_addresses`)
- `parent_payment_id` — ссылка на исходный платёж (для доплат, опционально)

#### 2.5b Module: MerchantTransaction (own) — унифицированный леджер

Mirror-ит структуру `transactions` из uexapp-backend. Одна таблица на все типы движений по балансу мерчанта.

- [ ] Migration: `merchant_transactions`
  - `id`, `merchant_id`, `currency_id`, `uuid`
  - `transaction_type_id` — int, discriminator типа (см. enum ниже)
  - `transaction_reference_id` — nullable int, полиморфная ссылка на источник
  - `amount` — decimal, сумма транзакции в `currency_id`
  - `status` — string (Pending/Success/Failed/Approved/Rejected/Completed — набор зависит от type)
  - `metadata` — JSON, type-специфичные поля
  - `created_at`, `updated_at`
  - Индексы: `(merchant_id, created_at)`, `(merchant_id, transaction_type_id)`, `(transaction_type_id, transaction_reference_id)`
- [ ] Enum `MerchantTransactionTypeEnum`
  - `PAYMENT_RECEIVED = 1` — зачисление от покупателя. `reference_id` → `merchant_payments.id`. metadata: `{ expected_amount, received_amount, difference, is_exact, tx_hash, confirmations }`
  - `WITHDRAWAL = 2` — вывод мерчанта. `reference_id = NULL`. metadata: `{ recipient, network, fee, tx_hash?, admin_id?, rejected_reason? }`
  - `CONVERSION = 7` — авто-конвертация входящего платежа. `reference_id` → исходный `merchant_transactions.id` (PAYMENT_RECEIVED). metadata: `{ from_currency_id, from_amount, to_currency_id, to_amount, rate, exchange_commission }`
  - (extensible: `REFUND = 3`, `ADJUSTMENT_CREDIT = 4`, `ADJUSTMENT_DEBIT = 5`, `FEE = 6` — без миграций структуры)
- [ ] Model `MerchantTransaction`, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `TransactionByMerchantIdCriteria`, `TransactionByTypeCriteria`, `TransactionByStatusCriteria`, `TransactionByReferenceCriteria`, `TransactionByDateRangeCriteria`, `TransactionByCurrencyCriteria`, `OrderByCreatedAtCriteria`
- [ ] Handlers: `StoreMerchantTransactionHandler`, `UpdateMerchantTransactionHandler`, `FindMerchantTransactionByCriteriaHandler`, `PaginateMerchantTransactionHandler`

#### 2.6 Application: Generate Payment URL

**Модель комиссии: fee добавляется сверху (pass-through to buyer).**
Мерчант указывает сумму к получению (`amount`) — это `net_amount`. Fee рассчитывается поверх: покупатель платит `gross = net + fee`. Мерчант получает ровно `net_amount` независимо от fee.

- [ ] `POST /merchant/generate-payment-url`
  - Auth: Bearer token
  - Input:
    ```json
    {
      "amount": "100.00",
      "price_currency_id": 1,
      "crypto_currency_id": 3,
      "order_no": "ORD-777",
      "item_name": "iPhone 15",
      "parent_payment_id": null
    }
    ```
  - Шаги:
    1. Получить курс конвертации: `POST {BACKEND_URL}/api/exchanges/rate`
    2. `net_crypto_amount` = конвертированная сумма из ответа (100 USD → 100.02 USDT)
    3. Получить ставку мерчанта: `ResolveMerchantFeeRateHandler->execute($merchantId, $currencyId)` (Phase 3.9)
       — возвращает `string` процент (e.g. `"1.00"`) по цепочке: per-currency → global per-merchant → config default
    4. Рассчитать (всё через `bcmath`):
       ```
       // fee всегда платит мерчант — покупатель видит net_crypto_amount
       // fee снимается из зачисляемой суммы при получении платежа
       fee_amount   = bcmul($netCryptoAmount, bcdiv($feePercent, '100', 8), 8)
       gross_crypto = $netCryptoAmount   // покупатель всегда платит ровно net
       ```
       Если `feePercent = '0.00'` → `fee_amount = '0.00000000'`
    5. Назначить адрес через `MerchantAddressAssignment` (см. 2.4)
    6. Установить `expires_at` = now + `MERCHANT_PAYMENT_TTL_MINUTES` (default 120)
    7. Создать `merchant_payments`:
       - `amount` = исходная сумма мерчанта в price_currency (100.00 USD)
       - `total` = `gross_crypto` (101.00 USDT) — **то что должен заплатить покупатель**
       - `fee` = `fee_amount` в крипто (1.00 USDT) — зафиксировано на момент создания
       - `percentage` = курс конвертации
       - `metadata`: `{ net_amount, fee_amount, fee_percentage, gross_amount }`
       - `status = MerchantPaymentStatusEnum::PENDING`, `parent_payment_id` если передан
  - Returns:
    ```json
    {
      "payment_url": "https://pay.uex.com/pay/{uuid}",
      "address": "TKHfnNi7CMrnF7ME3gL4BXr1qo9VnvrRcs",
      "crypto_amount": "101.00",
      "net_amount": "100.00",
      "fee_amount": "1.00",
      "fee_percentage": "1.00",
      "currency": "USDT",
      "network": "TRC-20",
      "rate": "1.0002",
      "expires_at": "2026-05-22T15:10:00Z"
    }
    ```
    > `crypto_amount` — это `gross` (то что видит покупатель на checkout-странице).
    > `net_amount` — то что зачислится мерчанту после оплаты.

#### 2.7 Application: Payment Incoming (internal, secured)
Вызывается из uexapp-backend когда Kafka детектирует подтверждённый платёж на адрес из пула.

- [ ] `POST /internal/payment/incoming`
  - Защищён `X-Internal-Key`
  - Input: `{ address, amount, tx_hash, currency_id, confirmations, network }`
  - Ищет активный `merchant_payments` по `gateway_reference = address` AND `status = MerchantPaymentStatusEnum::PENDING`
  - Если найден:
    - Из `merchant_payments` читаем зафиксированные значения:
      - `gross_expected = merchant_payments.total` (gross, что должен был заплатить покупатель)
      - `fee_amount     = merchant_payments.fee` (зафиксировано при создании)
      - `net_amount     = gross_expected - fee_amount` (то что мерчант должен получить)
    - Рассчитываем фактическое зачисление:
      ```
      received       = input amount (фактически получено от покупателя)
      is_exact       = (received == gross_expected)
      difference     = received - gross_expected          // + overpay, - underpay
      net_credited   = received - fee_amount              // мерчант получает всё за вычетом fee
                                                          // если received < fee_amount → net_credited = 0
      net_credited   = max('0.00000000', net_credited)   // защита от отрицательного
      ```
    - Меняет `merchant_payments.status` → `MerchantPaymentStatusEnum::SUCCESS`
    - **Создаёт запись в `merchant_transactions`** (леджер):
      - `transaction_type_id = MerchantTransactionTypeEnum::PAYMENT_RECEIVED`
      - `transaction_reference_id = merchant_payments.id`
      - `merchant_id`, `currency_id`, `amount = net_credited`, `status = MerchantTransactionStatusEnum::SUCCESS`
      - `metadata`:
        ```json
        {
          "gross_expected":  "101.00000000",
          "gross_received":  "101.00000000",
          "net_amount":      "100.00000000",
          "fee_amount":      "1.00000000",
          "fee_percentage":  "1.00",
          "fee_fixed":       "0.00000000",
          "net_credited":    "100.00000000",
          "difference":      "0.00000000",
          "is_exact":        true,
          "tx_hash":         "0xabc123...",
          "confirmations":   12,
          "network":         "TRC-20",
          "network_fee":     null
        }
        ```
    - Зачисляет `net_credited` на `merchant_balances.amount`
    - DELETE `merchant_address_assignments`
    - DELETE `wallet_histories` (address, merchant user_id)
    - address: status → free
    - Запускает `DispatchMerchantWebhookJob`
    - **Проверяет авто-конвертацию:**
      ```
      $autoConvert = FindAutoConvertByCriteriaHandler->byMerchantId($merchantId)->execute()
      if ($autoConvert && $autoConvert->isEnabled()
          && $autoConvert->getToCurrencyId() !== $currency_id) {
          dispatch(AutoConvertJob(
              merchantId:          $merchantId,
              fromCurrencyId:      $currency_id,
              amount:              $net_credited,
              toCurrencyId:        $autoConvert->getToCurrencyId(),
              referenceTransactionId: $merchantTransaction->getId(),
          ));
      }
      ```
  - Если не найден (адрес неизвестен / платёж уже закрыт) — логируем, игнорируем

**Статусы платежа:**
- `Pending` — ожидает оплаты
- `Success` — деньги получены
- `Expired` — время истекло
- `Blocked` — заблокирован
- `Refund` — возврат

**Webhook payload мерчанту:**
```json
{
  "order_no":       "ORD-777",
  "status":         "Success",
  "gross_expected": "101.00000000",
  "gross_received": "101.00000000",
  "net_credited":   "100.00000000",
  "fee_amount":     "1.00000000",
  "is_exact":       true,
  "difference":     "0.00000000",
  "currency":       "USDT",
  "network":        "TRC-20",
  "tx_hash":        "0xabc123..."
}
```
`net_credited` — сумма зачисленная на баланс мерчанта. `is_exact: false` сигнализирует о недоплате/переплате покупателя.

> **Нужно реализовать в uexapp-backend:**
> Файл: `app/Application/Actions/Webhook/TransactionWebhook/TransactionIncomingWebhookAction.php`
>
> Текущий флоу: `FindWalletHandler` → `AddBalanceHandler` (зачисляет на личный `wallets`)
>
> Нужно добавить проверку **перед `AddBalanceHandler`**:
> - Проверить: адрес транзакции (`transfer->to`) есть в `merchant_payment_addresses` (shared DB)
> - Если да → **пропустить `AddBalanceHandler`** (не зачислять на личный `wallets`)
> - Вместо этого → HTTP POST на `{MERCHANT_PLATFORM_URL}/internal/payment/incoming`
> - `merchant_balances` (бизнес-средства) **не смешиваются** с личными `wallets` (личные средства)
>
> Альтернатива (через Event): создать `AfterTransactionIncomingWebhookEvent` + `MerchantPaymentNotificationListener`
> в `app/Application/Listeners/Webhook/MerchantPaymentNotificationListener.php`
> и зарегистрировать в `EventServiceProvider`. Обсудить с командой какой подход предпочтительнее.

#### 2.8 Job: AutoConvertJob

Запускается асинхронно из `POST /internal/payment/incoming` если `auto_convert_enabled = true`.
Выполняет бухгалтерскую конвертацию баланса мерчанта. Никакого реального крипто-перевода — только пересчёт через курс.

**Флоу:**
```
1. HTTP POST {BACKEND_URL}/api/exchanges/rate  (тот же эндпоинт что в Phase 2.6)
   { from: from_currency_id, to: to_currency_id, amount: net_credited }
   ← { rate_without_commission, convert_without_commission, commission }

   to_amount = convert_without_commission   ← берём без комиссии

2. DB::transaction:
   merchant_balances: amount -= net_credited  WHERE currency_id = from_currency_id
   merchant_balances: amount += to_amount     WHERE currency_id = to_currency_id
                                               (upsert — создать запись если нет)
   CREATE merchant_transactions:
     transaction_type_id        = CONVERSION (7)
     transaction_reference_id   = reference_transaction_id  (исходный PAYMENT_RECEIVED)
     merchant_id, currency_id   = to_currency_id
     amount                     = to_amount
     status                     = Success
     metadata = {
       from_currency_id, from_amount: net_credited,
       to_currency_id,   to_amount,
       rate:              rate_without_commission,
       exchange_commission: commission
     }
```

**Retry / Failure:**
```
On failure:
  - Retry × 3 с экспоненциальным backoff (5s, 30s, 120s)
  - Если все 3 попытки провалились:
    - Log::error(...)
    - MerchantNotificationService->conversionFailed(
        merchantId, merchantEmail,
        fromAmount, fromCurrency,
        toCurrency, attempts: 3
      )
    - from_currency остаётся на merchant_balances (деньги не потеряны)
```

**Application checklist:**
- [ ] `AutoConvertJob` — `final class implements ShouldQueue`
- [ ] Retry: `public int $tries = 3`, `public array $backoff = [5, 30, 120]`
- [ ] Добавить `conversionFailed()` в `MerchantNotificationService` (Phase 7.4):
  - In-system: red
  - Telegram: ✓
  - Email: ✓

| Матрица каналов | In-system | Telegram | Email |
|---|---|---|---|
| Conversion failed | red | ✓ | ✓ |

---

#### 2.9 Job: ExpireMerchantPaymentsJob
- [ ] Scheduled job (каждую минуту) — проверяет истёкшие платежи
  - Находит `merchant_payments` где `expires_at < now` AND `status = MerchantPaymentStatusEnum::PENDING`
  - Меняет статус → `MerchantPaymentStatusEnum::EXPIRED`
  - DELETE `merchant_address_assignments`
  - DELETE `wallet_histories` (address, merchant user_id)
  - address: status → free

#### 2.10 Module: MerchantWebhook (own)

##### Migration: `merchant_webhooks`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint | без FK — isolation rule |
| `name` | string 255 | "Production", "Staging" |
| `url` | string 500 | HTTPS endpoint мерчанта |
| `secret` | string | для HMAC-SHA256 подписи |
| `mode` | ENUM('live','test') | к каким платежам привязан |
| `events` | JSON | массив event types (см. `MerchantWebhookEventEnum`) |
| `status` | ENUM('active','degraded','disabled') | `degraded` — success_rate < 90%; `disabled` — < 50% или вручную |
| `success_rate` | decimal(5,2) default 100.00 | % успешных доставок за последние 100 попыток |
| `is_active` | bool default true | мерчант может вручную включить/выключить |
| `created_at` / `updated_at` | | |

##### Migration: `merchant_webhook_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `webhook_id` | bigint | FK → merchant_webhooks |
| `event_type` | string | из `MerchantWebhookEventEnum` |
| `session_id` | string nullable | `merchant_payments.uuid` |
| `payload` | JSON | отправленный payload |
| `response_code` | int nullable | HTTP-статус ответа |
| `response_body` | text nullable | первые 1000 символов ответа |
| `duration_ms` | int nullable | время ответа в мс |
| `attempt` | int | 1 / 2 / 3 |
| `success` | bool | `response_code` in [200..299] |
| `delivered_at` | timestamp nullable | |
| `created_at` | | |

##### Enum `MerchantWebhookEventEnum`

| Value | Событие |
|---|---|
| `payment.settled` | платёж подтверждён, деньги зачислены |
| `payment.confirming` | платёж обнаружен в сети, ждём подтверждений |
| `payment.failed` | платёж истёк или заблокирован |
| `refund.created` | создан возврат |
| `payout.completed` | вывод выполнен |
| `payout.failed` | вывод отклонён |

**Module checklist:**
- [ ] Migration `merchant_webhooks`, `merchant_webhook_logs`
- [ ] Enum `MerchantWebhookEventEnum`, `MerchantWebhookStatusEnum`
- [ ] Model `MerchantWebhook`, Entity, EntityFactory, Presenter
- [ ] Model `MerchantWebhookLog`, Entity, EntityFactory, Presenter
- [ ] Contract + Repository для обоих
- [ ] Criteria: `WebhookByMerchantIdCriteria`, `WebhookByModeCriteria`, `WebhookByStatusCriteria`, `WebhookByEventTypeCriteria`
- [ ] Criteria: `WebhookLogByWebhookIdCriteria`, `WebhookLogBySuccessCriteria`, `WebhookLogOrderByCreatedAtCriteria`
- [ ] Transfers: `StoreWebhookTransfer`, `UpdateWebhookTransfer`
- [ ] Handlers: `StoreWebhookHandler`, `UpdateWebhookHandler`, `FindWebhookByCriteriaHandler`, `PaginateWebhooksHandler`, `UpdateWebhookSuccessRateHandler`
- [ ] ServiceProvider `MerchantWebhookServiceProvider`

**Application API:**
- [ ] `GET /merchant/webhooks` — список (name, url, mode, events[], status, success_rate, last delivery)
- [ ] `POST /merchant/webhooks` — создать `{ name, url, mode, events[], secret? }`
- [ ] `PUT /merchant/webhooks/{id}` — обновить url/name/events
- [ ] `PATCH /merchant/webhooks/{id}/toggle` — включить/выключить `is_active`
- [ ] `DELETE /merchant/webhooks/{id}` — удалить
- [ ] `POST /merchant/webhooks/{id}/test` — отправить тестовый ping-payload
- [ ] `GET /merchant/webhooks/{id}/deliveries` — лог доставки (последние 100 попыток)
  - Query: `filter[success]=true|false`, `per_page`
  - Response item: `{ id, event_type, session_id, response_code, duration_ms, attempt, success, created_at }`

**Response (webhook list item):**
```json
{
  "id": 1,
  "name": "Production",
  "url": "https://shop.example.com/webhooks/uex",
  "mode": "live",
  "events": ["payment.settled", "payout.completed"],
  "status": "active",
  "success_rate": "98.40",
  "is_active": true,
  "last_delivery_at": "2026-05-21T09:42:00Z",
  "created_at": "2026-05-01T10:00:00Z"
}
```

> `status = "degraded"` — success_rate упал ниже 90%, доставки продолжаются. Мерчант видит предупреждение.
> `status = "disabled"` — success_rate ниже 50% или мерчант отключил вручную. Доставки пропускаются.

#### 2.10b Webhook Dispatcher

- [ ] Job: `DispatchMerchantWebhookJob` — отправляет POST на URL мерчанта
- [ ] Выбор получателей: `WebhookByMerchantIdCriteria + WebhookByModeCriteria(MerchantWebhookModeEnum) + WebhookByEventTypeCriteria(MerchantWebhookEventEnum) + WebhookByStatusCriteria([MerchantWebhookStatusEnum::ACTIVE, MerchantWebhookStatusEnum::DEGRADED])` — webhooks со статусом `DISABLED` пропускаются
- [ ] Payload:
  ```json
  {
    "event": "payment.settled",
    "livemode": true,
    "order_no": "ORD-777",
    "status": "Success",
    "gross_expected": "101.000000",
    "gross_received": "101.000000",
    "net_credited":   "100.000000",
    "fee_amount":     "1.000000",
    "difference":     "0.000000",
    "is_exact": true,
    "currency": "USDT",
    "network": "TRC-20",
    "tx_hash": "0xabc123...",
    "created_at": "2026-05-21T09:42:00Z"
  }
  ```
  > `livemode: false` для платежей из test-режима (Phase 4c)
- [ ] HMAC-SHA256 подпись в header `X-UEX-Signature: sha256=<hex>`
- [ ] Retry logic: 3 попытки с backoff (5s, 30s, 120s)
- [ ] Каждая попытка логируется в `merchant_webhook_logs`
- [ ] После каждой доставки (успех или неудача) → `UpdateWebhookSuccessRateHandler`:
  ```
  COUNT success=true / COUNT(*) WHERE webhook_id — последние 100 записей в webhook_logs
  → обновить merchant_webhooks.success_rate
  → если < 90% → status = MerchantWebhookStatusEnum::DEGRADED
  → если < 50% → status = MerchantWebhookStatusEnum::DISABLED
  → если >= 90% && status=DEGRADED → status = MerchantWebhookStatusEnum::ACTIVE  (auto-recover)
  ```
- [ ] После 3+ неудачных попыток → `MerchantNotificationService->webhookFailed()`

---

### Phase 3 — Financial

**Goal:** Мерчант видит транзакции (унифицированная лента: зачисления + выводы), баланс, инициирует вывод через прокси в uexapp-backend.

#### 3.1 Application: Transactions (использует MerchantTransaction из Phase 2.5b)

Единый список движений по балансу — оба типа (`PAYMENT_RECEIVED` и `WITHDRAWAL`) в одной ленте.

##### Фильтры и вкладки

Вкладки в UI соответствуют фильтру по статусу:

| Вкладка | `filter[status]` |
|---|---|
| All | — (нет фильтра) |
| Settled | `Success` |
| Pending | `Pending` |
| Failed | `Failed` |
| Refunded | `Refunded` |

Остальные фильтры (query params):
- `filter[type]` — `payment` / `withdrawal`
- `filter[currency_id]` — comma-separated int
- `filter[network]` — строка сети (TRC-20, ERC-20, Bitcoin, …)
- `filter[date_from]` / `filter[date_to]` — YYYY-MM-DD
- `filter[search]` — строка: ищет по `order_no`, `uuid`, `tx_hash` (из metadata), customer email
- `per_page` — default 12

##### Критерии (новые, добавить в MerchantTransaction модуль)

| Criteria | Условие |
|---|---|
| `MerchantTransactionByStatusCriteria` | `WHERE status IN (...)` |
| `MerchantTransactionByTypeCriteria` | `WHERE transaction_type_id = ?` |
| `MerchantTransactionByCurrencyIdCriteria` | `WHERE currency_id IN (...)` |
| `MerchantTransactionByDateRangeCriteria` | `WHERE created_at BETWEEN ? AND ?` |
| `MerchantTransactionBySearchCriteria` | `WHERE order_no LIKE ? OR uuid LIKE ? OR JSON_EXTRACT(metadata, '$.tx_hash') LIKE ?` |

CriteriaTrait: добавить методы `byStatus`, `byType`, `byCurrencyId`, `byDateRange`, `bySearch`, `byMerchantId`, `orderByCreatedAt`.

##### Data Enrichment (паттерн по аналогии с GetUserTransactionsWithPaginateAction из uexapp-backend)

```
PaginateMerchantTransactionHandler (с критериями) → LengthAwarePaginator<MerchantTransactionItemEntity>
  ↓
Собрать уникальные currency_id → batch-fetch из currencies (shared)
  ↓
Собрать уникальные transaction_reference_id (type=PAYMENT_RECEIVED) → batch-fetch merchant_payments
  ↓
tap(paginator) → для каждого item:
  + currency: { symbol, code, name, logo, network }
  + order_no, customer (из merchant_payments)
  + fee_amount (из metadata)
```

##### Application checklist

- [ ] `PaginateMerchantTransactionsRequestTransfer` — все фильтры + per_page
- [ ] `PaginateMerchantTransactionsRequest` — rules() + getTransfer()
- [ ] `PaginateMerchantTransactionsAction` — applyFilters() → handler → enrichment через batch-fetch
- [ ] `PaginateMerchantTransactionsController`
- [ ] `GET /merchant/transactions`

##### Response (list item)

```json
{
  "id": 42,
  "order_no": "#A-1042",
  "internal_id": "tx_3kj21x",
  "date": "21 May, 09:42",
  "customer": "lena@dvb.de",
  "currency": { "symbol": "USDT", "code": "usdt", "name": "Tether", "logo": "..." },
  "network": "TRC-20",
  "amount": "249.21000000",
  "amount_usd": "249.21",
  "fee_amount": "1.25",
  "fee_usd": "1.25",
  "status": "Success",
  "status_label": "Settled",
  "transaction_type_id": 1,
  "created_at": "2026-05-21T09:42:00Z"
}
```

##### Пагинация

Offset-based: `?page=N&per_page=12`. Response meta содержит `total`, `current_page`, `per_page`, `from`, `to` — фронт отображает "Showing 1–12 of 1,284" и кнопки Prev/Next.

#### 3.2 Module: MerchantBalance (own)
- [ ] Migration: `merchant_balances`
  - merchant_id, currency_id, amount, frozen_amount
  - `amount` — доступный баланс (пополняется при `merchant_transactions` type=PAYMENT_RECEIVED status=Success)
  - `frozen_amount` — заморожено под активные `merchant_transactions` type=WITHDRAWAL в статусах Pending/Approved
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `BalanceByMerchantIdCriteria`, `BalanceByCurrencyIdCriteria`
- [ ] Handlers: `FindMerchantBalanceByCriteriaHandler`, `UpdateMerchantBalanceHandler`
- [ ] `GET /merchant/balance` — балансы по всем валютам (amount, frozen_amount, available = amount - frozen_amount)

#### 3.3 Module: MerchantWithdrawalDestination (own)

Сохранённые крипто-адреса для вывода. Мерчант добавляет адреса один раз, выбирает из списка при каждом выводе.

**Migration: `merchant_withdrawal_destinations`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint | без FK — isolation rule |
| `label` | string 255 | "Казначейский кошелёк", "Холодильное хранилище" |
| `network` | string | ERC-20, Bitcoin, TRC-20, BEP-20, … |
| `address` | string | крипто-адрес |
| `destination_tag` | string nullable | для XRP, XLM и т.д. |
| `is_default` | bool default false | адрес по умолчанию в форме вывода |
| `status` | string | `active` / `whitelisted` |
| `created_at` / `updated_at` | | |

> Только крипто-адреса. Банковские реквизиты (SEPA, SWIFT) не поддерживаются.

**Module checklist:**
- [ ] Migration `merchant_withdrawal_destinations`
- [ ] Enum `MerchantWithdrawalDestinationStatusEnum` — `active`, `whitelisted`
- [ ] Model `MerchantWithdrawalDestination`
- [ ] Entity `MerchantWithdrawalDestinationItemEntity`
- [ ] EntityFactory `MerchantWithdrawalDestinationItemEntityFactory`
- [ ] Presenter `MerchantWithdrawalDestinationEntityPresenter`
- [ ] Contract `MerchantWithdrawalDestinationRepositoryContract`
- [ ] Repository `MerchantWithdrawalDestinationRepository`
- [ ] Criteria: `WithdrawalDestinationByMerchantIdCriteria`, `WithdrawalDestinationByIdCriteria`, `WithdrawalDestinationByStatusCriteria`, `WithdrawalDestinationByNetworkCriteria`
- [ ] CriteriaTrait `WithdrawalDestinationCriteriaTrait` (`byMerchantId`, `byId`, `byStatus`, `byNetwork`)
- [ ] Transfers: `StoreWithdrawalDestinationTransfer`, `UpdateWithdrawalDestinationTransfer`
- [ ] Handlers: `StoreWithdrawalDestinationHandler`, `UpdateWithdrawalDestinationHandler`, `FindWithdrawalDestinationByCriteriaHandler`, `PaginateWithdrawalDestinationsHandler`
- [ ] ServiceProvider `WithdrawalDestinationServiceProvider`

**Application API:**
- [ ] `GET /merchant/withdrawal-destinations` — список адресов мерчанта
- [ ] `POST /merchant/withdrawal-destinations` — добавить новый адрес
- [ ] `PUT /merchant/withdrawal-destinations/{id}` — обновить label / destination_tag
- [ ] `DELETE /merchant/withdrawal-destinations/{id}` — удалить
- [ ] `PATCH /merchant/withdrawal-destinations/{id}/set-default` — сделать адресом по умолчанию

**Response (list item):**
```json
{
  "id": 1,
  "label": "Казначейский кошелёк",
  "network": "ERC-20",
  "address": "0x8a31...AB29",
  "destination_tag": null,
  "is_default": true,
  "status": "active"
}
```

---

#### 3.4 Application: Withdrawal Preview

Dry-run расчёт перед подтверждением вывода. Никаких записей в БД не создаёт.

- [ ] `POST /merchant/withdrawals/preview`
  - Input:
    ```json
    {
      "currency_id": 10,
      "amount": "10000.00000000",
      "destination_id": 1
    }
    ```
  - Шаги:
    1. Загрузить `merchant_withdrawal_destination` по `destination_id`
    2. Получить fee: `FindFeeLimitByCriteriaHandler->byCurrencyId()->byMetadataType(FeeLimitMetadataTypeEnum::MERCHANT_WITHDRAWAL)->onlyActive()->execute()`
    3. `fee_amount = feeEntity->calculateFee(amount)` (bcmath)
    4. `amount_to_receive = amount - fee_amount`
    5. `amount_usd = amount_to_receive * currencies.rate` (текущий курс из shared `currencies` таблицы)
  - Returns:
    ```json
    {
      "amount_to_send":    "10000.00000000",
      "currency":          "USDT",
      "network":           "ERC-20",
      "destination":       { "id": 1, "label": "Казначейский кошелёк", "address": "0x8a31...AB29" },
      "fee_amount":        "2.10000000",
      "fee_percentage":    "0.00",
      "fee_fixed":         "2.10",
      "amount_to_receive": "9997.90000000",
      "amount_usd":        "9997.90",
      "estimated_arrival": "~30 seconds"
    }
    ```

---

#### 3.5 Application: Withdrawal Proxy (в uex-merchant-platform)

Мерчант подтверждает вывод через 2FA. Merchant platform валидирует баланс и проксирует в uexapp-backend.

- [ ] `GET /merchant/withdrawals` — список выводов (фильтр через `merchant_transactions` type=WITHDRAWAL)
  - Query params: `filter[method]=manual|auto`, `filter[destination_type]=blockchain`, `per_page`
  - Вкладки в UI: **Все** / **В блокчейне** (банковские выводы не поддерживаются)
- [ ] `GET /merchant/withdrawals/{id}` — детали конкретного вывода
- [ ] `POST /merchant/withdrawals` — создать заявку:
  - Input:
    ```json
    {
      "currency_id": 10,
      "amount": "10000.00000000",
      "destination_id": 1,
      "one_time_password": "999999"
    }
    ```
  - Шаги (uex-merchant-platform):
    1. Валидирует merchant auth
    2. Загружает `merchant_withdrawal_destination` по `destination_id`, проверяет принадлежность мерчанту
    3. Повторяет расчёт preview: `fee_amount`, `amount_to_receive`
    4. Проверяет `amount + fee_amount ≤ available_balance` на `merchant_balances`
    5. HTTP POST на `{BACKEND_URL}/api/internal/merchant/withdrawal/crypto/create` с `X-Internal-Key`:
       ```json
       {
         "merchant_id": 1,
         "user_id": 42,
         "currency_id": 10,
         "amount": "10000.00000000",
         "recipient": "0x8a31...AB29",
         "destination_tag": null,
         "network": "ERC-20",
         "one_time_password": "999999",
         "method": "manual",
         "destination_id": 1,
         "fee_amount": "2.10000000",
         "amount_usd": "9997.90"
       }
       ```
    6. Возвращает мерчанту: `{ transaction_id, status: MerchantTransactionStatusEnum::PENDING->value, expected_completion: "manual_approval" }`

**Response (list item — история выплат):**
```json
{
  "id": 42,
  "date": "2026-05-18T14:22:00Z",
  "method": "manual",
  "destination": {
    "label": "Казначейский кошелёк",
    "address": "0x8a31...AB29",
    "network": "ERC-20"
  },
  "amount":           "5000.00000000",
  "currency":         "USDC",
  "fee_amount":       "1.05000000",
  "amount_to_receive":"4998.95000000",
  "amount_usd":       "4998.95",
  "status":           "Completed",
  "tx_hash":          "0xabc123..."
}
```

#### 3.6 Backend Endpoint (в uexapp-backend) — Merchant Withdrawal Create

> **Нужно реализовать в uexapp-backend:**
>
> Файл: `app/Application/Http/Controllers/Internal/Merchant/CreateMerchantWithdrawalController.php`
> Защищён `InternalKeyMiddleware` (header `X-Internal-Key`).
>
> - [ ] `POST /api/internal/merchant/withdrawal/crypto/create`
>   - Input: `{ merchant_id, user_id, currency_id, amount, recipient, destination_tag?, network, one_time_password, method, destination_id, fee_amount, amount_usd }`
>   - Валидация 2FA через существующий механизм бекенда
>   - **В одной транзакции БД (DB::transaction):**
>     - Уменьшает `merchant_balances.amount` на `(amount + fee_amount)`, увеличивает `frozen_amount` на ту же сумму
>     - Создаёт запись в `merchant_transactions`:
>       - `transaction_type_id = MerchantTransactionTypeEnum::WITHDRAWAL`
>       - `transaction_reference_id = NULL`
>       - `merchant_id`, `currency_id`, `amount`, `status = MerchantTransactionStatusEnum::PENDING`
>       - `metadata`: `{ recipient, destination_tag, network, fee_amount, amount_usd, method, destination_id, initiated_by_user_id: user_id }`
>   - Return: `{ transaction_id, uuid, status: MerchantTransactionStatusEnum::PENDING->value }`

#### 3.7 Backend Admin Page — Merchant Withdrawals (в uexapp-backend)

> **Нужно реализовать в uexapp-backend (Admin слой):**
>
> Отдельная страница в админке для управления withdrawal-транзакциями мерчантов.
>
> - [ ] Контроллер: `app/Admin/Http/Controllers/Merchant/MerchantTransactionController.php`
>   - `GET /admin/merchant-transactions` — листинг (фильтр по merchant, type, status, дате)
>     - Колонки: merchant, currency, amount, type, status, recipient (из metadata), created_at
>   - `GET /admin/merchant-transactions/{id}` — детали
> - [ ] Approve endpoint:
>   - `POST /admin/merchant-transactions/{id}/approve` — для type=MerchantTransactionTypeEnum::WITHDRAWAL, status=MerchantTransactionStatusEnum::PENDING
>     - Внутри: использует существующую логику `CreateExternalWithdrawalCryptoAction` / `CreateInternalWithdrawalCryptoAction` (см. `app/Application/Actions/Dashboard/Withdrawals/Crypto/`) — но **от имени мерчанта**, с уже зарезервированной суммой из `merchant_balances.frozen_amount`
>     - В случае успеха крипто-перевода:
>       - Обновляет `merchant_transactions`: `status = MerchantTransactionStatusEnum::COMPLETED`, metadata.tx_hash, metadata.admin_id, processed_at
>       - Списывает `frozen_amount` с баланса мерчанта (деньги физически ушли)
>       - Триггерит webhook мерчанту (через очередь, новый event `MerchantWebhookEventEnum::PAYOUT_COMPLETED`)
>     - В случае ошибки исполнения:
>       - `merchant_transactions.status = Failed`, metadata.error
>       - Возвращает `frozen_amount` обратно в `amount` (деньги не ушли)
> - [ ] Reject endpoint:
>   - `POST /admin/merchant-transactions/{id}/reject` — admin отклоняет заявку
>     - `merchant_transactions.status = MerchantTransactionStatusEnum::REJECTED`, metadata.admin_id, metadata.rejected_reason, processed_at
>     - Возвращает `frozen_amount` → `amount` (заявка не исполнена)
>     - Триггерит webhook мерчанту (event `MerchantWebhookEventEnum::PAYOUT_FAILED`)
>
> **Важно:** вывод **не** проходит через личный `wallets` пользователя. Списание идёт прямо из `merchant_balances` → крипто-гейтвей. Личные средства мерчанта и бизнес-средства не смешиваются.

#### 3.8 Application: Transaction Detail (side panel)

**Goal:** Полные данные одной транзакции для бокового блока (mer-1.2.png).

##### Отображаемые поля

| Поле | Источник |
|---|---|
| Order / Internal ID | `merchant_transactions.uuid` (внутренний ID) + `merchant_payments.order_no` |
| Tx hash | `metadata.tx_hash` (с кнопкой copy + block explorer URL) |
| Date | `merchant_transactions.created_at` |
| Customer | `merchant_payments` → email или сокращённый адрес кошелька |
| Confirmations | `metadata.confirmations` / `metadata.required_confirmations` |
| Asset · Network | currency (batch-fetch) + network из `metadata.network` |
| Gross | `metadata.received_amount` (для PAYMENT_RECEIVED) или `amount` |
| Network fee | `metadata.network_fee` (null → отображается "—") |
| UEX fee (N%) | `metadata.fee_amount` + `metadata.fee_percentage` |
| Net to wallet | `metadata.net_amount` = received_amount − fee_amount |

##### Data Enrichment

```
FindMerchantTransactionByCriteriaHandler->byId($id)->byMerchantId($merchantId)->execute()
  ↓  MerchantTransactionItemEntity
  ↓
Если type = PAYMENT_RECEIVED:
  FindMerchantPaymentByCriteriaHandler->byId($entity->getTransactionReferenceId())->execute()
  → order_no, customer_identifier, address, network
  ↓
Currency batch-fetch (shared) → symbol, name, logo, decimals
  ↓
Block explorer URL: config('chains.' . $network . '.explorer') . '/tx/' . $tx_hash
```

##### Application checklist

- [ ] `GetMerchantTransactionAction` — find + enrich (currency + payment session)
- [ ] `GetMerchantTransactionController`
- [ ] `GET /merchant/transactions/{id}`

##### Response (detail)

```json
{
  "id": 42,
  "order_no": "#A-1042",
  "internal_id": "tx_3kj21x",
  "status": "Success",
  "status_label": "Settled",
  "transaction_type_id": 1,
  "date": "2026-05-21T09:42:00Z",
  "currency": { "symbol": "USDT", "code": "usdt", "name": "Tether", "logo": "...", "decimals": 6 },
  "network": "TRC-20",
  "amount": "249.21000000",
  "tx_hash": "0x71c7...j21x",
  "block_explorer_url": "https://tronscan.org/#/transaction/0x71c7...",
  "customer": "lena@dvb.de",
  "confirmations": 32,
  "required_confirmations": 12,
  "gross_received": "101.00000000",
  "gross_expected": "101.00000000",
  "network_fee": null,
  "fee_percentage": "1.00",
  "fee_amount": "1.00000000",
  "fee_amount_usd": "1.00",
  "net_credited": "100.00000000",
  "metadata": { ... }
}
```

Для `type = WITHDRAWAL` — дополнительно: `recipient`, `destination_tag`, `processed_at`, `rejected_reason`.

---

#### 3.9 Module: MerchantFeeRate — Fee System

**Goal:** Per-merchant fee rates с дефолтным процентом и per-currency overrides. Аналог savings rate системы. Хранить начисленную fee в `merchant_transactions.metadata`.

##### Разделение источников комиссий

| Операция | Источник fee | Кто применяет |
|---|---|---|
| Входящий платёж (`PAYMENT_RECEIVED`) | `merchant_fee_rates` per-merchant | uex-merchant-platform при создании платёжной ссылки (Phase 2.6) |
| Вывод (`WITHDRAWAL`) | `fees_limits` WHERE `metadata->type = 'merchant_withdrawal'` | uexapp-backend при approve вывода (Phase 3.4 / 3.7) |

Fee всегда платит **мерчант** — покупатель всегда видит ровную сумму.

> **Нужно добавить в uexapp-backend** новый тип в таблицу `fees_limits`:
>
> | Тип | Назначение |
> |---|---|
> | `metadata->type = FeeLimitMetadataTypeEnum::MERCHANT_WITHDRAWAL` (`'merchant_withdrawal'`) | Комиссия платформы при выводе средств мерчанта |

##### Module: MerchantFeeRate (own — таблица принадлежит merchant-platform)

**Migration: `merchant_fee_rates`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint | без FK — isolation rule |
| `currency_id` | int nullable | NULL = глобальная ставка для мерчанта; id = per-currency override |
| `percent` | decimal(5,2) | 1.00, 0.50, 1.50 |
| `created_at` / `updated_at` | | |

Уникальный индекс: `(merchant_id, currency_id)`.

**Module checklist:**
- [ ] Migration `merchant_fee_rates`
- [ ] Model `MerchantFeeRate`
- [ ] Entity `MerchantFeeRateItemEntity`
  - Метод `calculateFee(string $amount): string` — `bcmul($amount, bcdiv($percent, '100', 8), 8)`
  - Метод `calculateNetAmount(string $grossAmount): string` — `bcsub($grossAmount, $this->calculateFee($grossAmount), 8)`
- [ ] EntityFactory `MerchantFeeRateItemEntityFactory`
- [ ] Presenter `MerchantFeeRateEntityPresenter`
- [ ] Contract `MerchantFeeRateRepositoryContract`
  - `findByCriteria(array $criteria): ?MerchantFeeRateItemEntity`
  - `upsert(int $merchantId, ?int $currencyId, string $percent): MerchantFeeRateItemEntity`
- [ ] Repository `MerchantFeeRateRepository`
- [ ] Criteria: `FeeRateByMerchantIdCriteria`, `FeeRateByCurrencyIdCriteria`, `FeeRateGlobalOnlyCriteria` (`WHERE currency_id IS NULL`)
- [ ] CriteriaTrait `MerchantFeeRateCriteriaTrait` (`byMerchantId`, `byCurrencyId`, `globalOnly`)
- [ ] Handler: `ResolveMerchantFeeRateHandler` — цепочка разрешения ставки (см. ниже)
- [ ] Handler: `StoreMerchantFeeRateHandler`, `UpdateMerchantFeeRateHandler`, `FindMerchantFeeRateByCriteriaHandler`
- [ ] ServiceProvider `MerchantFeeRateServiceProvider`

**`ResolveMerchantFeeRateHandler::execute(int $merchantId, int $currencyId): string`**

```
1. merchant_fee_rates WHERE merchant_id = $merchantId AND currency_id = $currencyId
   → нашли → return percent

2. merchant_fee_rates WHERE merchant_id = $merchantId AND currency_id IS NULL
   → нашли → return percent  (глобальная ставка мерчанта)

3. config('merchant-config.default_fee_percent')   ← hardcoded fallback (e.g. "1.00")
```

**`config/merchant-config.php`:**
```php
return [
    'default_fee_percent' => env('MERCHANT_DEFAULT_FEE_PERCENT', '1.00'),
];
```

##### Admin Panel: управление ставками мерчанта (в uexapp-backend)

> **Реализовать в `uexapp-backend/app/Admin`** — аналог `SavingRatesController`:

- [ ] `GET /admin/merchants/{id}/fee-rates` — текущие ставки мерчанта (глобальная + per-currency список)
- [ ] `POST /admin/merchants/{id}/fee-rates` — установить ставку:
  - `{ currency_id: null, percent: "0.75" }` → глобальная ставка
  - `{ currency_id: 3, percent: "0.50" }` → per-currency override
- [ ] `DELETE /admin/merchants/{id}/fee-rates/{rateId}` — удалить per-currency override (глобальную нельзя удалить)

**Admin actions:**
- [ ] `MerchantFeeRateViewAction` — загружает глобальную ставку + per-currency список + конфиг дефолт
- [ ] `MerchantFeeRateUpsertAction` — создаёт или обновляет запись (upsert по merchant_id + currency_id)
- [ ] `MerchantFeeRateDeleteAction` — удаляет per-currency override

##### Fee Application: при создании платёжной ссылки (PAYMENT_RECEIVED)

Fee рассчитывается **один раз при создании** `merchant_payments` (Phase 2.6) и фиксируется. При зачислении (Phase 2.7) данные fee берутся из уже сохранённых полей.

```
[Phase 2.6 — GeneratePaymentUrlAction]

net_crypto_amount = результат конвертации курса
  ↓
$feePercent = ResolveMerchantFeeRateHandler->execute($merchantId, $cryptoCurrencyId)
  // '1.00' из per-currency, или global, или config
  ↓
fee_amount   = bcmul($netCryptoAmount, bcdiv($feePercent, '100', 8), 8)
gross_amount = $netCryptoAmount   // покупатель платит net; fee снимается при зачислении
  ↓
Сохранить в merchant_payments:
  total    = gross_amount   (= net, покупатель платит это)
  fee      = fee_amount     (зафиксирована)
  metadata = { net_amount, fee_amount, fee_percentage, gross_amount }

──────────────────────────────────────────────────────

[Phase 2.7 — IncomingPaymentAction]

Читаем из merchant_payments.fee → fee_amount (уже зафиксирована)
received = input amount от uexapp-backend
net_credited = bcsub(received, fee_amount, 8)    // мерчант получает received - fee
net_credited = max('0.00000000', net_credited)   // защита от отрицательного
  ↓
Зачислить net_credited на merchant_balances
  ↓
Записать в merchant_transactions.metadata:
  {
    "gross_expected":  merchant_payments.total,
    "gross_received":  received,
    "net_amount":      merchant_payments.metadata.net_amount,
    "fee_amount":      merchant_payments.fee,
    "fee_percentage":  merchant_payments.metadata.fee_percentage,
    "net_credited":    net_credited,
    "difference":      bcsub(received, merchant_payments.total, 8),
    "is_exact":        difference == '0.00000000',
    "tx_hash":         ...,
    "confirmations":   ...,
    "network_fee":     null
  }
```

##### Module: FeeLimit (читает shared таблицу `fees_limits`, read-only)

Используется **только для расчёта fee при выводе** (Phase 3.4 withdrawal preview). Таблица принадлежит uexapp-backend, без миграции.

- [ ] Model `FeeLimit` → таблица `fees_limits` (`$fillable = []`)
- [ ] Entity `FeeLimitItemEntity`
  - `calculateFee(string $amount): string` — `bcadd(bcmul($amount, bcdiv($charge_percentage, '100', 8), 8), $charge_fixed, 8)`
- [ ] EntityFactory, Presenter
- [ ] Contract `FeeLimitRepositoryContract` → `findByCriteria(array $criteria): ?FeeLimitItemEntity`
- [ ] Repository `FeeLimitRepository`
- [ ] Criteria: `FeeLimitByCurrencyIdCriteria`, `FeeLimitByMetadataTypeCriteria`, `FeeLimitByStatusCriteria`
- [ ] CriteriaTrait `FeeLimitCriteriaTrait` (`byCurrencyId`, `byMetadataType`, `onlyActive`)
- [ ] Handler: `FindFeeLimitByCriteriaHandler`
- [ ] ServiceProvider `FeeLimitServiceProvider`

##### Fee Application: при выводе (WITHDRAWAL)

> Применяется в **uexapp-backend** при approve вывода (Phase 3.4):
> - `FindFeeLimitByCriteriaHandler->byCurrencyId($currencyId)->byMetadataType(FeeLimitMetadataTypeEnum::MERCHANT_WITHDRAWAL)->onlyActive()->execute()`
> - `fee_amount = $feeEntity->calculateFee($amount)`
> - Фактическое списание = `amount + fee_amount` из `merchant_balances.frozen_amount`
> - В `merchant_transactions.metadata`: добавить `fee_amount`, `fee_percentage`, `fee_fixed`, `network_fee` (реальная сетевая комиссия после исполнения)

##### Отображение в транзакции

После записи в metadata — данные доступны в `GET /merchant/transactions/{id}` (Phase 3.9) без дополнительных запросов:
- `gross_received` → "Gross: $101.00" (фактически получено от покупателя)
- `fee_amount` + `fee_percentage` → "UEX fee (1%): $1.00"
- `network_fee` → "Network fee: —" (null) или реальное значение
- `net_credited` → "Net to wallet: $100.00" ← именно эта сумма зачислена на баланс

---

#### 3.10 Module: MerchantAutoSettlement (own)

**Goal:** Правило автоматического вывода — срабатывает по расписанию и/или при превышении порога баланса.

**Migration: `merchant_auto_settlement_rules`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint unique | один мерчант — одно правило |
| `is_active` | bool default false | глобальный toggle ON/OFF |
| `is_paused` | bool default false | временная пауза без удаления правила |
| `trigger_type` | string | `schedule` / `threshold` / `both` |
| `schedule_time` | string nullable | "17:00" UTC (для schedule/both) |
| `balance_threshold_usd` | decimal nullable | 10000.00 (для threshold/both) |
| `currency_id` | int | из какой валюты выводить (или ALL) |
| `destination_id` | bigint | FK → merchant_withdrawal_destinations |
| `created_at` / `updated_at` | | |

**Module checklist:**
- [ ] Migration `merchant_auto_settlement_rules`
- [ ] Enum `AutoSettlementTriggerEnum` — `schedule`, `threshold`, `both`
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `AutoSettlementByMerchantIdCriteria`, `AutoSettlementActiveOnlyCriteria`
- [ ] Transfers: `StoreAutoSettlementTransfer`, `UpdateAutoSettlementTransfer`
- [ ] Handlers: `StoreAutoSettlementHandler`, `UpdateAutoSettlementHandler`, `FindAutoSettlementByCriteriaHandler`
- [ ] ServiceProvider `AutoSettlementServiceProvider`

**Application API:**
- [ ] `GET /merchant/auto-settlement` — получить текущее правило мерчанта
- [ ] `POST /merchant/auto-settlement` — создать правило
- [ ] `PUT /merchant/auto-settlement` — обновить правило
- [ ] `PATCH /merchant/auto-settlement/toggle` — включить / выключить (`is_active`)
- [ ] `PATCH /merchant/auto-settlement/pause` — пауза / продолжить (`is_paused`)

**Response:**
```json
{
  "is_active": true,
  "is_paused": false,
  "trigger_type": "both",
  "schedule_time": "17:00",
  "balance_threshold_usd": "10000.00",
  "currency_id": 10,
  "destination": {
    "id": 1,
    "label": "Казначейский кошелёк",
    "address": "0x8a31...AB29",
    "network": "ERC-20"
  }
}
```

**Job: `ProcessAutoSettlementJob`** — запускается по крону каждую минуту:
```
Найти все правила WHERE is_active=true AND is_paused=false
  ↓
Для каждого правила:
  Проверить триггер:
    trigger_type=schedule  → сравнить текущее UTC-время с schedule_time (с точностью до минуты)
    trigger_type=threshold → SUM(merchant_balances.amount WHERE currency_id) * currencies.rate >= balance_threshold_usd
    trigger_type=both      → любое из условий
  ↓
Если триггер сработал:
  1. Получить available_balance по currency_id
  2. amount = available_balance (вывести весь доступный баланс)
  3. Повторить логику preview (fee_amount, amount_to_receive, amount_usd)
  4. Проверить amount > 0 и amount > fee_amount (иначе пропустить)
  5. HTTP POST /api/internal/merchant/withdrawal/crypto/create
     + method = 'auto', rule_id в metadata
  6. Записать в merchant_transactions.metadata: { method: 'auto', rule_id, trigger: 'schedule'|'threshold' }
```

> Автовывод не требует 2FA (`one_time_password = null`) — правило уже подтверждено мерчантом
> при его создании/активации. uexapp-backend должен пропускать 2FA-проверку при `method=auto`.

---

### Phase 4 — Settings

**Goal:** Настройки мерчанта, управление API ключами.

#### 4.1 Application: API Credentials Management

Использует `MerchantApp` модуль из Phase 1.4. Расширяет shared-таблицу `merchant_apps` новыми колонками через migration.

##### Расширение таблицы `merchant_apps` (ALTER TABLE)

> Таблица принадлежит uexapp-backend, добавляем свои колонки migration-ом:

```sql
ALTER TABLE merchant_apps
  ADD COLUMN name                varchar(255)       NOT NULL DEFAULT '' AFTER merchant_id,
  ADD COLUMN mode                ENUM('live','test') NOT NULL DEFAULT 'live' AFTER client_secret,
  ADD COLUMN permissions         JSON               NOT NULL DEFAULT '["payments"]' AFTER mode,
  ADD COLUMN rate_limit_per_minute int              NULL AFTER permissions,
  ADD COLUMN status              ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER rate_limit_per_minute,
  ADD COLUMN last_used_at        timestamp          NULL AFTER status;
```

> `created_by_user_id` — не нужно. Ключи создаёт сам мерчант через кабинет.
> `rate_limit_per_minute = NULL` → применяется глобальный default (60/min).

##### Permissions (scopes)

| scope | что разрешено |
|---|---|
| `payments` | создавать платёжные сессии, проверять статус |
| `reports` | читать транзакции, балансы — read-only, без создания платежей |
| `payouts` | инициировать вывод средств (отдельный scope — высокий риск) |

Большинство ключей: `["payments"]`. Репортинг: `["reports"]`. Автоматизация с выводом: `["payments", "payouts"]`.

##### Middleware

- **`ApiKeyPermissionMiddleware`** — проверяет `$request->apiApp->permissions` против нужного scope на роуте → 403 если нет.
- **`ModeResolverMiddleware`** — читает `$request->apiApp->mode` → `app()->instance('request.mode', $mode)`. Доступно во всей цепочке (Action, Job).
- **`ApiRateLimitMiddleware`** — Redis throttle: `RateLimiter::attempt("throttle:app:{$appId}:{$group}", $limit)` → 429 с `Retry-After`.
- **`IpWhitelistMiddleware`** — если у app есть строки в `merchant_ip_whitelist` → проверяет IP клиента по CIDR. Пустой список = allow all.

Группы rate limit:

| group | default |
|---|---|
| `payments.create` | 60/min |
| `status.check` | 300/min |
| `reports` | 120/min |

##### Module: MerchantIpWhitelist (own)

- [ ] Migration `merchant_ip_whitelist`
  - `id`, `merchant_app_id` int, `cidr` varchar (e.g. `"192.168.1.1/32"`, `"10.0.0.0/8"`), `label` varchar 255 nullable, `created_at`
  - Index: `(merchant_app_id)`
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `IpWhitelistByAppIdCriteria`
- [ ] Handlers: `StoreIpWhitelistHandler`, `DeleteIpWhitelistHandler`, `FindIpWhitelistByCriteriaHandler`
- [ ] `IpWhitelistMiddleware` — загружает список по app_id, проверяет CIDR через `ip2long()` + bitmask
- [ ] ServiceProvider `IpWhitelistServiceProvider`

##### Application API

**API Keys:**
- [ ] `GET /merchant/api-keys` — список (name, mode, permissions, status, last_used_at)
- [ ] `POST /merchant/api-keys` — создать:
  - Input: `{ name, mode: "live"|"test", permissions: ["payments"] }`
  - Генерирует `client_id` + `client_secret`
  - `client_secret` возвращается **только один раз** при создании
  - Return: `{ id, name, mode, client_id, client_secret, permissions, created_at }`
- [ ] `PUT /merchant/api-keys/{id}` — обновить name и/или permissions
- [ ] `POST /merchant/api-keys/{id}/rotate` — сгенерировать новый `client_secret`:
  - Старый secret немедленно инвалидируется
  - Return: `{ client_secret }` — показывается один раз
- [ ] `DELETE /merchant/api-keys/{id}` — удалить (немедленная инвалидация)

**IP Whitelist:**
- [ ] `GET /merchant/api-keys/{id}/ip-whitelist`
- [ ] `POST /merchant/api-keys/{id}/ip-whitelist` — `{ cidr: "192.168.1.0/24", label: "Office" }`
- [ ] `DELETE /merchant/api-keys/{id}/ip-whitelist/{whitelistId}`

**Rate limits:**
- [ ] `GET /merchant/api/rate-limit` — текущее использование из Redis:
  ```json
  {
    "limits": {
      "payments_create": { "limit": 60, "remaining": 42, "reset_at": "2026-05-21T09:43:00Z" },
      "status_check":    { "limit": 300, "remaining": 198, "reset_at": "2026-05-21T09:43:00Z" },
      "reports":         { "limit": 120, "remaining": 118, "reset_at": "2026-05-21T09:43:00Z" }
    }
  }
  ```

**Response (list item):**
```json
{
  "id": 1,
  "name": "Production Server",
  "mode": "live",
  "client_id": "client_abc123",
  "permissions": ["payments"],
  "status": "active",
  "last_used_at": "2026-05-21T09:42:00Z",
  "created_at": "2026-05-01T10:00:00Z",
  "ip_whitelist_count": 2
}
```

> `client_secret` не возвращается в списке — только при создании и rotate.

#### 4.2 Application: Merchant Settings
- [ ] `PUT /merchant/settings` — обновить business_name, site_url, logo
- [ ] `POST /merchant/logo` — загрузить логотип
- [ ] `GET /merchant/currencies/auto-convert` — получить настройку авто-конвертации (Phase 2.3b)
- [ ] `PATCH /merchant/currencies/auto-convert` — обновить `is_enabled` + `to_currency_id`

---

### Phase 4b — Payment Links

**Goal:** Мерчант создаёт переиспользуемые checkout-страницы (payment links) с кастомным slug-ом. Покупатель открывает ссылку, выбирает крипту, инициирует платёж. Внутри переиспользует логику Phase 2.6 (`GeneratePaymentUrlAction`).

---

#### 4b.1 Module: MerchantPaymentLink (own)

**Migration: `merchant_payment_links`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint | без FK — isolation rule |
| `slug` | string unique | URL-сегмент: `x9a2lq`, `wls-q2`, `sample` |
| `name` | string 255 | название продукта/услуги |
| `amount` | decimal(18,8) nullable | null = Variable (покупатель вводит сам) |
| `price_currency_id` | int | валюта цены (USD, EUR и т.д.) |
| `settle_currency_id` | int nullable | preferred settlement crypto (USDT) |
| `accepted_currency_ids` | JSON | [1, 2, 3, ...] — список крипто для оплаты |
| `status` | string | `draft` / `active` / `scheduled` / `archived` |
| `scheduled_at` | timestamp nullable | когда автоматически активируется |
| `expires_after_minutes` | int default 15 | TTL рейт-лока на каждую сессию оплаты |
| `redirect_url` | string 500 nullable | URL для редиректа после оплаты (см. ограничение ниже) |
| `collect_email` | bool default true | **всегда true для payment_link** — email обязателен (см. примечание) |
| `show_on_hosted_page` | bool default true | отображать на публичной странице мерчанта |
| `allow_tipping` | bool default false | разрешить произвольную доплату |
| `is_template` | bool default false | сохранено как шаблон, не публикуется |
| `uses_count` | int default 0 | счётчик успешных оплат, инкрементируется при каждом Success |
| `created_at` / `updated_at` | | |

> **Примечание: email обязателен при source=payment_link.**
> `collect_email` нельзя отключить для payment links. Без email мерчант не знает кто оплатил —
> нет order_no от сервера мерчанта, нет идентификатора покупателя. Email — единственный
> способ связать платёж с конкретным покупателем.
> При `source=api` (generate-payment-url) email необязателен — мерчант сам знает кто платит.

> **Примечание: redirect_url работает только если покупатель держит checkout-страницу открытой.**
> Крипто-оплата асинхронна: покупатель уходит из браузера на биржу/кошелёк для отправки.
> Если он закрыл вкладку — редирект не произойдёт, но деньги всё равно придут.
> Checkout-фронт делает polling `GET /public/sessions/{session_id}/status` каждые 10 сек.
> При `status=success` → `window.location = redirect_url`. Best-effort, не гарантия.

**Enum `MerchantPaymentLinkStatusEnum`:** `draft`, `active`, `scheduled`, `archived`

**Module checklist:**
- [ ] Migration `merchant_payment_links`
- [ ] Enum `MerchantPaymentLinkStatusEnum`
- [ ] Model `MerchantPaymentLink`
- [ ] Entity `MerchantPaymentLinkItemEntity`
- [ ] EntityFactory `MerchantPaymentLinkItemEntityFactory`
- [ ] Presenter `MerchantPaymentLinkEntityPresenter`
- [ ] Contract `MerchantPaymentLinkRepositoryContract`
- [ ] Repository `MerchantPaymentLinkRepository`
- [ ] Criteria: `MerchantPaymentLinkByMerchantIdCriteria`, `MerchantPaymentLinkBySlugCriteria`, `MerchantPaymentLinkByStatusCriteria`, `MerchantPaymentLinkByIsTemplateCriteria`, `MerchantPaymentLinkOrderByCreatedAtCriteria`
- [ ] CriteriaTrait `MerchantPaymentLinkCriteriaTrait` (`byMerchantId`, `bySlug`, `byStatus`, `onlyTemplates`, `orderByCreatedAt`)
- [ ] Transfers: `StoreMerchantPaymentLinkTransfer`, `UpdateMerchantPaymentLinkTransfer`
- [ ] Handlers: `StoreMerchantPaymentLinkHandler`, `UpdateMerchantPaymentLinkHandler`, `FindMerchantPaymentLinkByCriteriaHandler`, `PaginateMerchantPaymentLinksHandler`, `IncrementPaymentLinkUsesHandler`
- [ ] ServiceProvider `MerchantPaymentLinkServiceProvider`

---

#### 4b.2 Обновить MerchantPayment (shared table)

Добавить поля в `merchant_payments` (через миграцию — наша таблица):

> **Важно:** `merchant_payments` в роадмапе описана как shared-таблица (Phase 2.5). Но раз мы пишем в неё — нам нужна миграция для добавления наших полей:

- `payment_link_id` — bigint nullable — ссылка на `merchant_payment_links.id`
- `source` — string nullable — `api` / `payment_link`

Критерий для фильтрации в транзакциях:
- `MerchantPaymentBySourceCriteria` — `WHERE source = ?`
- `MerchantPaymentByPaymentLinkIdCriteria` — `WHERE payment_link_id = ?`

---

#### 4b.3 Application: Payment Links CRUD (merchant-facing)

- [ ] `GET /merchant/payment-links` — список (фильтр: `status`, `is_template`, `search` по name/slug, `per_page`)
- [ ] `POST /merchant/payment-links` — создать (статус = `draft`)
- [ ] `GET /merchant/payment-links/{id}` — детали + статистика (uses_count, total_volume через merchant_transactions)
- [ ] `PUT /merchant/payment-links/{id}` — обновить (только в статусе `draft`)
- [ ] `PATCH /merchant/payment-links/{id}/activate` — `draft` → `active`
- [ ] `PATCH /merchant/payment-links/{id}/schedule` — `draft` → `scheduled` (передать `scheduled_at`)
- [ ] `PATCH /merchant/payment-links/{id}/archive` — `active` → `archived`
- [ ] `POST /merchant/payment-links/{id}/duplicate` — создать копию как `draft`
- [ ] `DELETE /merchant/payment-links/{id}` — только `draft` / `archived`
- [ ] `GET /merchant/payment-links/templates` — только `is_template=true`

**Application checklist (per endpoint — по архитектурному паттерну):**
- [ ] `{Action}MerchantPaymentLinkRequestTransfer`, `{Action}MerchantPaymentLinkRequest`
- [ ] `{Action}MerchantPaymentLinkAction`, `{Action}MerchantPaymentLinkController`

**Response (list item):**
```json
{
  "id": 1,
  "slug": "x9a2lq",
  "name": "1kg Yirgacheffe",
  "url": "checkout.uex.pay/p/x9a2lq",
  "amount": "78.00",
  "amount_label": "$78.00",
  "price_currency": { "symbol": "USD" },
  "accepted_currencies": [
    { "id": 1, "symbol": "BTC", "logo": "..." },
    { "id": 2, "symbol": "ETH", "logo": "..." }
  ],
  "uses_count": 42,
  "status": "active",
  "is_template": false,
  "created_at": "2026-05-12T10:00:00Z"
}
```

> `amount = null` → `amount_label = "Variable"`

---

#### 4b.4 Application: Public Checkout Endpoints (buyer-facing, no auth)

Отдельный middleware-группа без `MerchantAuthMiddleware`. Rate-limited.

**`GET /public/payment-links/{slug}`** — вернуть данные ссылки для рендера checkout-страницы:
- Проверить статус Active (archived/draft → 404)
- Не возвращать внутренние поля (merchant_id, fee настройки и т.д.)
- Возвращает: name, amount/variable, accepted_currencies, merchant name/logo, collect_email, allow_tipping, expires_after_minutes

**`POST /public/payment-links/{slug}/initiate`** — покупатель выбрал крипту → создать сессию:
- Input:
  ```json
  {
    "crypto_currency_id": 1,
    "email": "lena@dvb.de",
    "amount": "78.00"
  }
  ```
- `email` обязателен (collect_email всегда true для payment links)
- `amount` обязателен если link.amount = null (Variable), иначе игнорируется
- Внутри вызывает ту же логику что `GeneratePaymentUrlAction` (Phase 2.6):
  - Конвертация курса, расчёт fee, назначение адреса, создание `merchant_payments`
  - `merchant_payments.payment_link_id = link.id`
  - `merchant_payments.source = 'payment_link'`
  - `merchant_payments.metadata.customer_email = email`
- Возвращает:
  ```json
  {
    "session_id": "sess_abc123",
    "address": "TKHfnNi7CMrnF7ME3gL4BXr1qo9VnvrRcs",
    "crypto_amount": "0.000821",
    "net_amount": "0.000816",
    "fee_amount": "0.000005",
    "currency": "BTC",
    "network": "Bitcoin",
    "rate": "95028.40",
    "rate_locked_until": "2026-05-21T09:57:00Z",
    "expires_at": "2026-05-21T09:57:00Z"
  }
  ```

**`GET /public/sessions/{session_id}/status`** — polling статуса для checkout-страницы:
- Покупатель держит вкладку открытой, фронт опрашивает каждые 10 сек
- Возвращает: `{ status: "pending" | "success" | "expired" | "underpaid" }`
- При `success` → фронт редиректит на `redirect_url` (если задан)
- При `expired` → фронт показывает "Session expired, start over"

> **Ограничение redirect_url:** редирект происходит только если покупатель держит
> checkout-страницу открытой в момент подтверждения транзакции. Если покупатель перешёл
> на биржу/кошелёк и закрыл вкладку — редирект не произойдёт. Оплата будет зачислена,
> но покупатель не попадёт на страницу успеха. Это стандартное ограничение крипто-эквайринга
> (BTCPay, NOWPayments — все работают так же).

---

#### 4b.5 Payment Incoming: обновить Phase 2.7

При обработке входящего платежа (`POST /internal/payment/incoming`) — если `merchant_payments.payment_link_id` не null:
- `IncrementPaymentLinkUsesHandler->execute(payment_link_id)` — `uses_count++`
- В `merchant_transaction.metadata` добавить:
  ```json
  {
    "source": "payment_link",
    "payment_link_id": 1,
    "payment_link_slug": "x9a2lq",
    "payment_link_name": "1kg Yirgacheffe",
    "customer_email": "lena@dvb.de"
  }
  ```

---

#### 4b.6 Job: ActivateScheduledPaymentLinksJob

- [ ] Scheduled job (каждую минуту) — активирует ссылки по расписанию
  - `merchant_payment_links` WHERE `status = scheduled` AND `scheduled_at <= now()`
  - `status → active`

---

#### 4b.7 Отображение в транзакциях

**В списке транзакций (Phase 3.1):**
Добавить в response поле `source` (`api` / `payment_link`). Добавить фильтр `?filter[source]=payment_link`.

**В детали транзакции (Phase 3.9):**
Если `source = payment_link` — дополнительный блок:
```json
{
  "source": "payment_link",
  "customer": "lena@dvb.de",
  "payment_link": {
    "id": 1,
    "name": "1kg Yirgacheffe",
    "slug": "x9a2lq",
    "url": "checkout.uex.pay/p/x9a2lq"
  }
}
```

Если `source = api`:
```json
{
  "source": "api",
  "customer": null
}
```

---

#### 4b.8 Будущие фазы (stub)

> **Invoices** — B2B счёт с данными клиента, номером инвойса, сроком оплаты. Отдельный модуль `merchant_invoices`. Вкладка "Invoices" в UI. Вынести в отдельную фазу при проектировании.

> **Subscriptions** — рекуррентные платежи с периодичностью (weekly/monthly). Отдельный модуль `merchant_subscriptions`. Вкладка "Subscriptions" в UI. Требует отдельного проектирования.

---

### Phase 4c — Test Mode / Sandbox

**Goal:** Мерчант тестирует интеграцию без реальных денег и блокчейна. API ключ с `mode=test` включает sandbox-режим на всю цепочку.

#### 4c.1 Принцип: mode наследуется от API ключа

`merchant_apps.mode = 'live' | 'test'` (добавлено в Phase 4.1).

`ModeResolverMiddleware` (из Phase 4.1) → кладёт mode в `app()->instance('request.mode')` для всей цепочки запроса. Все Action/Job/Handler читают `app('request.mode')`.

#### 4c.2 Изоляция данных (миграции)

Добавить `mode` колонку в ключевые таблицы:

```sql
-- merchant_balances: отдельные строки для live и test
ALTER TABLE merchant_balances
  ADD COLUMN mode ENUM('live','test') NOT NULL DEFAULT 'live' AFTER frozen_amount;
DROP INDEX merchant_balances_merchant_id_currency_id_unique;
ADD UNIQUE KEY (merchant_id, currency_id, mode);

-- merchant_payments: помечаем сессию режимом
ALTER TABLE merchant_payments
  ADD COLUMN mode ENUM('live','test') NOT NULL DEFAULT 'live' AFTER source;

-- merchant_transactions: помечаем транзакцию режимом
ALTER TABLE merchant_transactions
  ADD COLUMN mode ENUM('live','test') NOT NULL DEFAULT 'live' AFTER status;
```

**Принцип:** live и test данные живут в тех же таблицах, но изолированы полем `mode`. Dashboard, транзакции, балансы — всегда фильтруют по `mode='live'` (если не указано иное). В кабинете мерчант может переключиться в "Test view" и увидеть тест-данные с бейджем **TEST**.

#### 4c.3 Платёжный флоу в test mode

**Создание платежа (test):**
```
POST /merchant/generate-payment-url  (test API ключ)
  → mode = 'test' (из ModeResolverMiddleware)
  → НЕ вызывать CryptoGatewayService (нет реального адреса)
  → address = "test_mock_" + random_hex(16)
  → merchant_payments.mode = 'test'
  → НЕ INSERT в merchant_address_assignments (нет реального назначения)
  → НЕ INSERT в wallet_histories (shared DB)
  → Всё остальное как обычно (fee, курс, TTL)
```

**Симуляция подтверждения:**
```
POST /merchant/test/simulate/payment-received
  { "payment_id": "pay_uuid_xxx", "amount": "101.000000" }
  Auth: test API ключ (ModeResolverMiddleware проверяет mode=test, иначе 403)
  → Запускает ту же логику что POST /internal/payment/incoming
  → merchant_payments.status = MerchantPaymentStatusEnum::SUCCESS, mode = 'test'
  → merchant_transactions.mode = MerchantAppModeEnum::TEST
  → merchant_balances: зачисляет на строку WHERE mode='test'
  → Webhook payload: "livemode": false
```

> Faucet (прямое пополнение баланса) — не нужен. Баланс накапливается через симуляцию, как в живом режиме.

**Запрет вывода в test mode:**
```php
// WithdrawalAction
if (app('request.mode') === 'test') {
    throw new TestModeOperationException('Withdrawals are not available in test mode.');
    // HTTP 422 с понятным сообщением
}
```

#### 4c.4 Webhooks в test mode

`merchant_webhooks.mode` (из Phase 2.10) используется для фильтрации:
- Test API ключ → платёж с `mode=test` → `DispatchMerchantWebhookJob` ищет только webhooks с `mode='test'`
- Live API ключ → только live webhooks

Payload test-вебхука содержит `"livemode": false`. Мерчант настраивает отдельный URL для test-вебхуков.

#### 4c.5 Application checklist

- [ ] `ModeResolverMiddleware` (Phase 4.1 — уже заложено)
- [ ] Миграции: `mode` в `merchant_balances`, `merchant_payments`, `merchant_transactions`
- [ ] Обновить `GeneratePaymentUrlAction` — if mode=test: mock address, skip gateway/assignments
- [ ] `POST /merchant/test/simulate/payment-received` — только для test ключей
  - `SimulatePaymentReceivedRequest`, `SimulatePaymentReceivedAction`
  - Внутри вызывает ту же логику что `IncomingPaymentAction` с test-флагом
- [ ] Обновить `WithdrawalAction` — check mode=test → throw `TestModeOperationException`
- [ ] Обновить `DispatchMerchantWebhookJob` — фильтровать по `mode` платежа
- [ ] Обновить `PaginateMerchantTransactionsAction` — добавить фильтр `mode` (default: live)
- [ ] Обновить `GET /merchant/balance` — отдавать live балансы (test балансы по отдельному флагу)

#### 4c.6 Test vs Live — что изолировано, что общее

| Компонент | Изолировано по mode | Общее |
|---|---|---|
| `merchant_balances` | ✓ отдельная строка | — |
| `merchant_transactions` | ✓ mode='test' | — |
| `merchant_payments` | ✓ mode='test' | — |
| `merchant_webhooks` | ✓ mode='test' endpoint | — |
| API keys | ✓ mode='live'/'test' | — |
| `merchant_enabled_currencies` | — | ✓ (одни и те же) |
| `merchant_withdrawal_destinations` | — | ✓ (адреса общие) |
| `merchant_webhook_logs` | ✓ через webhook.mode | — |

---

### Phase 5 — Analytics

**Goal:** KPI, статистика по платежам и выручке.

#### 5.1 Application: Dashboard Summary (KPI Cards)

**Goal:** 4 KPI-метрики для главной страницы дашборда с мини-спарклайнами и дельтой к предыдущему периоду (mer-2.1.png).

| Карточка | Данные |
|---|---|
| **Available to withdraw** | `SUM(merchant_balances.amount)` по всем валютам → конвертация в USD |
| **Volume · last 7d** | `SUM(amount)` в `merchant_transactions` type=PAYMENT_RECEIVED status=Success за 7д → USD |
| **Transactions** | `COUNT(*)` в `merchant_transactions` type=PAYMENT_RECEIVED за 7д |
| **Avg ticket** | Volume / Transactions за 7д → USD |

У каждой карточки: `value`, `delta_percent`, `delta_absolute`, `delta_positive`, `sparkline` (7 дневных точек).

##### Strategy pattern (аналог `GetEarningsChartCommand` + `blocks[]` из uexapp-backend)

```
App\Application\Shared\Analytics\
  Contracts\
    DashboardSummaryBlockStrategyContract.php   — build(merchantId): SummaryBlockResult
  Resolvers\
    DashboardSummaryBlockResolver.php
  Commands\
    GetDashboardSummaryCommand.php              — итерирует blocks[], вызывает resolver
  Results\
    DashboardSummaryResult.php                  — implements Arrayable
    SummaryBlockResult.php                      — block, value, delta_percent, delta_absolute, delta_positive, sparkline[]
  Strategies\Summary\
    AvailableBalanceSummaryStrategy.php         — merchant_balances → USD (курс из currencies.rate)
    VolumeSummaryStrategy.php                   — merchant_transactions sum, GROUP BY day
    TransactionsSummaryStrategy.php             — merchant_transactions count, GROUP BY day
    AvgTicketSummaryStrategy.php                — volume / transactions, sparkline из дневных avg
```

**`DashboardSummaryBlockStrategyContract`:**
```php
interface DashboardSummaryBlockStrategyContract
{
    public function build(int $merchantId): SummaryBlockResult;
    public function supports(DashboardSummaryBlockEnum $block): bool;
}
```

**`SummaryBlockResult`** (`final readonly class implements Arrayable`):
```php
public function __construct(
    public DashboardSummaryBlockEnum $block,
    public string $value,          // "48213.92"
    public string $label,          // "Available to withdraw"
    public string $deltaPercent,   // "+9.4"
    public string $deltaAbsolute,  // "+4128.50"
    public bool   $deltaPositive,  // true = green badge, false = red
    public array  $sparkline,      // ["44085.00", ..., "48213.92"] — 7 точек
) {}
```

**Дельта и спарклайн:**
- `available_balance`: дельта = (сегодня - вчера) / вчера × 100; спарклайн = 7 дневных снимков баланса
- `volume`, `transactions`, `avg_ticket`: дельта = (текущие 7д - предыдущие 7д) / предыдущие 7д × 100; спарклайн = дневные значения за текущие 7д

**Всё через `bcmath`** (8 знаков для сумм, 2 знака для процентов).

##### Enum

```
App\Modules\MerchantTransaction\Enums\DashboardSummaryBlockEnum (новый)
  AVAILABLE_BALANCE = 'available_balance'
  VOLUME            = 'volume'
  TRANSACTIONS      = 'transactions'
  AVG_TICKET        = 'avg_ticket'
```

##### Application checklist

- [ ] Enum `DashboardSummaryBlockEnum`
- [ ] Contract `DashboardSummaryBlockStrategyContract`
- [ ] Resolver `DashboardSummaryBlockResolver` — итерирует массив стратегий, `supports()` → resolve
- [ ] Command `GetDashboardSummaryCommand`
- [ ] Results: `DashboardSummaryResult`, `SummaryBlockResult`
- [ ] Strategies: `AvailableBalanceSummaryStrategy`, `VolumeSummaryStrategy`, `TransactionsSummaryStrategy`, `AvgTicketSummaryStrategy`
- [ ] `GetDashboardSummaryRequestTransfer` — `blocks: DashboardSummaryBlockEnum[]` (default: все 4)
- [ ] `GetDashboardSummaryRequest` — rules() + getTransfer()
- [ ] `GetDashboardSummaryAction`
- [ ] `GetDashboardSummaryController`
- [ ] `GET /merchant/analytics/summary`

##### Response

```json
{
  "blocks": [
    {
      "block": "available_balance",
      "label": "Available to withdraw",
      "value": "48213.92",
      "delta_percent": "+9.4",
      "delta_absolute": "+4128.50",
      "delta_positive": true,
      "sparkline": ["44085.00","45200.00","46100.00","45800.00","46900.00","47500.00","48213.92"]
    },
    {
      "block": "volume",
      "label": "Volume · last 7 days",
      "value": "214890.12",
      "delta_percent": "+17.6",
      "delta_absolute": "+32118.00",
      "delta_positive": true,
      "sparkline": ["25000.00","28000.00","31000.00","27000.00","32000.00","36000.00","35890.12"]
    },
    {
      "block": "transactions",
      "label": "Transactions",
      "value": "1284",
      "delta_percent": "+12.9",
      "delta_absolute": "+147",
      "delta_positive": true,
      "sparkline": ["160","175","189","172","195","212","181"]
    },
    {
      "block": "avg_ticket",
      "label": "Avg ticket",
      "value": "167.32",
      "delta_percent": "-2.4",
      "delta_absolute": "-4.10",
      "delta_positive": false,
      "sparkline": ["172.00","168.50","171.00","169.00","166.00","167.50","167.32"]
    }
  ]
}
```

> `blocks[]` query param позволяет запросить только нужные карточки: `?blocks[]=volume&blocks[]=transactions`.
> По умолчанию (пустой массив) — возвращаются все 4.

#### 5.2 Application: Transaction Page KPI

**Goal:** 4 KPI-метрики для страницы транзакций — обновляются в реальном времени.

Метрики (по дизайну из mer-1.1.png):

| Метрика | Источник |
|---|---|
| **Today's volume** | `merchant_transactions` WHERE type=PAYMENT_RECEIVED AND status=Success AND DATE(created_at)=today — сумма amount |
| **Transactions today** | то же самое — COUNT |
| **Pending** | `merchant_payments` WHERE status=Pending — COUNT (awaiting confirmation) |
| **Failed (24h)** | `merchant_payments` WHERE status IN (Expired, Blocked) AND created_at >= now()-24h — COUNT + % от volume |

**Архитектура:**

Добавить новый метод в `MerchantTransactionContract` + `MerchantPaymentContract`:
```php
// MerchantTransactionContract
public function getTodayKpi(int $merchantId): array; // ['volume' => '28406.14', 'count' => 147, 'volume_delta_pct' => '+12.43']

// MerchantPaymentContract
public function getPendingCount(int $merchantId): int;
public function getFailedLast24hCount(int $merchantId): int;
public function getTotalVolumeToday(int $merchantId): string; // для расчёта % failed
```

**Module layer** (расширение существующих модулей MerchantTransaction + MerchantPayment):
- [ ] `GetTransactionKpiHandler` — агрегирует volume + count за сегодня + дельта vs вчера
- [ ] `GetPaymentPendingCountHandler` — COUNT pending payments
- [ ] `GetPaymentFailedCountHandler` — COUNT expired/blocked за 24ч + % от сегодняшнего volume

**Application layer:**
- [ ] `GetTransactionKpiController` → `GetTransactionKpiAction`
- [ ] `GET /merchant/transactions/kpi`

**Response:**
```json
{
  "today_volume": "28406.14",
  "today_volume_delta_pct": "+12.43",
  "today_volume_delta_label": "vs avg",
  "transactions_today": 147,
  "transactions_today_delta": "+22",
  "pending": 3,
  "pending_label": "awaiting confirmation",
  "failed_24h": 2,
  "failed_24h_pct_of_volume": "0.4"
}
```

---

#### 5.3 Application: Settlement Volume Chart

**Goal:** Area chart главного дашборда с переключателем периода и 3 сериями (mer-2.2.png).

Переключатели: `24H`, `7D`, `30D`, `All`. Серии: **Settled**, **Pending**, **Failed**.

##### Рекомендация: 1 endpoint с `series[]` параметром (не отдельные роуты)

Аналогично `/earning/chart?blocks[]` из uexapp-backend — один запрос возвращает все запрошенные серии. Это позволяет:
- Frontend делает 1 HTTP-запрос при переключении периода, получает все линии сразу
- Добавить новую серию (например, `refunded`) без нового роута — только стратегия + enum
- Или запросить только нужные серии: `?range=7d&series[]=settled`

##### Strategy pattern

```
App\Application\Shared\Analytics\
  Contracts\
    DashboardChartSeriesStrategyContract.php    — build(merchantId, range): ChartSeriesResult
  Resolvers\
    DashboardChartSeriesResolver.php
  Commands\
    GetDashboardChartCommand.php                — итерирует series[], вызывает resolver для каждой
  Results\
    DashboardChartResult.php                    — range, total, total_delta_percent, total_delta_absolute, series[]
    ChartSeriesResult.php                       — series, total, points[]
    ChartDataPoint.php                          — date, value (USD), count
  Strategies\Chart\
    SettledChartSeriesStrategy.php              — merchant_transactions type=PAYMENT_RECEIVED status=Success
    PendingChartSeriesStrategy.php              — merchant_payments status=Pending
    FailedChartSeriesStrategy.php               — merchant_payments status IN (Expired, Blocked)
```

**`DashboardChartSeriesStrategyContract`:**
```php
interface DashboardChartSeriesStrategyContract
{
    public function build(int $merchantId, DashboardChartRangeEnum $range): ChartSeriesResult;
    public function supports(DashboardChartSeriesEnum $series): bool;
}
```

**`ChartDataPoint`** (`final readonly class`):
```php
public function __construct(
    public string $date,    // "2026-05-17" (7D/30D/All) или "2026-05-17 14:00" (24H)
    public string $value,   // сумма в USD за этот период
    public int    $count,   // количество транзакций/платежей
) {}
```

**Группировка по range:**

| Range | GROUP BY | Количество точек |
|---|---|---|
| `24h` | hour | 24 точки |
| `7d` | day | 7 точек |
| `30d` | day | 30 точек |
| `all` | week (если < 6м) или month | переменно |

**Заголовок chart** (SETTLEMENT VOLUME + дельта): `total` = SUM всех точек в `settled` серии за период; `total_delta` = vs предыдущий равный период.

##### Enums (новые)

```
DashboardChartRangeEnum  — 24h, 7d, 30d, all     (используется также в Phase 5.4)
DashboardChartSeriesEnum — settled, pending, failed
```

##### Application checklist

- [ ] Enums `DashboardChartRangeEnum`, `DashboardChartSeriesEnum`
- [ ] Contract `DashboardChartSeriesStrategyContract`
- [ ] Resolver `DashboardChartSeriesResolver`
- [ ] Command `GetDashboardChartCommand`
- [ ] Results: `DashboardChartResult`, `ChartSeriesResult`, `ChartDataPoint`
- [ ] Strategies: `SettledChartSeriesStrategy`, `PendingChartSeriesStrategy`, `FailedChartSeriesStrategy`
- [ ] `GetDashboardChartRequestTransfer` — `range: DashboardChartRangeEnum`, `series: DashboardChartSeriesEnum[]` (default: все 3)
- [ ] `GetDashboardChartRequest` — rules() + getTransfer()
- [ ] `GetDashboardChartAction`
- [ ] `GetDashboardChartController`
- [ ] `GET /merchant/analytics/chart`

##### Response

```json
{
  "range": "7d",
  "total": "214890.12",
  "total_delta_percent": "+17.6",
  "total_delta_absolute": "+32118.00",
  "series": [
    {
      "series": "settled",
      "total": "204890.12",
      "points": [
        { "date": "2026-05-15", "value": "28420.00", "count": 42 },
        { "date": "2026-05-16", "value": "31000.00", "count": 48 },
        { "date": "2026-05-17", "value": "28420.00", "count": 38 }
      ]
    },
    {
      "series": "pending",
      "total": "8500.00",
      "points": [
        { "date": "2026-05-15", "value": "1200.00", "count": 5 },
        { "date": "2026-05-16", "value": "3210.00", "count": 12 }
      ]
    },
    {
      "series": "failed",
      "total": "1500.00",
      "points": [
        { "date": "2026-05-15", "value": "300.00", "count": 2 }
      ]
    }
  ]
}
```

> **Добавление новой серии** (например `refunded`): создать `RefundedChartSeriesStrategy`, добавить `refunded` в `DashboardChartSeriesEnum`, зарегистрировать в Resolver. Ничего существующего не трогаем.

---

#### 5.4 Application: Volume by Asset

**Goal:** Разбивка объёма по крипто-валютам за выбранный период — правый блок дашборда (mer-2.2.png).

Отдельный endpoint (не объединять с chart) — это не time-series, а агрегат. Разные SQL, разная форма данных. Frontend может кешировать независимо.

**Данные:** `merchant_transactions` WHERE type=PAYMENT_RECEIVED AND status=Success AND period → `GROUP BY currency_id, SUM(amount)`. Enrich через batch-fetch `currencies` (symbol, name, logo). Конвертировать в USD через `currencies.rate`. Сортировка по `amount_usd DESC`.

##### Application checklist

- [ ] Command `GetDashboardAssetsCommand` — group by currency, convert to USD, calculate percentages
- [ ] Results: `DashboardAssetsResult`, `AssetVolumeResult` (currency_id, symbol, name, logo, amount_native, amount_usd, share_percent)
- [ ] `GetDashboardAssetsRequestTransfer` — `range: DashboardChartRangeEnum`
- [ ] `GetDashboardAssetsRequest` — rules() + getTransfer()
- [ ] `GetDashboardAssetsAction`
- [ ] `GetDashboardAssetsController`
- [ ] `GET /merchant/analytics/assets`

##### Response

```json
{
  "range": "7d",
  "total_usd": "214890.12",
  "assets": [
    {
      "currency_id": 1,
      "symbol": "BTC",
      "name": "Bitcoin",
      "logo": "https://...",
      "amount_native": "2.41",
      "amount_usd": "108420.00",
      "share_percent": "50"
    },
    {
      "currency_id": 2,
      "symbol": "USDT",
      "name": "Tether",
      "logo": "https://...",
      "amount_native": "62310.00",
      "amount_usd": "62310.00",
      "share_percent": "29"
    },
    {
      "currency_id": 3,
      "symbol": "ETH",
      "name": "Ethereum",
      "logo": "https://...",
      "amount_native": "7.84",
      "amount_usd": "28114.00",
      "share_percent": "13"
    }
  ]
}
```

---

### Phase 6 — Global Search

**Goal:** Единый поиск по транзакциям, инвойсам и клиентам через один endpoint. Strategy-архитектура позволяет добавлять новые источники без изменения ядра — каждый источник оборачивается в стратегию, которая содержит свои хендлеры и критерии.

Строка поиска в топбаре: `Search transactions, invoices, customers…` (⌘K)

#### 6.1 Shared: Search Infrastructure

```
App\Application\Shared\Search\
  Contracts\
    SearchStrategyContract.php
  SearchStrategyRegistry.php
  Providers\
    SearchServiceProvider.php
```

**SearchStrategyContract:**
```php
interface SearchStrategyContract
{
    public function getType(): string;     // 'transactions' | 'invoices' | 'customers'
    public function execute(string $query, int $merchantId, int $limit): array;
}
```

**SearchStrategyRegistry** (`final readonly class`):
- Принимает `SearchStrategyContract[]` через DI
- `resolve(string $type): SearchStrategyContract`
- `resolveAll(): array` — все зарегистрированные стратегии
- Биндинги регистрируются в `SearchServiceProvider`

**SearchServiceProvider:**
```php
$this->app->bind(SearchStrategyRegistry::class, fn($app) => new SearchStrategyRegistry([
    $app->make(TransactionSearchStrategy::class),
    $app->make(InvoiceSearchStrategy::class),
    $app->make(CustomerSearchStrategy::class),
]));
```

#### 6.2 Criteria: добавить в существующие модули

| Модуль | Criteria | Поля для поиска |
|---|---|---|
| MerchantTransaction | `MerchantTransactionBySearchCriteria` | metadata->>'order_no' LIKE, uuid LIKE, metadata->>'tx_hash' LIKE |
| MerchantPayment | `MerchantPaymentBySearchCriteria` | order_no LIKE, uuid LIKE, gateway_reference LIKE |
| User | `UserBySearchCriteria` | email LIKE |

Каждая criteria добавляется в соответствующий `{Feature}CriteriaTrait` как `bySearch(string $q): static`.

#### 6.3 Strategies

```
App\Application\Shared\Search\Strategies\
  TransactionSearchStrategy.php   — ищет в merchant_transactions
  InvoiceSearchStrategy.php       — ищет в merchant_payments (payment sessions)
  CustomerSearchStrategy.php      — ищет в users по email
```

Каждая стратегия:
- `final readonly class implements SearchStrategyContract`
- Инжектирует нужный Handler (с CriteriaTrait)
- `execute()` применяет `bySearch($q)->byMerchantId($merchantId)->limit($limit)->execute()`
- Возвращает нормализованный массив: `[['id', 'type', 'label', 'sublabel', 'meta', 'link']]`

**Пример ответа TransactionSearchStrategy:**
```php
[
  'id'       => 42,
  'type'     => 'transactions',
  'label'    => '#ORD-1042',
  'sublabel' => '+249.21 USDT · Settled',
  'meta'     => ['status' => 'Success', 'created_at' => '2026-05-21T09:42:00Z'],
  'link'     => '/transactions/42',
]
```

#### 6.4 Application: Search Endpoint

- [ ] `SearchMerchantRequestTransfer` — `q: string (min:2, max:100)`, `types: array (nullable)`, `limit: int = 5`
- [ ] `SearchMerchantRequest` — правила валидации + `getTransfer()`
- [ ] `SearchMerchantAction` — резолвит стратегии из registry, вызывает каждую, merge результатов
- [ ] `SearchMerchantController` (GET → не мутирует, но сложная логика → через Action)
- [ ] `GET /merchant/search?q=...&types[]=transactions&types[]=invoices`

**Response:**
```json
{
  "query": "ORD-1042",
  "results": {
    "transactions": [
      { "id": 42, "label": "#ORD-1042", "sublabel": "+249.21 USDT · Settled", "link": "/transactions/42" }
    ],
    "invoices": [
      { "id": 15, "label": "#ORD-1042", "sublabel": "249.21 USDT · Pending", "link": "/invoices/15" }
    ],
    "customers": []
  }
}
```

> Добавление нового источника поиска: создать `{Feature}SearchStrategy`, добавить `{Feature}BySearchCriteria` в модуль, зарегистрировать стратегию в `SearchServiceProvider` — больше ничего не трогаем.

---

### Phase 7 — Notifications

**Goal:** Три канала нотификаций — все реализованы в uex-merchant-platform.
uexapp-backend используется только как референс для паттернов, не как место реализации.

1. **Telegram** — admin-уведомления об событиях мерчантов (гибкая стратегия с Message Builder)
2. **Email** — уведомления владельцу мерчанта (через Laravel Mail + queue)
3. **In-system** — bell-уведомления внутри кабинета (новый модуль)

Точки входа — уже существующие Action-ы / Job-ы:
- `POST /internal/payment/incoming` → `paymentReceived()`
- `ExpireMerchantPaymentsJob` → `paymentExpired()`
- Webhook approve/reject → `withdrawalStatusChanged()`
- `DispatchMerchantWebhookJob` (3+ неудачи) → `webhookFailed()`

---

#### 7.1 Shared: Telegram Infrastructure

**Проблема** существующего подхода в uexapp-backend:
- `NotificationTelegramAdminTransfer` — God-объект с 20 полями под крипто-транзакции, большинство nullable
- `AbstractTelegramNotificationStrategy` принуждает все стратегии принимать этот же Transfer
- `SendListingApplicationNotification` — обходит интерфейс (свой Transfer, не наследует Abstract), строит текст ручной конкатенацией

**Решение** — три независимых компонента без наследования:

```
App\Application\Shared\Notifications\Telegram\
  Builder\
    TelegramMessageBuilder.php      — fluent builder: header + rows + buttons → string
  Sender\
    TelegramSender.php              — единственное место с HTTP/cURL логикой
  Contracts\
    TelegramStrategyContract.php    — маркерный интерфейс для DI
  Strategies\                      — конкретные стратегии, одна на событие
  Transfers\                       — типизированные Transfer-ы, одна на событие
  Providers\
    TelegramNotificationServiceProvider.php
```

---

**`TelegramMessageBuilder`** (`final class` — fluent, не readonly т.к. мутирует внутреннее состояние):

```php
final class TelegramMessageBuilder
{
    private string $text = '';
    private array  $buttons = [];

    public static function make(): static
    {
        return new static();
    }

    public function header(string $title): static
    {
        $this->text .= "*{$title}*\n\n";
        return $this;
    }

    // emoji + bold label + monospace value
    public function row(string $label, string $value, string $emoji = ''): static
    {
        $prefix       = $emoji !== '' ? "{$emoji} " : '';
        $this->text  .= "{$prefix}*{$label}:* `{$value}`\n";
        return $this;
    }

    // строка без форматирования
    public function line(string $text): static
    {
        $this->text .= "{$text}\n";
        return $this;
    }

    public function spacer(): static
    {
        $this->text .= "\n";
        return $this;
    }

    // одна кнопка = одна строка inline-keyboard
    public function button(string $label, string $url): static
    {
        $this->buttons[][] = ['text' => $label, 'url' => $url];
        return $this;
    }

    // несколько кнопок в одну строку inline-keyboard
    public function buttonRow(array $buttons): static
    {
        $this->buttons[] = array_map(
            static fn(array $b) => ['text' => $b['label'], 'url' => $b['url']],
            $buttons
        );
        return $this;
    }

    public function getText(): string  { return trim($this->text); }
    public function getButtons(): array { return $this->buttons; }
}
```

Пример использования в стратегии:
```php
TelegramMessageBuilder::make()
    ->header('💰 Payment Received')
    ->row('Merchant', $transfer->merchantName,  '🏪')
    ->row('Order',   '#' . $transfer->orderNo, '📦')
    ->row('Amount',  $transfer->amount . ' ' . $transfer->currency, '💵')
    ->row('Network', $transfer->network, '🌐')
    ->spacer()
    ->row('Customer', $transfer->customerEmail, '👤')
    ->button('Open in Cabinet', $transfer->cabinetUrl);
```

---

**`TelegramSender`** (`final readonly class`) — единственное место с HTTP:

```php
final readonly class TelegramSender
{
    // читает конфиг: telegram.merchant.bot_token / chat_id / forum_chat_id
    public function send(TelegramMessageBuilder $builder, string $logChannel, ?int $topicId = null): void;
    // внутри: cURL POST → api.telegram.org/sendMessage, Log::channel($logChannel)->error() при неудаче
}
```

Биндится как singleton в ServiceProvider. Стратегии получают через конструктор — нет трейтов, нет статики.

---

**`TelegramStrategyContract`** — маркерный интерфейс (без методов):

```php
interface TelegramStrategyContract
{
    // Намеренно пуст.
    // Каждая стратегия имеет свой typed send(OwnTransfer $t): void.
    // Интерфейс существует только для DI-тегирования и type-hint в MerchantNotificationService.
    // Единый send(CommonTransfer): void невозможен без потери типизации в PHP.
}
```

---

**Шаблон стратегии** (каждая — `final readonly class`):

```php
final readonly class SendMerchantPaymentReceivedStrategy implements TelegramStrategyContract
{
    public function __construct(private TelegramSender $sender) {}

    public function send(MerchantPaymentReceivedTelegramTransfer $transfer): void
    {
        $builder = TelegramMessageBuilder::make()
            ->header('💰 Payment Received')
            ->row('Merchant', $transfer->merchantName,  '🏪')
            ->row('Order',   '#' . $transfer->orderNo, '📦')
            ->row('Amount',  $transfer->amount . ' ' . $transfer->currency, '💵')
            ->row('Network', $transfer->network, '🌐')
            ->spacer()
            ->row('Customer', $transfer->customerEmail, '👤')
            ->button('Open in Cabinet', $transfer->cabinetUrl);

        $this->sender->send($builder, 'telegram_merchant', config('telegram.merchant.topics.payments'));
    }
}
```

**Шаблон Transfer** (каждый — `final readonly class`, только нужные поля):

```php
final readonly class MerchantPaymentReceivedTelegramTransfer
{
    public function __construct(
        public string $merchantName,
        public string $orderNo,
        public string $amount,
        public string $currency,
        public string $network,
        public string $customerEmail,
        public string $cabinetUrl,
    ) {}
}
```

**Стратегии + Transfer-ы для реализации:**
- [ ] `SendMerchantPaymentReceivedStrategy` + `MerchantPaymentReceivedTelegramTransfer`
- [ ] `SendMerchantWithdrawalRequestedStrategy` + `MerchantWithdrawalRequestedTelegramTransfer`
- [ ] `SendMerchantWithdrawalApprovedStrategy` + `MerchantWithdrawalApprovedTelegramTransfer`
- [ ] `SendMerchantWithdrawalRejectedStrategy` + `MerchantWithdrawalRejectedTelegramTransfer`
- [ ] `SendMerchantWebhookFailedStrategy` + `MerchantWebhookFailedTelegramTransfer`

> **Добавление новой стратегии:** создать Transfer с нужными полями → создать стратегию, построить сообщение через Builder → зарегистрировать в TelegramNotificationServiceProvider → добавить вызов в MerchantNotificationService. Ничего существующего не трогаем.

---

#### 7.2 Shared: Email Notifications

Отправка email владельцу мерчанта через **Laravel Mail** (Mailable + ShouldQueue).

```
App\Application\Shared\Notifications\Email\
  Contracts\
    MerchantMailableContract.php    — interface getSubject(): string
  Mailables\
    MerchantPaymentReceivedMailable.php
    MerchantWithdrawalApprovedMailable.php
    MerchantWithdrawalRejectedMailable.php
    MerchantWebhookFailedMailable.php
  MerchantMailer.php                — final readonly class, dispatch через queue
```

**`MerchantMailer`** (`final readonly class`):

```php
final readonly class MerchantMailer
{
    public function send(string $toEmail, MerchantMailableContract $mailable): void;
    // Mail::to($toEmail)->queue($mailable); + Log при ошибке
}
```

**Каждый Mailable** — `final class extends Mailable implements ShouldQueue, MerchantMailableContract`:
- Принимает только нужные поля в конструктор (не общий God-Transfer)
- `build()` рендерит Blade-шаблон `resources/views/emails/merchant/{event}.blade.php`

```php
final class MerchantPaymentReceivedMailable extends Mailable implements ShouldQueue, MerchantMailableContract
{
    public function __construct(
        public readonly string $merchantName,
        public readonly string $orderNo,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $cabinetUrl,
    ) {}

    public function build(): static
    {
        return $this->subject($this->getSubject())
                    ->view('emails.merchant.payment_received');
    }

    public function getSubject(): string
    {
        return "Payment received: {$this->amount} {$this->currency} · Order #{$this->orderNo}";
    }
}
```

**Mailables для реализации:**
- [ ] `MerchantPaymentReceivedMailable` + `resources/views/emails/merchant/payment_received.blade.php`
- [ ] `MerchantWithdrawalApprovedMailable` + `withdrawal_approved.blade.php`
- [ ] `MerchantWithdrawalRejectedMailable` + `withdrawal_rejected.blade.php`
- [ ] `MerchantWebhookFailedMailable` + `webhook_failed.blade.php`

---

#### 7.3 Module: MerchantNotification (In-system bell)

**Migration: `merchant_notifications`**

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| merchant_id | bigint | без FK — isolation rule |
| type | string | MerchantNotificationTypeEnum |
| title | string 255 | strip_tags |
| message | text | strip_tags |
| is_read | boolean | default false |
| link_url | string 500 | nullable, deep-link в кабинете |
| created_at | timestamp | UPDATED_AT = null |

**Enum:** `MerchantNotificationTypeEnum` — `green / warning / red`

**Module checklist:**
- [ ] Migration `merchant_notifications`
- [ ] Enum `MerchantNotificationTypeEnum`
- [ ] Model `MerchantNotification` (SoftDeletes, `UPDATED_AT = null`)
- [ ] Entity `MerchantNotificationItemEntity`
- [ ] EntityFactory `MerchantNotificationItemEntityFactory`
- [ ] Presenter `MerchantNotificationEntityPresenter`
- [ ] Contract `MerchantNotificationRepositoryContract`
- [ ] Repository `MerchantNotificationRepository`
- [ ] Criteria: `MerchantNotificationByMerchantIdCriteria`, `MerchantNotificationByIsReadCriteria`, `MerchantNotificationOrderByCreatedAtCriteria`, `MerchantNotificationLimitCriteria`
- [ ] CriteriaTrait `MerchantNotificationCriteriaTrait` (`byMerchantId`, `onlyUnread`, `orderByCreatedAt`, `limit`)
- [ ] Transfer: `CreateMerchantNotificationTransfer`
- [ ] Handlers:
  - `CreateMerchantNotificationHandler`
  - `PaginateMerchantNotificationsHandler`
  - `MarkAllReadMerchantNotificationsHandler` — bulk UPDATE is_read=true WHERE merchant_id AND is_read=false
  - `MarkOneReadMerchantNotificationHandler`
  - `CountUnreadMerchantNotificationsHandler` → `int`
- [ ] ServiceProvider `MerchantNotificationServiceProvider`

---

#### 7.4 Shared: MerchantNotificationService (оркестратор)

Единственная точка входа для всех трёх каналов. Вызывается из Action-ов / Job-ов.

```
App\Application\Shared\Notifications\MerchantNotificationService
```

`final readonly class`, инжектирует конкретные стратегии напрямую:

```php
final readonly class MerchantNotificationService
{
    public function __construct(
        private CreateMerchantNotificationHandler       $notificationHandler,
        private MerchantMailer                          $mailer,
        private SendMerchantPaymentReceivedStrategy     $paymentReceivedTg,
        private SendMerchantWithdrawalRequestedStrategy $withdrawalRequestedTg,
        private SendMerchantWithdrawalApprovedStrategy  $withdrawalApprovedTg,
        private SendMerchantWithdrawalRejectedStrategy  $withdrawalRejectedTg,
        private SendMerchantWebhookFailedStrategy       $webhookFailedTg,
    ) {}

    public function paymentReceived(
        int $merchantId, string $merchantName, string $merchantEmail,
        string $orderNo, string $amount, string $currency,
        string $network, string $customerEmail,
    ): void {
        $this->notificationHandler->execute(new CreateMerchantNotificationTransfer(
            merchantId: $merchantId,
            type:       MerchantNotificationTypeEnum::GREEN,
            title:      'Payment received',
            message:    "Order #{$orderNo} · +{$amount} {$currency}",
            linkUrl:    "/transactions?order={$orderNo}",
        ));
        $this->paymentReceivedTg->send(new MerchantPaymentReceivedTelegramTransfer(
            merchantName:  $merchantName,
            orderNo:       $orderNo,
            amount:        $amount,
            currency:      $currency,
            network:       $network,
            customerEmail: $customerEmail,
            cabinetUrl:    config('app.url') . "/transactions?order={$orderNo}",
        ));
        $this->mailer->send($merchantEmail, new MerchantPaymentReceivedMailable(
            merchantName: $merchantName,
            orderNo:      $orderNo,
            amount:       $amount,
            currency:     $currency,
            cabinetUrl:   config('app.url') . '/transactions',
        ));
    }

    public function paymentExpired(int $merchantId, string $orderNo): void { /* only in-system, warning */ }
    public function withdrawalRequested(int $merchantId, string $merchantName, string $amount, string $currency): void { /* in-system + Telegram */ }
    public function withdrawalApproved(int $merchantId, string $merchantEmail, string $amount, string $currency): void { /* in-system + email */ }
    public function withdrawalRejected(int $merchantId, string $merchantEmail, string $amount, string $currency, string $reason): void { /* in-system + email */ }
    public function webhookFailed(int $merchantId, string $merchantEmail, string $merchantName, string $webhookUrl, int $attempts): void { /* in-system + Telegram + email */ }
}
```

**Матрица каналов:**

| Событие | In-system | Telegram | Email |
|---|---|---|---|
| Payment received | green | ✓ | ✓ |
| Payment expired | warning | — | — |
| Withdrawal requested | green | ✓ | — |
| Withdrawal approved | green | — | ✓ |
| Withdrawal rejected | red | — | ✓ |
| Webhook failed 3+ | red | ✓ | ✓ |

---

#### 7.5 Application: Notification Endpoints

- [ ] `GET /merchant/notifications?per_page=20` — список (unread первые, sorted by created_at DESC)
- [ ] `GET /merchant/notifications/unread-count` — `{ "count": 5 }` (badge на колокольчике)
- [ ] `POST /merchant/notifications/read-all` — пометить все как прочитанные
- [ ] `PATCH /merchant/notifications/{id}/read` — пометить одну как прочитанную

**Application checklist:**
- [ ] `GetMerchantNotificationsRequestTransfer`, `GetMerchantNotificationsRequest`, `GetMerchantNotificationsAction`, `GetMerchantNotificationsController`
- [ ] `GetUnreadCountMerchantNotificationsAction`, `GetUnreadCountMerchantNotificationsController`
- [ ] `MarkAllReadMerchantNotificationsAction`, `MarkAllReadMerchantNotificationsController`
- [ ] `MarkOneReadMerchantNotificationAction`, `MarkOneReadMerchantNotificationController`

**Response GET /merchant/notifications:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "green",
      "title": "Payment received",
      "message": "Order #ORD-1042 · +249.21 USDT",
      "is_read": false,
      "link_url": "/transactions?order=ORD-1042",
      "created_at": "2026-05-21T09:42:00Z"
    }
  ],
  "unread_count": 5,
  "meta": { "current_page": 1, "per_page": 20, "total": 48 }
}
```

#### 7.6 Integration: откуда вызывается MerchantNotificationService

| Место вызова | Метод |
|---|---|
| `IncomingPaymentAction` (POST /internal/payment/incoming) | `paymentReceived()` |
| `ExpireMerchantPaymentsJob` | `paymentExpired()` |
| `ProcessWithdrawalAction` (POST /merchant/withdrawals) | `withdrawalRequested()` |
| Internal callback: withdrawal approved | `withdrawalApproved()` |
| Internal callback: withdrawal rejected | `withdrawalRejected()` |
| `DispatchMerchantWebhookJob` (при 3+ неудачах) | `webhookFailed()` |

---

### Phase 8 — Settings

**Goal:** Страница настроек мерчанта: бизнес-профиль, безопасность (2FA), KYB-верификация компании.

Навигация в UI (левый сайдбар):
- Профиль продавца
- Безопасность и двухфакторная
- Соответствие требованиям · KYB
- Уведомления (Phase 7)
- Белый список для вывода (Phase 3.3)

---

#### 8.1 Module: MerchantBusinessProfile (own)

Расширенный бизнес-профиль мерчанта. Данные специфичны для merchant-platform, не принадлежат `merchants` (shared).

**Migration: `merchant_business_profiles`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint unique | один мерчант — один профиль |
| `legal_name` | varchar(255) nullable | официальное юридическое название компании |
| `display_name` | varchar(255) nullable | торговое название (DBA) — что видит покупатель |
| `business_type` | varchar(50) nullable | LLC / Corp / ИП / Sole Trader / ООО / Partnership |
| `registration_country_id` | int nullable | FK → countries (shared) |
| `tax_id` | varchar(100) nullable | ИНН / VAT / EIN / Company No (формат зависит от страны) |
| `mcc_code` | varchar(10) nullable | Merchant Category Code (4 цифры, стандарт Visa/MC, e.g. 5499) |
| `business_description` | text nullable | краткое описание бизнеса |
| `created_at` / `updated_at` | | |

> Поле `tax_id` универсальное — один формат для всех стран. Label меняется на фронте по `registration_country_id` (ИНН для RU/CIS, EIN для US, VAT для EU, Company No для UK).

**Module checklist:**
- [ ] Migration `merchant_business_profiles`
- [ ] Model `MerchantBusinessProfile`
- [ ] Entity `MerchantBusinessProfileItemEntity`
- [ ] EntityFactory `MerchantBusinessProfileItemEntityFactory`
- [ ] Presenter `MerchantBusinessProfileEntityPresenter`
- [ ] Contract `MerchantBusinessProfileRepositoryContract`
- [ ] Repository `MerchantBusinessProfileRepository`
- [ ] Criteria: `BusinessProfileByMerchantIdCriteria`
- [ ] Transfers: `StoreMerchantBusinessProfileTransfer`, `UpdateMerchantBusinessProfileTransfer`
- [ ] Handlers: `StoreMerchantBusinessProfileHandler`, `UpdateMerchantBusinessProfileHandler`, `FindMerchantBusinessProfileByCriteriaHandler`
- [ ] ServiceProvider `MerchantBusinessProfileServiceProvider`

**Application API:**
- [ ] `GET /merchant/settings/profile` — получить профиль (merchant + business_profile + country)
- [ ] `PUT /merchant/settings/profile` — обновить бизнес-профиль
- [ ] `POST /merchant/settings/logo` — загрузить логотип (обновляет `merchants.logo`)

**Response:**
```json
{
  "merchant_uuid": "AE399DC9A6959",
  "business_name": "Northwind Coffee Co.",
  "logo": "https://...",
  "status": "Approved",
  "profile": {
    "legal_name": "Northwind Coffee OÜ",
    "display_name": "Northwind Coffee",
    "business_type": "LLC",
    "registration_country": { "id": 68, "name": "Estonia", "code": "EE" },
    "tax_id": "EE102438192",
    "mcc_code": "5499",
    "mcc_label": "Retail - Food & Beverages",
    "business_description": "Specialty coffee roaster shipping single-origin beans worldwide."
  }
}
```

---

#### 8.2 Application: Security & 2FA

Управление двухфакторной аутентификацией. Читает и пишет напрямую в shared DB (`users.google2fa_secret`, `user_details.*`). Пакет `pragmarx/google2fa` устанавливается в merchant-platform.

**Как работает (не ломает uexapp-backend):**
- `users.google2fa_secret` — один секрет, один пользователь. Если мерчант включает 2FA через merchant-platform — в uexapp-backend 2FA тоже включена автоматически (одна таблица).
- Оба проекта используют `user_details.two_step_verification_type` = `'two_fa'` | `'disabled'`
- Оба проекта используют `user_details.two_step_verification_transactions_enabled` для транзакционной 2FA

**Flow включения 2FA:**
```
1. GET /merchant/security/2fa/status
   ← { is_enabled: false, type: "disabled" }

2. POST /merchant/security/2fa/generate
   → generate secret via google2fa->generateSecretKey()
   → сохранить в users.google2fa_secret
   ← { secret: "JBSWY3DPEHPK3PXP", qr_url: "otpauth://totp/..." }
   Фронт показывает QR-код мерчанту → тот сканирует в Google Authenticator

3. POST /merchant/security/2fa/enable { code: "123456" }
   → verify: google2fa->verify(users.google2fa_secret, code)
   → если верно → user_details.two_step_verification_type = 'two_fa'
   ← { success: true }

4. POST /merchant/security/2fa/disable { code: "123456" }
   → verify code → user_details.two_step_verification_type = 'disabled'
   → user_details.two_step_verification_transactions_enabled = false
```

**Транзакционная 2FA (защита вывода):**
- `user_details.two_step_verification_transactions_enabled` = true → при выводе обязателен OTP
- Phase 3.5 уже требует `one_time_password` — он проверяется через `Validated2faAction` (uexapp-backend) при проксировании вывода

**Application checklist:**
- [ ] Установить `pragmarx/google2fa` в composer.json
- [ ] `Google2faService` (`final readonly class`) — обёртка над пакетом: `generateSecret()`, `verify(secret, code): bool`, `getQrUrl(email, secret): string`
- [ ] `GET /merchant/security/2fa/status` → `{ is_enabled, is_txn_2fa_enabled }`
- [ ] `POST /merchant/security/2fa/generate` → генерирует секрет, сохраняет в `users.google2fa_secret`
- [ ] `POST /merchant/security/2fa/enable` → `{ code }` → верифицирует, включает
- [ ] `POST /merchant/security/2fa/disable` → `{ code }` → верифицирует, отключает
- [ ] `POST /merchant/security/2fa/enable-transaction` → `{ code }` → включает транзакционную 2FA
- [ ] `POST /merchant/security/2fa/disable-transaction` → `{ code }` → отключает

> **Зависимость в user_details:** модуль `UserDetail` (Phase 1 — читаем shared `user_details`) потребует расширения Criteria + Handlers для update. Добавить `UpdateUserDetailHandler` + `UserDetailByUserIdCriteria`.

---

#### 8.3 Module: MerchantKybVerification (own)

KYB-верификация компании через SumSub. Полностью в merchant-platform — не трогает uexapp-backend.

**Migration: `merchant_kyb_verifications`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `merchant_id` | bigint unique | один мерчант — одна активная верификация |
| `level` | tinyint | запрошенный уровень (2 или 3) |
| `status` | ENUM | `pending` / `approved` / `rejected` / `on_hold` |
| `kyb_applicant_id` | string nullable | SumSub applicant ID для KYB |
| `rejection_reason` | text nullable | причина отказа от SumSub |
| `verified_at` | timestamp nullable | когда одобрен |
| `created_at` / `updated_at` | | |

**SumSub KYB flow:**
```
1. POST /merchant/kyb/access-token { level: 2 }
   → SumsubClient->createAccessToken(
       userId: merchant.user_id,
       levelName: config('sumsub.levels.kyb_company')  // новый уровень в SumSub панели
     )
   ← { token: "...", expires_at: "..." }
   Фронт открывает SumSub Web SDK с этим токеном

2. SumSub webhook → POST /public/webhooks/sumsub/kyb  (незащищённый, проверка подписью)
   → HandleKybWebhookAction:
     if reviewResult == GREEN:
       UPDATE merchant_kyb_verifications.status = MerchantKybStatusEnum::APPROVED
       UPDATE merchant_kyb_verifications.verified_at = now()
       MerchantNotificationService->kybApproved()
     if reviewResult == RED:
       UPDATE status = MerchantKybStatusEnum::REJECTED, rejection_reason = ...
       MerchantNotificationService->kybRejected()

3. Admin в uexapp-backend видит что KYB approved → может вручную скорректировать fee rate мерчанта через `MerchantFeeRateController`
```

**`.env` merchant-platform:**
```
SUMSUB_API_KEY=         # те же что в uexapp-backend
SUMSUB_API_TOKEN=       # те же что в uexapp-backend
SUMSUB_WEBHOOK_SECRET=  # можно отдельный secret для KYB webhook
```

**`config/sumsub.php` (добавить):**
```php
'levels' => [
    'kyb_company' => env('SUMSUB_KYB_LEVEL', 'company-verification'),
],
```

**Module checklist:**
- [ ] Migration `merchant_kyb_verifications`
- [ ] Enum `MerchantKybStatusEnum` — `pending`, `approved`, `rejected`, `on_hold`
- [ ] Model, Entity, EntityFactory, Presenter
- [ ] Contract + Repository
- [ ] Criteria: `KybVerificationByMerchantIdCriteria`, `KybVerificationByStatusCriteria`
- [ ] Transfers: `StoreKybVerificationTransfer`, `UpdateKybVerificationTransfer`
- [ ] Handlers: `StoreKybVerificationHandler`, `UpdateKybVerificationHandler`, `FindKybVerificationByCriteriaHandler`
- [ ] ServiceProvider `MerchantKybServiceProvider`
- [ ] `SumsubKybClient` (адаптированный `SumsubClient` из uexapp-backend) — только `createAccessToken` + HMAC-verify webhook
- [ ] `HandleKybWebhookAction` — обрабатывает SumSub callback

**Application API:**
- [ ] `GET /merchant/kyb/status` — текущий статус верификации
- [ ] `POST /merchant/kyb/access-token` → `{ level: 2|3 }` → SumSub token для SDK
- [ ] `POST /public/webhooks/sumsub/kyb` — SumSub webhook (no auth, HMAC-verify)

**Response `GET /merchant/kyb/status`:**
```json
{
  "status": "approved",
  "verified_at": "2026-05-12T10:00:00Z",
  "rejection_reason": null
}
```

**Обновить `MerchantNotificationService` (Phase 7.4):**
- [ ] `kybApproved(merchantId, merchantEmail, level)` — in-system green + email
- [ ] `kybRejected(merchantId, merchantEmail, reason)` — in-system red + email

| Событие | In-system | Telegram | Email |
|---|---|---|---|
| KYB approved | green | — | ✓ |
| KYB rejected | red | — | ✓ |

---

#### 8.5 Application: Settings Page (сборный endpoint)

`GET /merchant/settings` — один запрос для загрузки всей страницы настроек:

```json
{
  "profile": { ... },
  "security": {
    "is_2fa_enabled": true,
    "is_txn_2fa_enabled": true
  },
  "kyb": {
    "status": "approved",
    "verified_at": "2026-05-12T10:00:00Z"
  }
}
```

- [ ] `GetMerchantSettingsAction` — batch-fetch: profile + user_details + kyb_verification
- [ ] `GetMerchantSettingsController`

---

## API Route Map (summary)

```
POST   /merchant/auth/exchange        # frontend: backend JWT → merchant JWT
POST   /merchant/oauth2/token         # server-to-server: client_id + secret → merchant JWT

GET    /merchant/profile
PUT    /merchant/settings
POST   /merchant/logo

GET    /merchant/currencies             # все валюты (фиат + крипто) для цены
GET    /merchant/currencies/crypto      # только крипто для выбора валют оплаты
GET    /merchant/currencies/enabled     # крипто которые мерчант принимает
POST   /merchant/currencies/enable
DELETE /merchant/currencies/{id}/disable
GET    /merchant/currencies/manage      # обогащённый грид: is_enabled, volume_7d, min_fee, networks (filter[tab], filter[chain], search)
GET    /merchant/currencies/stats       # счётчики шапки: enabled/total/networks + auto_convert info
GET    /merchant/currencies/auto-convert
PATCH  /merchant/currencies/auto-convert

POST   /merchant/generate-payment-url   # amount + price_currency + crypto_currency → payment_url + rate

GET    /merchant/transactions           # унифицированная лента (Payment + Withdrawal), фильтры по type/status/currency/date
GET    /merchant/transactions/{id}
GET    /merchant/balance

GET    /merchant/withdrawal-destinations
POST   /merchant/withdrawal-destinations
PUT    /merchant/withdrawal-destinations/{id}
DELETE /merchant/withdrawal-destinations/{id}
PATCH  /merchant/withdrawal-destinations/{id}/set-default

POST   /merchant/withdrawals/preview    # dry-run: fee + amount_to_receive + amount_usd
GET    /merchant/withdrawals            # type=WITHDRAWAL, filter[method]=manual|auto
GET    /merchant/withdrawals/{id}
POST   /merchant/withdrawals            # proxy → uexapp-backend (destination_id вместо raw address)

GET    /merchant/auto-settlement
POST   /merchant/auto-settlement
PUT    /merchant/auto-settlement
PATCH  /merchant/auto-settlement/toggle
PATCH  /merchant/auto-settlement/pause

POST   /internal/payment/incoming        # Kafka подтвердил платёж (X-Internal-Key)
# Approval/reject выводов — в админке uexapp-backend, не в merchant platform

GET    /merchant/api-keys
POST   /merchant/api-keys
PUT    /merchant/api-keys/{id}                        # update name/permissions
POST   /merchant/api-keys/{id}/rotate                 # new client_secret (old immediately invalidated)
DELETE /merchant/api-keys/{id}
GET    /merchant/api-keys/{id}/ip-whitelist
POST   /merchant/api-keys/{id}/ip-whitelist
DELETE /merchant/api-keys/{id}/ip-whitelist/{wid}
GET    /merchant/api/rate-limit                       # current Redis usage per endpoint group

GET    /merchant/webhooks
POST   /merchant/webhooks
PUT    /merchant/webhooks/{id}
PATCH  /merchant/webhooks/{id}/toggle
POST   /merchant/webhooks/{id}/test                   # send test ping payload
GET    /merchant/webhooks/{id}/deliveries             # delivery log (filter: success=true|false)
DELETE /merchant/webhooks/{id}

POST   /merchant/test/simulate/payment-received       # test keys only: simulate blockchain confirmation

GET    /merchant/payment-links
POST   /merchant/payment-links
GET    /merchant/payment-links/{id}
PUT    /merchant/payment-links/{id}
PATCH  /merchant/payment-links/{id}/activate
PATCH  /merchant/payment-links/{id}/schedule
PATCH  /merchant/payment-links/{id}/archive
POST   /merchant/payment-links/{id}/duplicate
DELETE /merchant/payment-links/{id}
GET    /merchant/payment-links/templates

GET    /public/payment-links/{slug}              # публичный: данные ссылки для checkout-фронта (no auth)
POST   /public/payment-links/{slug}/initiate     # публичный: покупатель выбрал крипту → создать сессию
GET    /public/sessions/{session_id}/status      # публичный: polling статуса (фронт держит вкладку открытой)

GET    /merchant/analytics/summary       # KPI cards: available_balance, volume, transactions, avg_ticket (blocks[] param)
GET    /merchant/analytics/chart         # Settlement volume chart: series[]=settled|pending|failed + range=24h|7d|30d|all
GET    /merchant/analytics/assets        # Volume by asset breakdown (range param)

GET    /merchant/transactions/kpi       # Today's volume, Tx today, Pending, Failed 24h

GET    /merchant/search?q=...           # Global search: transactions, invoices, customers

GET    /merchant/settings                          # сборный: profile + security + kyb
GET    /merchant/settings/profile
PUT    /merchant/settings/profile
POST   /merchant/settings/logo

GET    /merchant/security/2fa/status
POST   /merchant/security/2fa/generate             # → secret + QR url
POST   /merchant/security/2fa/enable               # { code } → включить
POST   /merchant/security/2fa/disable              # { code } → выключить
POST   /merchant/security/2fa/enable-transaction   # { code } → txn 2FA on
POST   /merchant/security/2fa/disable-transaction  # { code } → txn 2FA off

GET    /merchant/kyb/status                        # level + limits + jurisdictions
POST   /merchant/kyb/access-token                  # { level } → SumSub SDK token
POST   /public/webhooks/sumsub/kyb                 # SumSub callback (HMAC-verify, no auth)

GET    /merchant/notifications          # In-system bell notifications list
GET    /merchant/notifications/unread-count
POST   /merchant/notifications/read-all
PATCH  /merchant/notifications/{id}/read
```

---

## Notes

- Все `/merchant/*` роуты (кроме `/auth/exchange` и `/oauth2/token`) защищены `MerchantAuthMiddleware`
- `/internal/*` роуты защищены отдельным `InternalKeyMiddleware` (header `X-Internal-Key`) — только для вызовов из uexapp-backend
- Shared таблицы — только чтение, без миграций в этом проекте
- Все платежи и балансы — исключительно в крипто-валюте. Фиат используется только как валюта цены при создании платежа
- Курс конвертации фиксируется в момент создания платежа и не меняется
- Идентификация платежа — по адресу (один адрес = один активный платёж). Suffix не используется
- Адреса — глобальный пул (`merchant_payment_addresses`). Назначаются в момент создания платежа, освобождаются при завершении/истечении
- Адрес в пуле привязан к **chain**, не к currency. Один ETH-адрес покрывает все ERC-20 токены (USDT, USDC, ETH и т.д.). Какой именно токен ожидается — хранится в `merchant_address_assignments.currency_id`
- Назначение/освобождение адреса фиксируется в `merchant_address_assignments`
- `wallet_histories` (shared DB) — таблица per **chain** (не per currency). `wallets` — per currency (личные средства пользователя). Это разные таблицы с разными ролями
- При назначении адреса — `INSERT wallet_histories` (shared DB, merchant user_id, chain_id). При освобождении — `DELETE`
- `merchant_balances` — бизнес-средства мерчанта. Полностью отдельны от личных `wallets` пользователя. Не смешивать
- **`merchant_transactions` — единый леджер** движений по балансу мерчанта. Mirror-ит паттерн `transactions` из uexapp-backend: один тип → один enum value, источник через полиморфный `transaction_reference_id`, type-специфичные поля в `metadata` JSON. Расширяется новыми типами без миграций структуры
- `merchant_payments` — таблица **сессий** платежей покупателя (URL, адрес, ожидаемая сумма, жизненный цикл). НЕ ledger. Связь с леджером: `merchant_transactions.transaction_reference_id = merchant_payments.id` для записей с `type = PAYMENT_RECEIVED`
- **Withdrawal flow:** мерчант инициирует через `POST /merchant/withdrawals` → uex-merchant-platform проксирует в `POST /api/internal/merchant/withdrawal/crypto/create` (uexapp-backend) → бекенд резервирует средства (`amount → frozen_amount` на `merchant_balances`) и создаёт `merchant_transactions(type=WITHDRAWAL, status=Pending)`. Approve/reject — в админке uexapp-backend на отдельной странице "Merchant Transactions". Списание `frozen_amount` происходит только после успешного исполнения крипто-перевода
- Вывод **не** проходит через личный `wallets` пользователя — деньги идут напрямую `merchant_balances` → крипто-гейтвей. Бизнес-средства и личные средства полностью изолированы
- `CRYPTO_GATEWAY_URL` и `CRYPTO_GATEWAY_SECRET` — те же что в uexapp-backend
- `sub_verified` в таблице `users` — флаг что KYC через SumSub пройден (пишет uexapp-backend)
- Начинаем с Phase 1, каждую фазу согласовываем перед стартом
- **Live/Test mode:** mode определяется API ключом (`merchant_apps.mode`). `ModeResolverMiddleware` кладёт mode в `app('request.mode')` — весь запрос наследует режим. Test-платежи не используют реальный блокчейн (mock-адрес), test-балансы изолированы в тех же таблицах через колонку `mode`. Вывод в test режиме — запрещён
- **Permissions (scopes):** API ключи имеют scope-массив `["payments", "reports", "payouts"]`. `ApiKeyPermissionMiddleware` проверяет нужный scope на каждом роуте. Пустой ключ без scope — 403
- **Webhook mode:** каждый webhook endpoint привязан к mode (live/test). Test-платёж → только test-webhooks. Payload всегда содержит `"livemode": bool`
- **Webhook auto-disable:** `DispatchMerchantWebhookJob` пересчитывает `success_rate` после каждой доставки (100 последних попыток). Ниже 90% → `status=degraded` (продолжает слать). Ниже 50% → `status=disabled` (пропускает). Auto-recover при возврате >= 90%
- **2FA в merchant-platform:** пакет `pragmarx/google2fa` устанавливается в merchant-platform. Читает/пишет `users.google2fa_secret` и `user_details.two_step_verification_type` напрямую (shared DB). Включение 2FA через merchant-platform автоматически включает её и в uexapp-backend — одна таблица
- **KYB (компания):** SumSub company-level, полностью в merchant-platform. `merchant_kyb_verifications` хранит статус верификации. KYB — бинарный гейт: не прошёл → заблокированы API/webhooks/выводы. После одобрения KYB admin может скорректировать fee rate мерчанта через `uexapp-backend/app/Admin/Http/Controllers/Merchant/MerchantFeeRateController`
- **Fee rates:** per-merchant ставки в `merchant_fee_rates`. Цепочка разрешения: per-currency override → global per-merchant → config default. Admin управляет ставками через admin-панель (не через merchant_groups). При входящем платеже fee всегда платит мерчант (вычитается из зачисляемой суммы). При выводе — `fees_limits` type=`merchant_withdrawal`
