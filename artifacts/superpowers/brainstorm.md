## Goal
Add a capability for each WhatsApp phone number to forward data that the webhook received to another webhook URL. If not set, local processing proceeds normally.

## Constraints
- Must be configurable for each specific `WhatsappAccount` record.
- Should send the original Meta payload to the target URL via POST.
- Must not break existing local webhook processing (storing messages, delivery statuses, etc.).
- Performance: Webhook responses to Meta must remain fast to avoid timeouts.

## Known context
- The `whatsapp_accounts` table handles multiple phone numbers.
- Meta sends webhook data with `phone_number_id` inside `entry -> changes -> value -> metadata`.
- `WebhookController` is the entry point for all incoming webhooks.
- Laravel's `Http` client is already used in the project.

## Risks
- **Latency**: Synchronous HTTP calls to external URLs during a webhook request can cause Meta to timeout and retry the webhook.
- **Reliability**: If the external URL is intermittent, it might lose data unless a retry mechanism (queue) is used.
- **Privacy**: Forwarding raw data might include PII; users should ensure their target endpoints are secure.

## Options (2–4)
1. **Synchronous Direct Forwarding**:
   Within `WebhookController`, iterate through the payload, find the account, and if `forward_url` exists, immediately `Http::post($url, $payload)`.
   - *Pros*: Easiest to implement.
   - *Cons*: Blocks the request; high risk of Meta timeouts.

2. **Asynchronous Queued Forwarding (Recommended)**:
   Extract the `forward_url` from the relevant accounts and dispatch a background job (`ForwardWhatsappWebhook`) with the payload.
   - *Pros*: Non-blocking, allows for automatic retries via Laravel's queue system.
   - *Cons*: Requires a configured queue worker.

3. **Database-Trigerred Forwarding**:
   Log every webhook to a table first, then use a model observer or cron job to process and forward.
   - *Pros*: Best for auditing and debugging.
   - *Cons*: Adds database write latency and complexity.

## Recommendation
Implement **Option 2 (Asynchronous Queued Forwarding)**.
- Add a nullable `webhook_forward_url` column to the `whatsapp_accounts` migration.
- Create a queued job `ESolution\WhatsApp\Jobs\ForwardWebhookPayload`.
- Update `WebhookController@handle` to identify accounts in the payload and dispatch the job for any account that has a `forward_url` defined.
- Continue local processing of the webhook independently of the forwarding.

## Acceptance criteria
- [ ] Migration adds `webhook_forward_url` to `whatsapp_accounts`.
- [ ] `WhatsappAccount` model includes the new field.
- [ ] `WebhookController` detects the account even if multiple accounts are present in one Meta POST.
- [ ] HTTP request to the forward URL uses `POST` and passes the full JSON payload.
- [ ] Tests verify that forwarding is triggered correctly when the URL is set.
- [ ] Tests verify that local processing still occurs regardless of forwarding success.
