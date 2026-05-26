# Merchant Platform — API Reference

**Base URL:** `https://merchant.uex.com`  
**Auth:** Bearer JWT (merchant token) or OAuth2 client credentials  
**Format:** `Content-Type: application/json`

---

## Authentication

### `POST /merchant/auth/exchange`

Exchange a uexapp-backend JWT for a merchant platform JWT.

**Use case:** Frontend user logs in via uexapp-backend, then exchanges that token to access merchant cabinet.

**Request:**
```json
{ "token": "<backend_jwt>" }
```

**Response `200`:**
```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Errors:**
- `401` — invalid or expired backend JWT
- `403` — user has no merchant record, or merchant status is not Approved

---

### `POST /merchant/oauth2/token`

Server-to-server auth using API key credentials.

**Use case:** Merchant's backend server authenticates to call generate-payment-url, check status, etc.

**Request:**
```json
{
  "client_id": "client_abc123",
  "client_secret": "secret_xyz...",
  "grant_type": "client_credentials"
}
```

**Response `200`:**
```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 3600,
  "scope": ["payments"]
}
```

**Errors:**
- `401` — invalid credentials
- `403` — app suspended

---

## Profile

### `GET /merchant/profile`

Returns merchant profile data.

**Auth:** Bearer token  
**Response `200`:**
```json
{
  "id": 1,
  "merchant_uuid": "uuid-xxx",
  "business_name": "Acme Shop",
  "site_url": "https://acme.com",
  "logo": "https://cdn.uex.com/logos/acme.png",
  "status": "Approved"
}
```

---

## Currencies

### `GET /merchant/currencies`

All currencies (fiat + crypto) — for selecting price currency.

### `GET /merchant/currencies/crypto`

Crypto-only currencies — for selecting payment currency.

### `GET /merchant/currencies/enabled`

Crypto currencies the merchant has enabled for receiving payments.

### `POST /merchant/currencies/enable`

Enable a crypto currency for receiving payments.

**Request:** `{ "currency_id": 3 }`  
**Response `201`:** `{ "message": "Currency enabled" }`

### `DELETE /merchant/currencies/{id}/disable`

Disable a currency. Fails if there are active pending payments in that currency.

**Response `204`**

### `GET /merchant/currencies/manage`

Enriched currency grid with merchant-specific data.

**Query params:**
- `filter[tab]=all|enabled|disabled`
- `filter[chain]=ERC-20`
- `search=BTC`
- `per_page=20`

**Response item:**
```json
{
  "id": 1,
  "symbol": "BTC",
  "code": "btc",
  "name": "Bitcoin",
  "logo": "...",
  "networks": ["Bitcoin"],
  "confirmations": 3,
  "fee_percent": "1.00",
  "volume_7d": "0.18420000",
  "volume_7d_usd": "108420.00",
  "is_enabled": true
}
```

### `GET /merchant/currencies/stats`

Header counters for the currencies page.

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

### `GET /merchant/currencies/auto-convert`

Get current auto-convert setting.

### `PATCH /merchant/currencies/auto-convert`

Update auto-convert setting.

**Request:** `{ "is_enabled": true, "to_currency_id": 2 }`

---

## Payments

### `POST /merchant/generate-payment-url`

Create a payment session. Returns address + amount for checkout.

**Auth:** Bearer token (scope: `payments`)  
**Request:**
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

**How it works:**
1. Convert `amount` from `price_currency` to `crypto_currency` via uexapp-backend exchange rate API
2. Resolve merchant fee % via `ResolveMerchantFeeRateHandler` (per-currency → global → config default)
3. Calculate `fee_amount = net_crypto * fee% / 100`; `gross = net` (buyer pays net, fee deducted from credited amount)
4. Assign crypto address from pool (`merchant_payment_addresses`) or create new via CryptoGatewayService
5. Create `merchant_payments` record with `status = MerchantPaymentStatusEnum::PENDING`
6. Set `expires_at = now + MERCHANT_PAYMENT_TTL_MINUTES`

**Response `200`:**
```json
{
  "payment_url": "https://pay.uex.com/pay/{uuid}",
  "address": "TKHfnNi7CMrnF7ME3gL4BXr1qo9VnvrRcs",
  "crypto_amount": "100.00",
  "net_amount": "100.00",
  "fee_amount": "1.00",
  "fee_percentage": "1.00",
  "currency": "USDT",
  "network": "TRC-20",
  "rate": "1.0002",
  "expires_at": "2026-05-22T15:10:00Z"
}
```

**Notes:**
- `crypto_amount` = gross = what buyer sends
- `fee_amount` is deducted from merchant credited amount, not added on top of buyer's payment
- In test mode: `address` = mock (`test_mock_<hex>`), no real blockchain

---

## Internal Endpoints (secured with X-Internal-Key)

### `POST /internal/payment/incoming`

Called by uexapp-backend when blockchain confirms a payment to an address in the pool.

**Header:** `X-Internal-Key: <secret>`  
**Request:**
```json
{
  "address": "TKHfnNi7CMrnF7ME3gL4BXr1qo9VnvrRcs",
  "amount": "100.00000000",
  "tx_hash": "0xabc123...",
  "currency_id": 3,
  "confirmations": 12,
  "network": "TRC-20"
}
```

**How it works:**
1. Find `merchant_payments` by `gateway_reference = address` AND `status = PENDING`
2. Read fixed `fee_amount` from `merchant_payments.fee`
3. `net_credited = received - fee_amount`
4. Update `merchant_payments.status = SUCCESS`
5. Create `merchant_transactions` (type=`PAYMENT_RECEIVED`, status=`SUCCESS`)
6. Credit `net_credited` to `merchant_balances`
7. Release address assignment, free address back to pool
8. Dispatch `DispatchMerchantWebhookJob`
9. Check auto-convert setting → dispatch `AutoConvertJob` if enabled

---

## Transactions

### `GET /merchant/transactions`

Unified ledger — payments received + withdrawals in one list.

**Query params:**
- `filter[type]=payment|withdrawal`
- `filter[status]=Success|Pending|Failed`
- `filter[currency_id]=1,2,3`
- `filter[date_from]=2026-05-01`
- `filter[date_to]=2026-05-31`
- `filter[search]=<order_no|uuid|tx_hash>`
- `per_page=12` (default)

**Response item:**
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
  "transaction_type_id": 1,
  "created_at": "2026-05-21T09:42:00Z"
}
```

