# Database Schema

**Project:** uex-merchant-platform  
**Author:** Molozhenko  
**DB:** MariaDB — shared with uexapp-backend (single instance)

---

## Ownership Rules

| Type | Who owns | Migrations |
|---|---|---|
| **Shared tables** | uexapp-backend | No migrations in merchant-platform |
| **Own tables** | merchant-platform | Migrations in `database/migrations/` |
| **Extended shared tables** | Joint (ALTER TABLE) | Migration in merchant-platform adds columns |

---

## Shared Tables (read-only / extended)

Tables that belong to uexapp-backend. merchant-platform reads them and in some cases adds columns via `ALTER TABLE`.

---

### `users`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Identify merchant owner by `user_id`. Read verification flags for KYB/2FA logic.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `email` | varchar | Used for notifications, auth lookup |
| `google2fa_secret` | varchar nullable | 2FA seed — read/write by merchant-platform via pragmarx/google2fa |
| `sub_verified` | bool | SumSub individual KYC passed |
| `identity_verified` | bool | Identity verification flag |
| `applicant_id` | varchar nullable | SumSub applicant ID (individual KYC) |

**Example row:**
```
id=42, email="shop@acme.com", sub_verified=true, identity_verified=true
```

---

### `user_details`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Read/write 2FA type for merchant. Both projects share the same row.

| Column | Type | Notes |
|---|---|---|
| `user_id` | bigint | FK → users |
| `two_step_verification_type` | varchar | `'two_fa'` \| `'disabled'` |
| `two_step_verification_transactions_enabled` | bool | 2FA required for transactions |

---

### `merchants`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Core merchant profile. Read business_name, site_url, status, logo. `status` controls access (only `Approved` merchants can use the platform).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | bigint | FK → users |
| `merchant_uuid` | varchar | Public UUID for payment URLs |
| `business_name` | varchar | Display name |
| `site_url` | varchar nullable | Merchant website |
| `fee` | decimal | Legacy fee column — NOT used by merchant-platform (we use `merchant_fee_rates`) |
| `status` | varchar | `Moderation` \| `Disapproved` \| `Approved` |
| `logo` | varchar nullable | Logo file path |
| `merchant_group_id` | int | FK → merchant_groups (read-only reference) |

**Example row:**
```
id=1, user_id=42, merchant_uuid="a1b2c3d4-...", business_name="Acme Shop",
status="Approved", merchant_group_id=2
```

---

### `merchant_groups`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Read-only reference for display. Fee and fee_bearer columns are **not used** — merchant-platform uses `merchant_fee_rates` instead.

| Column | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `name` | varchar | "Premium", "Basic", "Default" |
| `fee` | decimal | Legacy — not used by merchant-platform |
| `fee_bearer` | varchar | Legacy — not used by merchant-platform |
| `is_default` | bool | Default group for new merchants |

---

### `merchant_apps`

**Owner:** uexapp-backend (extended by merchant-platform)  
**Usage in merchant-platform:** OAuth2 credentials. Merchant creates API keys (live/test mode, scopes). Used for server-to-server auth.

**Base columns (uexapp-backend):**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | FK → merchants |
| `client_id` | varchar | Public key identifier |
| `client_secret` | varchar | Hashed secret |

**Added by merchant-platform (ALTER TABLE migration):**

| Column | Type | Notes |
|---|---|---|
| `name` | varchar(255) | "Production Server", "Mobile App" |
| `mode` | ENUM('live','test') | `MerchantAppModeEnum` |
| `permissions` | JSON | `["payments"]`, `["payments","payouts"]`, `["reports"]` |
| `rate_limit_per_minute` | int nullable | NULL = global default (60/min) |
| `status` | ENUM('active','suspended') | `MerchantAppStatusEnum` |
| `last_used_at` | timestamp nullable | Updated on each authenticated request |

