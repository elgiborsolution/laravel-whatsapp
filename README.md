# Laravel WhatsApp Cloud API Package

Laravel package for integrating **Meta WhatsApp Business Cloud API**, providing:

- **CRUD Templates** for WhatsApp Business
- **Send messages** (text, media, template, interactive, location, document)
- **Multi-account support** (auto-pick default if only one)
- **Broadcast** to millions of recipients (chunking, scheduling, job queue)
- **Webhook** to update message status (sent, delivered, read, failed)
- **Queue + Retry** for reliability

---

## 🚀 Installation

1. Install:
```bash
composer require elgibor-solution/laravel-whatsapp-cloud:*
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
use Esolution\WhatsApp\Models\WhatsAppAccount;

$acc = WhatsAppAccount::resolve();

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

## 📜 Template CRUD

```php
$acc = WhatsAppAccount::resolve();

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

## 🔔 Webhook

- **URL**: `/whatsapp/webhook`
- **Verify**: Meta calls `GET /webhook?hub_mode=subscribe&hub_verify_token=xxx&hub_challenge=1234`
- **Status**: Meta sends `POST` updates with message status (`sent`, `delivered`, `read`, `failed`).

Statuses are saved to:
- `whatsapp_messages`
- `whatsapp_broadcast_recipients`

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