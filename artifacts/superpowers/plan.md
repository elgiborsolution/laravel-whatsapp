## Goal

Extend `laravel-whatsapp` to support Meta Tech Provider requirements, including customer onboarding, asset management, registration, profile management, media handling, flows, and analytics. Documentation and testing will be prioritized as requested.

## Assumptions

- The developer using the package has a Meta App with Tech Provider capabilities.
- The Graph API version to be used is v23.0.
- Existing `WhatsappAccount` model is sufficient for storing basic credentials.

## Plan

### 1. Onboarding & Assets

- **Files**:
  - [NEW] `src/Services/TechProvider/OnboardingService.php`
  - [NEW] `src/Services/TechProvider/AssetService.php`
  - [NEW] `tests/Unit/TechProvider/OnboardingTest.php`
- **Change**:
  - `OnboardingService`: Handle Embedded Signup tokens, retrieve WABA IDs, and provision accounts.
  - `AssetService`: List phone numbers and WABAs from the Business Management API.
- **Verify**:
  - Run `php vendor/bin/phpunit tests/Unit/TechProvider/OnboardingTest.php`

### 2. Registration & Profile

- **Files**:
  - [MODIFY] `src/Services/TechProvider/AssetService.php`
  - [NEW] `src/Services/TechProvider/ProfileService.php`
  - [NEW] `tests/Unit/TechProvider/ProfileTest.php`
- **Change**:
  - `AssetService`: Add `registerPhoneNumber` (with PIN) and `verifyPhoneNumber`.
  - `ProfileService`: Methods to update/read business profile (about, email, website).
- **Verify**:
  - Run `php vendor/bin/phpunit tests/Unit/TechProvider/ProfileTest.php`

### 3. Media & Messaging Actions

- **Files**:
  - [NEW] `src/Services/TechProvider/MediaService.php`
  - [MODIFY] `src/Services/WhatsAppService.php`
  - [NEW] `tests/Unit/TechProvider/MediaTest.php`
- **Change**:
  - `MediaService`: Add `upload`, `download`, and `delete` media.
  - `WhatsAppService`: Ensure `markAsRead` is working correctly with accounts.
- **Verify**:
  - Run `php vendor/bin/phpunit tests/Unit/TechProvider/MediaTest.php`

### 4. Flows & Templates

- **Files**:
  - [NEW] `src/Services/TechProvider/FlowsService.php`
  - [MODIFY] `src/Services/WhatsAppService.php`
  - [NEW] `tests/Unit/TechProvider/FlowsTest.php`
- **Change**:
  - `FlowsService`: Create, Update, Publish, and List WhatsApp Flows.
  - `WhatsAppService`: Enhance template methods to retrieve quality/status and handle non-creation operations.
- **Verify**:
  - Run `php vendor/bin/phpunit tests/Unit/TechProvider/FlowsTest.php`

### 5. Analytics & Webhooks

- **Files**:
  - [NEW] `src/Services/TechProvider/AnalyticsService.php`
  - [MODIFY] `src/Http/Controllers/WebhookController.php`
  - [NEW] `tests/Feature/TechProvider/WebhookTest.php`
- **Change**:
  - `AnalyticsService`: Fetch usage analytics and health diagnostics.
  - `WebhookController`: Ingest message status transitions and rich media inbound events.
- **Verify**:
  - Run `php vendor/bin/phpunit tests/Feature/TechProvider/WebhookTest.php`

### 6. Documentation

- **Files**:
  - [MODIFY] `README.md`
- **Change**:
  - Add documentation for all new Tech Provider features (Embedded Signup, Flows, Registration, Analytics).
- **Verify**:
  - Manual review of the `README.md` content.

## Risks & mitigations

- **Meta API changes**: Version the base URL and use modular services.
- **Complexity of Flows**: Provide clear examples in the README.

## Rollback plan

- Revert via git: `git checkout .`. New files can be deleted manually or via `git clean -fd`.

**Approve this plan? Reply APPROVED if it looks good.**
