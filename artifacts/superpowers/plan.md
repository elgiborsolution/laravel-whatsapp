## Goal
Add a capability for each phone number to forward data that the webhook received to another webhook URL, ensuring reliability through asynchronous queued processing.

## Assumptions
- Laravel queue is configured and running.
- Incoming Meta webhooks contain `phone_number_id` in the metadata to identify the account.

## Plan

### 1. Database Schema Update
- **Files**: `database/migrations/[timestamp]_add_webhook_forward_url_to_whatsapp_accounts_table.php` [NEW]
- **Change**: Add nullable `webhook_forward_url` string column to `whatsapp_accounts` table.
- **Verify**: Run `php artisan migrate` and check table structure.

### 2. Update Model
- **Files**: `src/Models/WhatsappAccount.php` [MODIFY]
- **Change**: Add `webhook_forward_url` to the `$fillable` array.
- **Verify**: Inspect the file content.

### 3. Create Forwarding Job
- **Files**: `src/Jobs/ForwardWebhookJob.php` [NEW]
- **Change**: Implement a queued job that takes `url` and `payload`, then performs a POST request using `Http::withOptions(['verify' => false])->retry(3, 100)->post($url, $payload)`.
- **Verify**: Unit test for the job.

### 4. Update Webhook Controller
- **Files**: `src/Http/Controllers/WebhookController.php` [MODIFY]
- **Change**: In `handle()` method, extract `phone_number_id` from the payload. Lookup the corresponding `WhatsappAccount`. If `webhook_forward_url` is present, dispatch `ForwardWebhookJob`.
- **Verify**: Integrated test with `Http::fake()`.

### 5. Documentation Update
- **Files**: `README.md` [MODIFY]
- **Change**: Add a section about Webhook Forwarding configuration.
- **Verify**: Inspect README.

## Risks & mitigations
- **Risk**: Target webhook is slow or down.
- **Mitigation**: Using a queued job with Laravel's built-in retry mechanism (`tries`, `backoff`).
- **Risk**: High volume of webhooks flooding the queue.
- **Mitigation**: Ensure the job is lightweight and use a dedicated queue if necessary (configurable via `config/whatsapp.php`).

## Rollback plan
- Delete the migration and run `php artisan migrate:rollback`.
- Remove the job class and controller logic changes.
