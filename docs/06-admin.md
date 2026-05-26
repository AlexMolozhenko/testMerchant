# Admin Panel — Merchant Management

**Location:** `uexapp-backend/app/Admin`  
**Purpose:** All administrative functionality for the merchant platform is implemented here — NOT in merchant-platform itself.

---

## Principle

The merchant-platform project has no Admin layer. Every admin operation (merchant withdrawals, fee rate management, KYB review, merchant approval) is built in `uexapp-backend/app/Admin` following the existing Admin layer patterns of that project.

New admin features follow the same structure as existing ones (e.g., `SavingRatesController`, `MerchantController`):
- Controller: `app/Admin/Http/Controllers/Merchant/`
- Actions: `app/Admin/Actions/Merchant/`
- Requests: `app/Admin/Http/Requests/Merchant/`
- Transfers: `app/Admin/Transfers/Requests/Merchant/`
- Views: `resources/views/admin/merchant/`

---

## Sections

> Filled progressively as roadmap phases are implemented.

---

### Merchant Withdrawals *(Phase 3.7)*

**Purpose:** Admin approves or rejects merchant withdrawal requests. Works with `merchant_transactions` (type=WITHDRAWAL) and `merchant_balances` from the shared DB.

#### Controller

**File:** `app/Admin/Http/Controllers/Merchant/MerchantTransactionController.php`

| Method | Route | Description |
|---|---|---|
| `index()` | `GET /admin/merchant-transactions` | List all merchant transactions with filters (merchant, type, status, date range) |
| `show()` | `GET /admin/merchant-transactions/{id}` | Full detail of one transaction including metadata |
| `approve()` | `POST /admin/merchant-transactions/{id}/approve` | Approve a WITHDRAWAL — executes crypto transfer |
| `reject()` | `POST /admin/merchant-transactions/{id}/reject` | Reject a WITHDRAWAL — releases frozen balance back |

#### Approve flow

Only valid for: `transaction_type_id = MerchantTransactionTypeEnum::WITHDRAWAL`, `status = MerchantTransactionStatusEnum::PENDING`

```
1. Load merchant_transaction by ID
2. Validate type=WITHDRAWAL, status=PENDING
3. Execute crypto transfer via CreateExternalWithdrawalCryptoAction (or Internal)
   — uses merchant's user_id, recipient from metadata, amount from transaction
4. On success:
   - merchant_transactions.status = MerchantTransactionStatusEnum::COMPLETED
   - metadata += { tx_hash, admin_id, processed_at }
   - merchant_balances.frozen_amount -= (amount + fee_amount)
   - Dispatch webhook event: MerchantWebhookEventEnum::PAYOUT_COMPLETED
5. On failure:
   - merchant_transactions.status = MerchantTransactionStatusEnum::FAILED
   - metadata += { error }
   - merchant_balances.frozen_amount → amount (funds returned)
```

#### Reject flow

```
1. merchant_transactions.status = MerchantTransactionStatusEnum::REJECTED
2. metadata += { admin_id, rejected_reason, processed_at }
3. merchant_balances.frozen_amount -= amount → amount += amount (unfreeze)
4. Dispatch webhook event: MerchantWebhookEventEnum::PAYOUT_FAILED
```

#### Actions

| Class | File | Method | Purpose |
|---|---|---|---|
| `MerchantTransactionViewAction` | `Actions/Merchant/MerchantTransactionViewAction.php` | `execute(): array` | Returns DataTables config + column heads |
| `MerchantTransactionApproveAction` | `Actions/Merchant/MerchantTransactionApproveAction.php` | `execute(int $id): void` | Runs approval flow |
| `MerchantTransactionRejectAction` | `Actions/Merchant/MerchantTransactionRejectAction.php` | `execute(int $id, string $reason): void` | Runs rejection flow |

---

### Merchant Fee Rates *(Phase 3.9)*

**Purpose:** Admin sets per-merchant fee percentages. Analogue of `SavingRatesController`. Supports global per-merchant rate and per-currency overrides.

#### How fee resolution works

```
ResolveMerchantFeeRateHandler::execute(merchantId, currencyId):
  1. merchant_fee_rates WHERE merchant_id=? AND currency_id=?    → per-currency rate
  2. merchant_fee_rates WHERE merchant_id=? AND currency_id IS NULL → global merchant rate
  3. config('merchant-config.default_fee_percent')                → platform default
```

#### Controller

**File:** `app/Admin/Http/Controllers/Merchant/MerchantFeeRateController.php`

| Method | Route | Description |
|---|---|---|
| `index()` | `GET /admin/merchants/{id}/fee-rates` | Show current rates: global + per-currency list + config default |
| `upsert()` | `POST /admin/merchants/{id}/fee-rates` | Create or update a rate (global if currency_id=null, per-currency otherwise) |
| `destroy()` | `DELETE /admin/merchants/{id}/fee-rates/{rateId}` | Delete a per-currency override (global rate cannot be deleted) |

#### Requests

| Class | Validates |
|---|---|
| `MerchantFeeRateUpsertRequest` | `currency_id`: int\|null; `percent`: decimal 0–100 |

#### Actions

| Class | Method | Purpose |
|---|---|---|
| `MerchantFeeRateViewAction` | `execute(int $merchantId): array` | Loads global rate + per-currency list + config default for view |
| `MerchantFeeRateUpsertAction` | `execute(MerchantFeeRateUpsertTransfer $t): void` | upsert via `merchant_fee_rates` (merchant_id + currency_id unique) |
| `MerchantFeeRateDeleteAction` | `execute(int $rateId): void` | Delete per-currency row; throws if it's the global row |

#### Transfers

| Class | Properties |
|---|---|
| `MerchantFeeRateUpsertTransfer` | `int $merchantId`, `?int $currencyId`, `string $percent` |

---

### KYB Review *(Phase 8.3)*

**Purpose:** After merchant completes SumSub KYB verification, admin can review the outcome and optionally adjust the merchant's fee rate.

**Note:** KYB webhook from SumSub is handled directly by merchant-platform (`POST /public/webhooks/sumsub/kyb`) and updates `merchant_kyb_verifications`. Admin in uexapp-backend only has a read view + can trigger fee rate changes.

#### Controller

**File:** `app/Admin/Http/Controllers/Merchant/MerchantKybController.php`

| Method | Route | Description |
|---|---|---|
| `index()` | `GET /admin/merchant-kyb` | List all KYB verifications with status filter |
| `show()` | `GET /admin/merchant-kyb/{merchantId}` | KYB detail for one merchant |

#### Actions

| Class | Method | Purpose |
|---|---|---|
| `MerchantKybViewAction` | `execute(): array` | DataTables config for KYB list |

---

*Document updated as phases are implemented. Add new admin sections below following the same pattern.*