### `GET /merchant/transactions/{id}`

Full transaction detail for side panel.

**Response** additionally includes: `tx_hash`, `block_explorer_url`, `gross_received`, `gross_expected`, `network_fee`, `fee_percentage`, `net_credited`, `confirmations`, `required_confirmations`.  
For withdrawal: `recipient`, `destination_tag`, `processed_at`, `rejected_reason`.

### `GET /merchant/transactions/kpi`

Real-time KPI counters for the transactions page.

```json
{
  "today_volume": "28406.14",
  "today_volume_delta_pct": "+12.43",
  "transactions_today": 147,
  "pending": 3,
  "failed_24h": 2,
  "failed_24h_pct_of_volume": "0.4"
}
```

---

## Balance

### `GET /merchant/balance`

Balances by all currencies (live mode by default).

```json
{
  "balances": [
    {
      "currency": { "symbol": "USDT", "code": "usdt" },
      "amount": "4820.00000000",
      "frozen_amount": "500.00000000",
      "available": "4320.00000000"
    }
  ]
}
```

---

## Withdrawals

### `POST /merchant/withdrawals/preview`

Dry-run fee calculation before submitting withdrawal. No DB writes.

**Request:** `{ "currency_id": 10, "amount": "10000.00000000", "destination_id": 1 }`

**Response:**
```json
{
  "amount_to_send": "10000.00000000",
  "currency": "USDT",
  "network": "ERC-20",
  "destination": { "id": 1, "label": "Treasury Wallet", "address": "0x8a31...AB29" },
  "fee_amount": "2.10000000",
  "fee_percentage": "0.00",
  "fee_fixed": "2.10",
  "amount_to_receive": "9997.90000000",
  "amount_usd": "9997.90",
  "estimated_arrival": "~30 seconds"
}
```

### `GET /merchant/withdrawals`

List of withdrawal transactions.

### `GET /merchant/withdrawals/{id}`

Withdrawal detail.

### `POST /merchant/withdrawals`

Submit withdrawal request. Requires 2FA.

**Request:**
```json
{
  "currency_id": 10,
  "amount": "10000.00000000",
  "destination_id": 1,
  "one_time_password": "999999"
}
```

**How it works:**
1. Validate merchant auth + 2FA code
2. Verify `destination_id` belongs to merchant
3. Recalculate fee (same as preview)
4. Check `amount + fee ≤ available_balance`
5. HTTP POST to uexapp-backend `/api/internal/merchant/withdrawal/crypto/create`
6. Returns `{ transaction_id, status: "Pending" }`

**Blocked in test mode** → `422 TestModeOperationException`

---

## Withdrawal Destinations

### `GET /merchant/withdrawal-destinations`
### `POST /merchant/withdrawal-destinations`

**Request:** `{ "label": "Treasury", "network": "ERC-20", "address": "0x...", "destination_tag": null }`

### `PUT /merchant/withdrawal-destinations/{id}`
### `DELETE /merchant/withdrawal-destinations/{id}`
### `PATCH /merchant/withdrawal-destinations/{id}/set-default`

---

## API Keys

### `GET /merchant/api-keys`
### `POST /merchant/api-keys`

**Request:** `{ "name": "Production Server", "mode": "live", "permissions": ["payments"] }`  
**Note:** `client_secret` returned **only once** at creation.