**Example row:**
```
id=1, merchant_id=1, name="Production Server", client_id="client_abc123",
mode="live", permissions=["payments"], status="active", last_used_at="2026-05-21 09:42:00"
```

---

### `merchant_payments`

**Owner:** uexapp-backend (extended by merchant-platform)  
**Usage in merchant-platform:** Core payment session. Created when merchant calls `POST /merchant/generate-payment-url`. Tracks status from Pending → Success/Expired/Blocked.

**Base columns (uexapp-backend):**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | FK → merchants |
| `currency_id` | int | Price currency |
| `payment_method_id` | int | Crypto currency used for payment |
| `user_id` | bigint nullable | Buyer user ID (if registered) |
| `gateway_reference` | varchar | Crypto address assigned to this payment |
| `order_no` | varchar | Merchant's order reference |
| `item_name` | varchar | Product/service name |
| `uuid` | varchar | Payment session UUID (used in payment URL) |
| `amount` | decimal | Original amount in price currency (e.g. 100.00 USD) |
| `total` | decimal | Crypto amount buyer must send (gross = net for our fee model) |
| `fee` | decimal | Fee amount in crypto — fixed at creation time |
| `percentage` | decimal | Exchange rate at creation time |
| `status` | varchar | `MerchantPaymentStatusEnum`: Pending / Success / Expired / Blocked / Refund |
| `parent_payment_id` | bigint nullable | Reference to original payment (for top-ups) |
| `metadata` | JSON | `{ net_amount, fee_amount, fee_percentage, gross_amount }` |

**Added by merchant-platform (ALTER TABLE migration):**

| Column | Type | Notes |
|---|---|---|
| `expires_at` | timestamp nullable | Payment TTL — set at creation |
| `mode` | ENUM('live','test') | `MerchantAppModeEnum` — isolates test payments |
| `payment_link_id` | bigint nullable | FK → merchant_payment_links (if initiated via link) |
| `source` | varchar nullable | `'api'` \| `'payment_link'` |

**Example row:**
```
id=100, merchant_id=1, order_no="ORD-777", uuid="sess_abc123",
gateway_reference="TKHfnNi7...", amount=100.00, total=100.00, fee=1.00,
status="Pending", mode="live", expires_at="2026-05-21 11:10:00",
metadata={"net_amount":"100.00","fee_amount":"1.00","fee_percentage":"1.00","gross_amount":"100.00"}
```

---

### `currencies`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Lookup currency data (symbol, code, name, logo, rate for USD conversion). Used in every flow involving amounts.

| Column | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `symbol` | varchar | "BTC", "USDT", "ETH" |
| `code` | varchar | "btc", "usdt" (lowercase) |
| `name` | varchar | "Bitcoin", "Tether" |
| `logo` | varchar | CDN URL |
| `status` | int | 1 = active |
| `rate` | decimal | Current rate in USD |
| `type` | varchar | `'crypto'` \| `'fiat'` \| `'metal'` |

---

### `fees_limits`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Read-only. Used **only for withdrawal fee calculation** (`metadata->type = FeeLimitMetadataTypeEnum::MERCHANT_WITHDRAWAL`). NOT used for incoming payment fees (those come from `merchant_fee_rates`).

| Column | Type | Notes |
|---|---|---|
| `id` | int PK | |
| `currency_id` | int | Which currency this fee applies to |
| `charge_percentage` | decimal | Fee % |
| `charge_fixed` | decimal | Fixed fee amount |
| `min_limit` | decimal | Minimum transaction amount |
| `max_limit` | decimal | Maximum transaction amount |
| `status` | int | 1 = active |
| `metadata` | JSON | `{ "type": "merchant_withdrawal" }` — type discriminator |

**Example row:**
```
id=5, currency_id=3 (USDT), charge_percentage=0.00, charge_fixed=2.10,
status=1, metadata={"type":"merchant_withdrawal"}
```

---

### `wallet_histories`

