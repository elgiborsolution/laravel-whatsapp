## Goal
Implement a general-purpose Inbound Token system (OTP, Voucher, etc.) that parses tokens from mixed-text WhatsApp messages.

## Assumptions
- Laravel 10+ environment.
- `whatsapp_messages` table exists and webhooks are already configured.
- Normalization of phone numbers is handled by existing traits.

## Plan

### Step 1: Database Migration
- **Files**: `database/migrations/2026_02_01_000000_create_whatsapp_tokens_table.php`
- **Change**: Create table to store tokens, types, metadata, and expiration.
- **Verify**: Run `php artisan migrate:status`.

### Step 2: Token Model
- **Files**: `src/Models/WhatsappToken.php`
- **Change**: Implement model with scopes for active/verified status.
- **Verify**: Tinker instantiation.

### Step 3: Service Logic (Generation)
- **Files**: `src/Services/WhatsAppService.php`
- **Change**: Add `createToken` method. 
    - **Support multiple formats**: `alphanumeric` (default), `numeric`, or `uuid`.
    - Allow custom length for alphanumeric/numeric.
- **Verify**: Test generating each format via Tinker.

### Step 4: Service Logic (Consumption/Matching)
- **Files**: `src/Services/WhatsAppService.php`
- **Change**: Add `consumeToken` method to find and verify tokens in mixed text.
- **Verify**: Unit test with mixed text payloads for each format.

### Step 5: Webhook Integration
- **Files**: `src/Http/Controllers/WebhookController.php`
- **Change**: Integrate `consumeToken` into inbound message processing.
- **Verify**: Simulated webhook POST request.

### Step 6: Documentation Update
- **Files**: `README.md`
- **Change**: Detail usage, including how to choose between Alphanumeric (user-friendly) and UUID (highly secure/machine-readable) formats.
- **Verify**: Final review of documentation.

## Risks & mitigations
- **Risk**: Token collision or false positives. 
- **Mitigation**: 
    - Use Alphanumeric (8+ chars) for a balance of security and usability.
    - **Use UUID** for maximum security if the user can copy-paste (no risk of collision).
    - Scope searches to the sender's phone number.

## Rollback plan
- Delete the migration and the new files (`WhatsappToken.php`).
- Revert changes to `WhatsAppService.php` and `WebhookController.php`.
