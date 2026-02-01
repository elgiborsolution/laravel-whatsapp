# Implementation Plan - WhatsApp Tech Provider APIs

Create a comprehensive set of API endpoints to manage WhatsApp assets, flows, media, onboarding, profiles, analytics, and inbound tokens.

## Proposed Changes

### Controllers
Create 7 new controllers to handle the respective functionalities.

#### [NEW] [AssetController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/AssetController.php)
- `index`: List phone numbers.
- `show`: Get phone number details.
- `register`: Register a phone number.
- `verify`: Verify a phone number.

#### [NEW] [FlowsController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/FlowsController.php)
- `index`: List flows.
- `store`: Create a new flow.
- `updateAsset`: Upload flow asset (JSON).
- `publish`: Publish a flow.

#### [NEW] [MediaController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/MediaController.php)
- `store`: Upload media.
- `show`: Get media details.
- `destroy`: Delete media.

#### [NEW] [OnboardingController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/OnboardingController.php)
- `exchangeToken`: Exchange short-lived token for long-lived.
- `debugToken`: Get WABA info via token.

#### [NEW] [ProfileController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/ProfileController.php)
- `show`: Get business profile.
- `update`: Update business profile.

#### [NEW] [AnalyticsController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/AnalyticsController.php)
- `wabaMetrics`: Get WABA messaging metrics.
- `phoneHealth`: Get phone number health status.

#### [NEW] [TokenController.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/Http/Controllers/TokenController.php)
- `store`: Create a new inbound token (OTP/Voucher).
- `consume`: Manually verify/consume a token.

---

### Routing

#### [MODIFY] [routes.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/src/routes.php)
- Register the new routes under the existing `whatsapp` prefix and middleware group.

---

### Documentation

#### [MODIFY] [README.md](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/README.md)
- Update the "Tech Provider (Partner) Services" and "Inbound Tokens" sections to include documentation for the new API endpoints.

---

### Testing

#### [NEW] [TechProviderApiTest.php](file:///Users/e-solution/Documents/Packages/laravel-whatsapp/tests/Feature/TechProviderApiTest.php)
- Implement feature tests for each new endpoint using `Http::fake()` to mock Meta API responses (where applicable) and standard request/response testing.

## Verification Plan

### Automated Tests
Run the newly created feature tests:
```bash
./vendor/bin/phpunit tests/Feature/TechProviderApiTest.php
```

### Manual Verification
1. Verify routes are registered:
```bash
php artisan route:list --path=whatsapp
```
2. For local testing:
- Use Postman to call the endpoints with a valid `WhatsappAccount` (for assets/flows/media/etc) or phone number (for tokens).

## Rollback Plan
- Delete New Controllers.
- Revert changes to `src/routes.php`.
- Revert changes to `README.md`.
- Delete `tests/Feature/TechProviderApiTest.php`.

---
Approve this plan? Reply APPROVED if it looks good.
