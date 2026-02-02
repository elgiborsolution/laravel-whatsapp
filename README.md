# Laravel WhatsApp Cloud API Package

Laravel package for integrating **Meta WhatsApp Business Cloud API**, providing:

- **CRUD Templates** for WhatsApp Business
- **Send messages** (text, media, template, interactive, location, document)
- **Multi-account support** (auto-pick default if only one)
- **Broadcast** to millions of recipients (chunking, scheduling, job queue)
- **Webhook** to update message status (sent, delivered, read, failed)
- **Inbound Tokens** for OTP, Vouchers, and mixed-message parsing
- **Queue + Retry** for reliability

---

## 🚀 Installation

1. Install:

```bash
composer require elgibor-solution/laravel-whatsapp-meta:*
```

3. Publish config & migrations:

```bash
php artisan vendor:publish --tag=whatsapp-config
php artisan vendor:publish --tag=whatsapp-migrations
php artisan migrate
```

4. Add to **scheduler**:

```php
// routes/console.php
$schedule->command('whatsapp:broadcast-run')->everyMinute();
```

---

## ⚙️ Configuration

### Single Account (via `.env`)

```env
WHATSAPP_SINGLE_ACCOUNT=true
WHATSAPP_NAME=default
WHATSAPP_PHONE_NUMBER_ID=YOUR_PHONE_ID
WHATSAPP_ACCESS_TOKEN=YOUR_LONG_LIVED_TOKEN
WHATSAPP_WABA_ID=YOUR_WABA_ID
WHATSAPP_WEBHOOK_VERIFY_TOKEN=change-me
```

### Multi Account

If multiple accounts are needed:

- Insert rows into `whatsapp_accounts` table (migration provided).
- Mark one with `is_default = 1` as the default account.

---

## 📡 Routes

All endpoints are available with prefix `/whatsapp/*`:

- `GET /whatsapp/webhook` – webhook verification
- `POST /whatsapp/webhook` – handle message status updates
- `POST /whatsapp/messages/send` – send a message
- `GET/POST/PUT/DELETE /whatsapp/templates` – manage templates
- `POST /whatsapp/broadcasts` – create broadcast
- `POST /whatsapp/broadcasts/{id}/schedule` – schedule broadcast
- `POST /whatsapp/broadcasts/{id}/pause|resume` – control broadcast

---

## 🧩 Usage via Facade

```php
use WhatsApp;
use ESolution\WhatsApp\Models\WhatsappAccount;

$acc = WhatsappAccount::resolve();

// 1. Send text
WhatsApp::sendText($acc, '08123456789', 'Hello from Cloud API!');

// 2. Send template
WhatsApp::sendTemplate($acc, '08123456789', 'order_update', 'en', [
    ['type'=>'body','parameters'=>[['type'=>'text','text'=>'John']]]
]);

// 3. Send media
WhatsApp::sendMedia($acc, '08123456789', 'image', [
    'link'=>'https://example.com/img.jpg',
    'caption'=>'Promo'
]);

// 4. Send location
WhatsApp::sendLocation($acc, '08123456789', -6.2, 106.8, 'Office', 'Jakarta');

// 5. Send interactive
WhatsApp::sendInteractive($acc, '08123456789', [
    'type'=>'list',
    'header'=>['type'=>'text','text'=>'Menu'],
    'body'=>['text'=>'Choose option'],
    'footer'=>['text'=>'E-Solution'],
    'action'=>[
        'button'=>'View',
        'sections'=>[
            ['title'=>'Products','rows'=>[
                ['id'=>'prod1','title'=>'Product 1','description'=>'Description 1'],
                ['id'=>'prod2','title'=>'Product 2','description'=>'Description 2'],
            ]]
        ]
    ]
]);
```

---

## 🔑 Inbound Tokens (OTP / Vouchers)

Allow users to verify or claim items by sending a unique code back to your WhatsApp Business Number. The system automatically searches for these tokens even if they are buried within a longer message.

### 1. Create a Token

```php
use ESolution\WhatsApp\Facades\WhatsApp;

// Generate an 8-character alphanumeric code (default)
$token = WhatsApp::createToken('08123456789', 'otp');

// Generate a 10% discount voucher valid for 1 hour
$voucher = WhatsApp::createToken('08123456789', 'voucher', [
    'discount' => '10%',
    'campaign' => 'black_friday'
], [
    'expires_in' => 60,
    'length' => 12
]);

// Generate a secure UUID token
$uuid = WhatsApp::createToken('08123456789', 'secure_claim', [], [
    'format' => 'uuid'
]);
```

### 2. Handle Verification

When the user sends a message containing the token (e.g., "Hi, here is my code: ABC-123"), the package fires an event.

**Register Listeners:**

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    'whatsapp.token.verified' => [
        \App\Listeners\LogVerification::class,
    ],
    /** OR type-specific listener **/
    'whatsapp.token.verified.voucher' => [
        \App\Listeners\ApplyDiscount::class,
    ],
];
```

**Listener Example:**

```php
public function handle($token)
{
    // $token is an instance of ESolution\WhatsApp\Models\WhatsappToken
    $phone = $token->phone;
    $metadata = $token->metadata; // ['discount' => '10%', ...]

    // Perform your logic here
}
```

---

## 📜 Template CRUD

```php
$acc = WhatsappAccount::resolve();

// List
$list = WhatsApp::listTemplates($acc);

