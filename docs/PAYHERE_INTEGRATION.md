# PayHere Payment Gateway Integration

> **Type:** Integration Guide  
> **Environment:** Sandbox (Testing) + Production  
> **Applies to:** Company Dashboard → Invoices & Purchased Lots

---

## Overview

ecoCycle uses [PayHere](https://www.payhere.lk) as the payment gateway to allow companies to pay their invoices online. The integration uses PayHere's **Checkout API** — a server-side form POST approach where:

1. The backend generates a signed hash and returns checkout params
2. The frontend auto-submits a hidden form to PayHere's hosted payment page
3. PayHere processes the payment and calls our `notify_url` server-to-server
4. Our server verifies the callback signature and updates the invoice status

---

## Architecture

```
Company clicks "Pay with PayHere"
        │
        ▼
POST /api/payhere/checkout/{invoiceId}        ← PayHereController@initiateCheckout
        │  (generates hash, returns payload)
        ▼
Frontend auto-submits hidden form
        │
        ▼
https://sandbox.payhere.lk/pay/checkout      ← PayHere hosted payment page
        │  (customer enters card details)
        ▼
   ┌────┴────────────────┐
   │                     │
   ▼                     ▼
notify_url (server)   return_url (browser)
POST /api/payhere/notify   /company/purchases?payment=success
   │
   ▼
PayHereController@notify
   │  (verifies md5sig, updates invoice)
   ▼
Invoice status → completed
```

---

## Files

| File | Purpose |
|------|---------|
| `src/Services/Payment/PayHereService.php` | Hash generation, payload builder, notification verification |
| `src/Controllers/Api/PayHereController.php` | `initiateCheckout` and `notify` endpoints |
| `src/Views/company/purchases.php` | Frontend — Pay with PayHere button + hidden form |
| `config/routes.php` | Route registration |
| `docker-compose.dev.yml` | Dev Docker environment variables |
| `.env` / `.env.local` | Credentials (read by Docker Compose for substitution) |

---

## Environment Variables

Set these in your `.env` file (Docker Compose reads them for `${VAR}` substitution into the container):

```env
PAYHERE_SANDBOX=true
PAYHERE_MERCHANT_ID=your_sandbox_merchant_id
PAYHERE_MERCHANT_SECRET=your_sandbox_merchant_secret
PAYHERE_NOTIFY_URL=https://your-tunnel.trycloudflare.com/api/payhere/notify
PAYHERE_RETURN_URL=http://localhost:8080/company/purchases?payment=success
PAYHERE_CANCEL_URL=http://localhost:8080/company/purchases?payment=cancelled
```

> **Important:** Because the app runs inside Docker, environment variables must be declared in `docker-compose.dev.yml` under the `environment:` block. The `.env` file on the host is read by Docker Compose to substitute `${PAYHERE_MERCHANT_ID}` etc. — the actual `.env` file is **not** visible to PHP inside the container.

---

## API Routes

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/payhere/checkout/{id}` | Company only | Generates signed checkout payload for a given invoice ID |
| `POST` | `/api/payhere/notify` | None (PayHere server) | Receives and processes PayHere payment notification callback |

### Hash Formula

```
hash = strtoupper(md5(
    merchant_id . order_id . formatted_amount . currency . strtoupper(md5(merchant_secret))
))
```

### Notification Verification (md5sig)

```
local_sig = strtoupper(md5(
    merchant_id . order_id . payhere_amount . payhere_currency . status_code . strtoupper(md5(merchant_secret))
))

valid = (local_sig === md5sig from POST)
```

### Payment Status Codes

| Code | Meaning |
|------|---------|
| `2` | ✅ Success — update invoice to `completed` |
| `0` | ⏳ Pending — wait for final notification |
| `-1` | ❌ Cancelled by customer |
| `-2` | ❌ Failed |
| `-3` | ❌ Chargedback |

---

## Sandbox Setup Guide

### 1. Create a Sandbox Account

1. Go to [sandbox.payhere.lk/merchant/sign-up](https://sandbox.payhere.lk/merchant/sign-up)
2. Register and log in
3. Go to **Side Menu → Integrations**
4. Copy your **Merchant ID**
5. Click **"Add Domain/App"** → enter `localhost` → click **"Request to Allow"**
   - Sandbox approves instantly
6. Copy the **Merchant Secret** shown next to `localhost`

### 2. Set Up a Public Tunnel

PayHere needs to call your `notify_url` server-to-server. Since the app runs on `localhost`, you need a public tunnel.

**Option A — Cloudflare Tunnel (recommended):**
```bash
# Install (one-time)
brew install cloudflared

# Start tunnel — run every time you test
cloudflared tunnel --url http://localhost:8080
# Output: https://proud-duck-abc123.trycloudflare.com
```

**Option B — localhost.run (no install needed):**
```bash
ssh -R 80:localhost:8080 localhost.run
# Output: https://abc123.lhr.life
```

> ⚠️ Each time you restart the tunnel you get a new URL — update `PAYHERE_NOTIFY_URL` in `.env` and restart Docker.

### 3. Update `.env`

```env
PAYHERE_MERCHANT_ID=1234870
PAYHERE_MERCHANT_SECRET=your_actual_secret
PAYHERE_SANDBOX=true
PAYHERE_NOTIFY_URL=https://proud-duck-abc123.trycloudflare.com/api/payhere/notify
PAYHERE_RETURN_URL=http://localhost:8080/company/purchases?payment=success
PAYHERE_CANCEL_URL=http://localhost:8080/company/purchases?payment=cancelled
```

### 4. Restart Docker

```bash
docker compose -f docker-compose.dev.yml down
docker compose -f docker-compose.dev.yml up -d
```

### 5. Test the Flow

1. Log in as a **Company** user
2. Go to **Purchases/Invoices** page
3. Open a **pending invoice** → click **"Pay with PayHere"**
4. You're redirected to the PayHere Sandbox checkout page
5. Enter a test card (see below) and complete the payment
6. You're redirected back to `/company/purchases?payment=success`
7. The invoice status updates to `completed` (via the notify callback)

---

## Test Cards

### ✅ Successful Payments

| Card Type | Number |
|-----------|--------|
| Visa | `4916217501611292` |
| MasterCard | `5307732125531191` |
| AMEX | `346781005510225` |

> For **Name on Card**, **CVV**, and **Expiry Date** — enter any valid values (e.g. `Test User`, `123`, `12/26`).

### ❌ Decline Scenarios

| Scenario | Visa | MasterCard | AMEX |
|----------|------|------------|------|
| Insufficient Funds | `4024007194349121` | `5459051433777487` | `370787711978928` |
| Limit Exceeded | `4929119799365646` | `5491182243178283` | `340701811823469` |
| Do Not Honor | `4929768900837248` | `5388172137367973` | `374664175202812` |
| Network Error | `4024007120869333` | `5237980565185003` | `373433500205887` |

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `Payment gateway not configured` | Docker container doesn't have the env vars | Restart Docker after updating `.env` |
| Invoice stays `pending` after payment | `notify_url` points to `localhost` | Use a public tunnel URL |
| PayHere shows "Invalid Merchant" | Wrong Merchant ID or Secret | Verify from Sandbox → Integrations page |
| `Valid Domain Name Required` | ngrok URL rejected when adding domain | Use `localhost` as domain in PayHere Integrations instead |
| Payment page loads but hash error | Amount formatting issue | Amount must be formatted as `1000.00` (2 decimal places, no commas) |

### Watching Live Logs

```bash
# Watch notify callback in real-time
docker logs ecocycle-app -f | grep 'PayHere'
```

Lines starting with `[PayHere Notify]` appear when PayHere sends the server callback.

---

## Going Live (Production)

When ready to go live:

1. Register at [payhere.lk/merchant/sign-up](https://www.payhere.lk/merchant/sign-up) for a **Live account** (separate from Sandbox)
2. Add your **real domain** in Live → Integrations → get Live Merchant Secret
3. Update environment variables:
   ```env
   PAYHERE_SANDBOX=false
   PAYHERE_MERCHANT_ID=your_live_merchant_id
   PAYHERE_MERCHANT_SECRET=your_live_merchant_secret
   PAYHERE_NOTIFY_URL=https://yourdomain.com/api/payhere/notify
   PAYHERE_RETURN_URL=https://yourdomain.com/company/purchases?payment=success
   PAYHERE_CANCEL_URL=https://yourdomain.com/company/purchases?payment=cancelled
   ```
4. The `PayHereService` automatically switches to `https://www.payhere.lk/pay/checkout` when `PAYHERE_SANDBOX=false`

> 🔒 **Never commit real Merchant Secrets to version control.** Use environment variables or a secrets manager in production.

---

## References

- [PayHere Checkout API Docs](https://support.payhere.lk/api-&-mobile-sdk/checkout-api)
- [PayHere Sandbox & Testing](https://support.payhere.lk/sandbox-and-testing)
- [Cloudflare Tunnel Docs](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/do-more-with-tunnels/trycloudflare/)