**Owner:** uexapp-backend  
**Usage in merchant-platform:** Write on address assignment (`INSERT`), delete on release (`DELETE`). Links crypto address to merchant's user account in the shared blockchain tracking system.

| Column | Type | Notes |
|---|---|---|
| `user_id` | bigint | merchant.user_id |
| `chain_id` | int | Blockchain network ID |
| `address` | varchar | Crypto address |

---

## Own Tables (merchant-platform owns)

Tables created by merchant-platform migrations. Full ownership.

---

### `merchant_payment_addresses`

**Purpose:** Global pool of crypto addresses. Not bound to any specific merchant — addresses are rented temporarily via `merchant_address_assignments`. One address covers all tokens on the same chain (ETH address handles USDT ERC-20, USDC, ETH).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `chain_id` | int | Blockchain network (ETH, TRX, BTC…) |
| `address` | varchar | Crypto address string |
| `status` | varchar | `'free'` \| `'occupied'` |
| `created_at` | timestamp | |

**Example row:**
```
id=1, chain_id=2 (TRC-20), address="TKHfnNi7CMrnF7ME3gL4BXr1qo9VnvrRcs", status="free"
```

**Lifecycle:**
- `free` → available for assignment
- `occupied` → currently assigned to an active payment; released when payment completes/expires

---

### `merchant_address_assignments`

**Purpose:** Active mapping between a crypto address and a specific payment session. One address can only be assigned once at a time (unique index on `address_id`).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | Which merchant |
| `currency_id` | int | Which token is expected (USDT vs ETH on same ETH address) |
| `address_id` | bigint UNIQUE | FK → merchant_payment_addresses |
| `merchant_payment_id` | bigint | FK → merchant_payments |
| `assigned_at` | timestamp | |

**Example row:**
```
id=1, merchant_id=1, currency_id=3 (USDT TRC-20), address_id=1, merchant_payment_id=100
```

**Lifecycle:** Inserted when payment is created. Deleted when payment reaches terminal state (Success/Expired).

---

### `merchant_enabled_currencies`

**Purpose:** List of crypto currencies a merchant has enabled for receiving payments. Separate from address management — just a preference list.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `currency_id` | int | FK → currencies |
| `created_at` | timestamp | |

Unique index: `(merchant_id, currency_id)`

**Example row:**
```
id=1, merchant_id=1, currency_id=3 (USDT TRC-20)
id=2, merchant_id=1, currency_id=1 (BTC)
```

---

### `merchant_balances`

**Purpose:** Per-merchant, per-currency balance ledger. `amount` = available funds. `frozen_amount` = funds locked for pending withdrawals. `available = amount - frozen_amount`.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `currency_id` | int | FK → currencies |
| `amount` | decimal(20,8) | Total balance (available + frozen) |
| `frozen_amount` | decimal(20,8) | Locked for pending/approved withdrawals |
| `mode` | ENUM('live','test') | Live and test balances are separate rows |
| `updated_at` | timestamp | |

Unique index: `(merchant_id, currency_id, mode)`

**Example row:**
```
merchant_id=1, currency_id=3, amount="4820.00000000", frozen_amount="500.00000000", mode="live"
```

**How balances change:**
- `PAYMENT_RECEIVED` confirmed → `amount += net_credited`
- Withdrawal created → `amount -= (amount+fee)`, `frozen_amount += (amount+fee)`
- Withdrawal completed → `frozen_amount -= (amount+fee)` (funds left the account)
- Withdrawal rejected → `frozen_amount -= (amount+fee)`, `amount += (amount+fee)` (unfreeze)

---

### `merchant_transactions`