### `PUT /merchant/api-keys/{id}`
### `POST /merchant/api-keys/{id}/rotate`

Generates new `client_secret`. Old secret invalidated immediately.

### `DELETE /merchant/api-keys/{id}`
### `GET /merchant/api-keys/{id}/ip-whitelist`
### `POST /merchant/api-keys/{id}/ip-whitelist`

**Request:** `{ "cidr": "192.168.1.0/24", "label": "Office" }`

### `DELETE /merchant/api-keys/{id}/ip-whitelist/{whitelistId}`
### `GET /merchant/api/rate-limit`

---

## Webhooks

### `GET /merchant/webhooks`
### `POST /merchant/webhooks`

**Request:** `{ "name": "Production", "url": "https://shop.example.com/webhooks/uex", "mode": "live", "events": ["payment.settled"], "secret": "optional" }`

### `PUT /merchant/webhooks/{id}`
### `PATCH /merchant/webhooks/{id}/toggle`
### `DELETE /merchant/webhooks/{id}`
### `POST /merchant/webhooks/{id}/test`

Sends test ping payload to webhook URL.

### `GET /merchant/webhooks/{id}/deliveries`

Delivery log (last 100 attempts).

**Query:** `filter[success]=true|false`, `per_page`

---

### Webhook Payload (sent to merchant)

```json
{
  "event": "payment.settled",
  "livemode": true,
  "order_no": "ORD-777",
  "status": "Success",
  "gross_expected": "100.00000000",
  "gross_received": "100.00000000",
  "net_credited": "99.00000000",
  "fee_amount": "1.00000000",
  "difference": "0.00000000",
  "is_exact": true,
  "currency": "USDT",
  "network": "TRC-20",
  "tx_hash": "0xabc123...",
  "created_at": "2026-05-21T09:42:00Z"
}
```

**Signature header:** `X-UEX-Signature: sha256=<hmac_hex>`  
**livemode:** `false` for test mode payments

---

## Test Mode / Sandbox

### `POST /merchant/test/simulate/payment-received`

Simulate blockchain confirmation for a test payment.

**Auth:** Bearer token with test API key only (mode=test, otherwise 403)  
**Request:**
```json
{
  "payment_id": "pay_uuid_xxx",
  "amount": "100.000000"
}
```

Triggers same logic as `POST /internal/payment/incoming` but with `mode=test`.

---

## Settings

### `GET /merchant/settings`

Single request to load the full settings page.

```json
{
  "profile": { "legal_name": "...", "display_name": "...", "business_type": "LLC", ... },
  "security": { "is_2fa_enabled": true, "is_txn_2fa_enabled": true },
  "kyb": { "status": "approved", "verified_at": "2026-05-12T10:00:00Z" }
}
```

### `PUT /merchant/settings`
### `POST /merchant/logo`

### `GET /merchant/security/2fa/status`
### `POST /merchant/security/2fa/generate`
### `POST /merchant/security/2fa/enable`
### `POST /merchant/security/2fa/disable`
### `POST /merchant/security/2fa/enable-transaction`
### `POST /merchant/security/2fa/disable-transaction`

### `GET /merchant/kyb/status`
### `POST /merchant/kyb/access-token`

**Request:** `{ "level": 2 }` → Returns SumSub SDK token.

---

## Public Checkout Endpoints (no auth)

### `GET /public/payment-links/{slug}`
### `POST /public/payment-links/{slug}/initiate`
### `GET /public/sessions/{session_id}/status`
### `POST /public/webhooks/sumsub/kyb`

SumSub KYB webhook — no auth, HMAC-verified via `X-App-Token` header.

---

## Analytics

### `GET /merchant/analytics/summary`

Dashboard KPI cards.

**Query:** `blocks[]=available_balance&blocks[]=volume&blocks[]=transactions&blocks[]=avg_ticket`

### `GET /merchant/analytics/chart`

Settlement volume chart with series.

**Query:** `range=24h|7d|30d|all`, `series[]=settled&series[]=pending&series[]=failed`

---

## Payment Links

### `GET /merchant/payment-links`
### `POST /merchant/payment-links`
### `GET /merchant/payment-links/{id}`
### `PUT /merchant/payment-links/{id}`
### `PATCH /merchant/payment-links/{id}/activate`
### `PATCH /merchant/payment-links/{id}/schedule`
### `PATCH /merchant/payment-links/{id}/archive`
### `POST /merchant/payment-links/{id}/duplicate`
### `DELETE /merchant/payment-links/{id}`
### `GET /merchant/payment-links/templates`

---

## Auto Settlement

### `GET /merchant/auto-settlement`
### `POST /merchant/auto-settlement`
### `PUT /merchant/auto-settlement`
### `PATCH /merchant/auto-settlement/toggle`
### `PATCH /merchant/auto-settlement/pause`