// Create
$tpl = WhatsApp::createTemplate($acc, [
  'name'=>'promo_aug',
  'category'=>'MARKETING',
  'language'=>'en',
  'components'=>[['type'=>'BODY','text'=>'Hello {{1}}, August promo!']]
]);

// Delete
WhatsApp::deleteTemplate($acc, 'promo_aug', 'en');
```

---

## 📢 Broadcast

### Create Broadcast

```json
POST /whatsapp/broadcasts
{
  "name": "Promo August",
  "type": "template",
  "payload": {
    "name": "promo_aug",
    "language": "en",
    "components": [
      { "type": "body", "parameters": [ { "type": "text", "text": "Customer" } ] }
    ]
  },
  "recipients": ["0811111111","0812222222","0813333333"],
  "chunk_size": 2000,
  "rate_per_min": 6000
}
```

### Schedule Broadcast

```json
POST /whatsapp/broadcasts/1/schedule
{ "scheduled_at": "2025-08-24 22:00:00" }
```

### Run Scheduler

```bash
php artisan schedule:run
```

Broadcasts will be dispatched in chunks using `SendBroadcastChunkJob` with respect to `chunk_size` and `rate_per_min`.

---

## 🚀 Tech Provider (Partner) Services

For Meta Tech Providers, this package provides specialized services and **API endpoints** for managing client accounts at scale.

### 🌐 API Endpoints

All routes are prefixed with `/whatsapp/` (customizable in config).

#### 📱 Asset Management (Phone Numbers)

- `GET /accounts/{acc_id}/phone-numbers` – List phone numbers for WABA.
- `GET /accounts/{acc_id}/phone-numbers/{phone_id}` – Get details.
- `POST /accounts/{acc_id}/phone-numbers/{phone_id}/register` – Register for Cloud API.
- `POST /accounts/{acc_id}/phone-numbers/{phone_id}/verify` – Verify with SMS code.

#### 🌊 WhatsApp Flows

- `GET /accounts/{acc_id}/flows` – List all flows.
- `POST /accounts/{acc_id}/flows` – Create new flow.
- `POST /accounts/{acc_id}/flows/{flow_id}/assets` – Upload JSON asset.
- `POST /accounts/{acc_id}/flows/{flow_id}/publish` – Publish flow.

#### 📁 Media Management

- `POST /accounts/{acc_id}/media` – Upload media (multipart/form-data).
- `GET /accounts/{acc_id}/media/{media_id}` – Get metadata.
- `DELETE /accounts/{acc_id}/media/{media_id}` – Delete media.

#### 🤝 Onboarding

- `POST /onboarding/exchange-token` – Exchange short-lived FB token.
- `POST /onboarding/debug-token` – Get WABA/token info.

#### 👤 Business Profile

- `GET /accounts/{acc_id}/profile/{phone_id}` – Get profile.
- `POST /accounts/{acc_id}/profile/{phone_id}` – Update profile fields.

#### 📊 Analytics & Health

- `GET /accounts/{acc_id}/analytics` – WABA messaging metrics.
- `GET /accounts/{acc_id}/phone-numbers/{phone_id}/health` – Quality rating & status.

---

## 🔑 Inbound Tokens (OTP / Vouchers)

### 🌐 API Endpoints

- `POST /whatsapp/tokens` – Create a token.
- `POST /whatsapp/tokens/consume` – Manually verify token from string.

---

## 🔔 Webhook

- **URL**: `/whatsapp/webhook`
- **Verify**: Meta calls `GET /webhook?hub_mode=subscribe&hub_verify_token=xxx&hub_challenge=1234`
- **Status**: Meta sends `POST` updates with message status (`sent`, `delivered`, `read`, `failed`).
- **Call Permission**: Meta sends `POST` updates with call_permission_reply status (`accept`, `reject`).
- **Forwarding**: If `webhook_forward_url` is set for an account, the raw payload is forwarded to that URL via a queued POST request.

Statuses are saved to:

- `whatsapp_messages`
- `whatsapp_broadcast_recipients`

Call permission are broadcast through event:
`whatsapp.call_permission.updated`

You need to create your own event listener and register it at EventServiceProvider

```bash
// app/Providers/EventServiceProvider.php
protected $listen = [
  'whatsapp.call_permission.updated' => [
    \App\Listeners\HandleCallPermissionUpdated::class,
  ],
];
```

---

## ⏱️ Queue & Retry

- Messages are sent via `SendMessageJob`.
- Broadcasts are handled via `SendBroadcastChunkJob`.
- Use Redis / SQS / Horizon queues for scaling.
- Automatic retry: `tries = 3`, `backoff = 30s`.

---

## ✅ Best Practices

- Always use **APPROVED templates** for outbound messages beyond 24h window.
- Respect **WABA tier rate limits**. `rate_per_min` only throttles locally.
- Media must be **public URLs**.
- Phone numbers are normalized: leading `0` → `62`. Adjust if needed.
- Store and log Graph API responses for error analysis.

---

## 🧪 Testing

The package uses **Orchestra Testbench** for comprehensive testing.

1. Install dev dependencies:

```bash
composer install
```

2. Run tests:

```bash
./vendor/bin/phpunit
```

Test coverage includes:

- **Unit**: Component logic and traits.
- **Integration**: Database models and migrations.
- **Feature**: Webhook routes and payload processing.

---

## Support & Hiring

Need professional help or want to move faster? **Hire the E-Solution / Elgibor team** for integration, audits, or custom features.  
📧 **info@elgibor-solution.com**

---

## Donations

If this package saves you time, consider supporting development ❤️

- **Ko‑fi**: [![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/U7U21L7D5J)