**Purpose:** Unified ledger of all balance movements. One table for all types: payments received, withdrawals, conversions, refunds. Mirrors structure of `transactions` in uexapp-backend.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `currency_id` | int | FK → currencies |
| `uuid` | varchar | Internal transaction ID (shown as "tx_3kj21x") |
| `transaction_type_id` | int | `MerchantTransactionTypeEnum` — 1=PAYMENT_RECEIVED, 2=WITHDRAWAL, 7=CONVERSION |
| `transaction_reference_id` | bigint nullable | Polymorphic source: `merchant_payments.id` for PAYMENT_RECEIVED; NULL for WITHDRAWAL |
| `amount` | decimal(20,8) | Amount credited/debited in `currency_id` |
| `status` | varchar | `MerchantTransactionStatusEnum` |
| `mode` | ENUM('live','test') | Isolates test transactions |
| `metadata` | JSON | Type-specific fields (see below) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes: `(merchant_id, created_at)`, `(merchant_id, transaction_type_id)`, `(transaction_type_id, transaction_reference_id)`

**metadata by type:**

`PAYMENT_RECEIVED`:
```json
{
  "gross_expected": "100.00000000",
  "gross_received": "100.00000000",
  "net_amount":     "100.00000000",
  "fee_amount":     "1.00000000",
  "fee_percentage": "1.00",
  "net_credited":   "99.00000000",
  "difference":     "0.00000000",
  "is_exact":       true,
  "tx_hash":        "0xabc123...",
  "confirmations":  12,
  "network":        "TRC-20",
  "network_fee":    null
}
```

`WITHDRAWAL`:
```json
{
  "recipient":       "0x8a31...AB29",
  "destination_tag": null,
  "network":         "ERC-20",
  "fee_amount":      "2.10000000",
  "amount_usd":      "9997.90",
  "method":          "manual",
  "destination_id":  1,
  "initiated_by_user_id": 42,
  "admin_id":        7,
  "tx_hash":         "0xdef456...",
  "processed_at":    "2026-05-21T10:00:00Z"
}
```

`CONVERSION`:
```json
{
  "from_currency_id": 1,
  "from_amount":      "0.00182000",
  "to_currency_id":   3,
  "to_amount":        "99.00000000",
  "rate":             "54395.60",
  "exchange_commission": "0.00"
}
```

---

### `merchant_webhooks`

**Purpose:** Webhook endpoints configured by merchant. Each webhook is bound to a mode (live/test) and listens to specific event types. Auto-disabled if success_rate drops below 50%.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `name` | varchar(255) | "Production", "Staging" |
| `url` | varchar(500) | HTTPS endpoint |
| `secret` | varchar | HMAC-SHA256 signing secret |
| `mode` | ENUM('live','test') | `MerchantWebhookModeEnum` |
| `events` | JSON | `["payment.settled","payout.completed"]` — `MerchantWebhookEventEnum` values |
| `status` | ENUM('active','degraded','disabled') | `MerchantWebhookStatusEnum` |
| `success_rate` | decimal(5,2) | % successful deliveries over last 100 attempts |
| `is_active` | bool | Merchant's manual on/off toggle |
| `created_at` / `updated_at` | | |

**Status transitions:**
- `success_rate >= 90%` AND `is_active=true` → `active`
- `success_rate < 90%` → `degraded` (deliveries continue, merchant sees warning)
- `success_rate < 50%` OR `is_active=false` → `disabled` (deliveries skipped)

---

### `merchant_webhook_logs`

**Purpose:** Delivery log for each webhook attempt. Stores response code, body, duration. Used to calculate `success_rate` and display delivery history to merchant.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `webhook_id` | bigint | FK → merchant_webhooks |
| `event_type` | varchar | `MerchantWebhookEventEnum` value |
| `session_id` | varchar nullable | `merchant_payments.uuid` |
| `payload` | JSON | Full payload sent |
| `response_code` | int nullable | HTTP status received (200, 500, null=timeout) |
| `response_body` | text nullable | First 1000 chars of response |
| `duration_ms` | int nullable | Round-trip time |
| `attempt` | int | 1 / 2 / 3 (retry number) |
| `success` | bool | `response_code` in 200–299 |
| `delivered_at` | timestamp nullable | |
| `created_at` | timestamp | |

---

### `merchant_fee_rates`

**Purpose:** Per-merchant fee percentages. Resolution chain: per-currency → global per-merchant → `config('merchant-config.default_fee_percent')`. Managed by admin in uexapp-backend Admin panel.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | Which merchant |
| `currency_id` | int nullable | NULL = global rate for this merchant; int = per-currency override |
| `percent` | decimal(5,2) | Fee percentage e.g. `1.00`, `0.50` |
| `created_at` / `updated_at` | | |

Unique index: `(merchant_id, currency_id)`

**Example rows:**
```
merchant_id=1, currency_id=NULL, percent=1.00   ← global rate for merchant 1
merchant_id=1, currency_id=1 (BTC), percent=0.50 ← BTC gets lower rate
merchant_id=2, currency_id=NULL, percent=0.75   ← merchant 2 has negotiated rate
```

**Fee is always paid by merchant** — deducted from `net_credited` at payment receipt, never added on top of buyer's amount.

---

### `merchant_kyb_verifications`

**Purpose:** KYB verification status for each merchant (company-level via SumSub). KYB is a binary gate — approved = full access, otherwise API/webhooks/withdrawals are blocked.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint UNIQUE | One active verification per merchant |
| `level` | tinyint | Requested level (2 or 3) |
| `status` | ENUM | `MerchantKybStatusEnum`: pending / approved / rejected / on_hold |
| `kyb_applicant_id` | varchar nullable | SumSub company applicant ID |
| `rejection_reason` | text nullable | SumSub rejection reason |
| `verified_at` | timestamp nullable | When approved |
| `created_at` / `updated_at` | | |

**Example row:**
```
merchant_id=1, level=2, status="approved", kyb_applicant_id="company_abc123",
verified_at="2026-05-12T10:00:00Z"
```

---

### `merchant_business_profiles`

**Purpose:** Extended business information for merchant (legal name, business type, tax ID, MCC code). Separate from `merchants` shared table — owned entirely by merchant-platform.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint UNIQUE | One profile per merchant |
| `legal_name` | varchar(255) nullable | Legal entity name |
| `display_name` | varchar(255) nullable | Name shown on checkout pages |
| `business_type` | varchar(50) nullable | LLC, ОАО, ООО, Sole trader |
| `registration_country_id` | int nullable | FK → countries |
| `tax_id` | varchar(100) nullable | TIN / VAT number |
| `mcc_code` | varchar(10) nullable | Merchant Category Code e.g. "5499" |
| `business_description` | text nullable | Business description for KYB |
| `created_at` / `updated_at` | | |

---

### `merchant_ip_whitelist`

**Purpose:** IP/CIDR whitelist per API key. If any records exist for an app, only requests from those CIDRs are allowed. Empty list = allow all.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_app_id` | int | FK → merchant_apps |
| `cidr` | varchar | `"192.168.1.0/24"`, `"10.0.0.1/32"` |
| `label` | varchar(255) nullable | "Office", "Production Server" |
| `created_at` | timestamp | |

Index: `(merchant_app_id)`

---

### `merchant_withdrawal_destinations`

**Purpose:** Saved crypto addresses for withdrawal. Merchant adds once, selects from list when withdrawing.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `label` | varchar(255) | "Treasury Wallet", "Cold Storage" |
| `network` | varchar | "ERC-20", "Bitcoin", "TRC-20" |
| `address` | varchar | Crypto address |
| `destination_tag` | varchar nullable | For XRP, XLM, etc. |
| `is_default` | bool | Pre-selected in withdrawal form |
| `status` | varchar | `WithdrawalDestinationStatusEnum`: active / whitelisted |
| `created_at` / `updated_at` | | |

---

### `merchant_auto_settlement_rules`

**Purpose:** Automated withdrawal rule. Triggers on schedule and/or when balance exceeds threshold.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint UNIQUE | One rule per merchant |
| `is_active` | bool | Global ON/OFF |
| `is_paused` | bool | Temporary pause without deletion |
| `trigger_type` | varchar | `AutoSettlementTriggerEnum`: schedule / threshold / both |
| `schedule_time` | varchar nullable | "17:00" UTC |
| `balance_threshold_usd` | decimal nullable | Trigger when balance ≥ this USD value |
| `currency_id` | int | Which currency to settle |
| `destination_id` | bigint | FK → merchant_withdrawal_destinations |
| `created_at` / `updated_at` | | |

---

### `merchant_auto_convert_settings`

**Purpose:** Auto-convert incoming payments to a target currency (e.g., always convert BTC → USDT). Runs after payment is credited, before balance is finalized.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint UNIQUE | One setting per merchant |
| `is_enabled` | bool | ON/OFF toggle |
| `to_currency_id` | int nullable | Target currency (e.g., USDT ERC-20) |
| `created_at` / `updated_at` | | |

---

### `merchant_payment_links`

**Purpose:** Reusable checkout pages with a public slug. Buyer opens link, picks crypto, payment session is created. Supports fixed amount, variable amount, tipping, email collection.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `merchant_id` | bigint | |
| `slug` | varchar UNIQUE | URL segment: `x9a2lq`, `coffee-123` |
| `name` | varchar(255) | "1kg Yirgacheffe Coffee" |
| `amount` | decimal(18,8) nullable | NULL = Variable (buyer enters amount) |
| `price_currency_id` | int | Price currency (USD, EUR) |
| `settle_currency_id` | int nullable | Preferred settlement crypto |
| `accepted_currency_ids` | JSON | `[1, 2, 3]` — allowed crypto currencies |
| `status` | varchar | `MerchantPaymentLinkStatusEnum`: draft / active / scheduled / archived |
| `scheduled_at` | timestamp nullable | Auto-activates at this time |
| `expires_after_minutes` | int | Rate lock TTL per session (default 15) |
| `redirect_url` | varchar(500) nullable | Post-payment redirect (best-effort) |
| `collect_email` | bool | Always true for payment links |
| `show_on_hosted_page` | bool | Show on public merchant page |
| `allow_tipping` | bool | Allow buyer to add tip |
| `is_template` | bool | Saved as template, not published |
| `uses_count` | int | Incremented on each successful payment |
| `created_at` / `updated_at` | | |

---

## Index Summary

| Table | Key Indexes |
|---|---|
| `merchant_payment_addresses` | `(chain_id, status)` |
| `merchant_address_assignments` | UNIQUE `(address_id)` |
| `merchant_enabled_currencies` | UNIQUE `(merchant_id, currency_id)` |
| `merchant_balances` | UNIQUE `(merchant_id, currency_id, mode)` |
| `merchant_transactions` | `(merchant_id, created_at)`, `(merchant_id, transaction_type_id)`, `(transaction_type_id, transaction_reference_id)` |
| `merchant_webhooks` | `(merchant_id, mode, status)` |
| `merchant_webhook_logs` | `(webhook_id, created_at)`, `(webhook_id, success)` |
| `merchant_fee_rates` | UNIQUE `(merchant_id, currency_id)` |
| `merchant_kyb_verifications` | UNIQUE `(merchant_id)` |
| `merchant_business_profiles` | UNIQUE `(merchant_id)` |
| `merchant_ip_whitelist` | `(merchant_app_id)` |
| `merchant_auto_settlement_rules` | UNIQUE `(merchant_id)` |
| `merchant_auto_convert_settings` | UNIQUE `(merchant_id)` |
| `merchant_payment_links` | UNIQUE `(slug)`, `(merchant_id, status)` |

---

*Document updated as new tables are added or existing tables are extended.*
