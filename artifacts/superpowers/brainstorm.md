# Brainstorm: WhatsApp API Implementation

## Goal
Create a set of API endpoints to expose the following functionalities:
1. **Phone Number / Assets**: List, get details, register, and verify phone numbers.
2. **Flows**: Create, update, publish, and list WhatsApp Flows.
3. **Media**: Upload, retrieve, and delete media files.
4. **Onboarding**: Handle token exchange and WABA discovery from Embedded Signup.
5. **Profile**: Get and update WhatsApp Business Profile.
6. **Analytics**: Retrieve WABA metrics and phone number health status.

## Constraints
- **Framework**: Laravel package structure.
- **Consistency**: Must follow the existing Controller-Service pattern.
- **Security**: Routes must use `whatsapp.routes_middleware` (typically `api` or `auth:api`).
- **Dependency**: Must use existing `WhatsappAccount` records to retrieve tokens/IDs.

## Known context
- Core logic is already implemented in `src/Services/TechProvider/` services:
    - `AssetService`
    - `FlowsService`
    - `MediaService`
    - `OnboardingService`
    - `ProfileService`
    - `AnalyticsService`
- Current routes are defined in `src/routes.php`.
- Existing controllers occupy `src/Http/Controllers/`.

## Risks
- **Rate Limits**: Excessive API calls to Meta Graph API.
- **Security**: Ensuring users can only manage assets for accounts they own (if multiple users share one Laravel instance).
- **Complexity**: WhatsApp Flows require JSON asset uploads which might need specific request handling (multipart/form-data).

## Options (2–4)
1. **Granular Controllers**: Create a separate controller for each service (`AssetController`, `FlowsController`, etc.).
2. **Management Controller**: Group Assets, Profile, and Analytics into a single `ManagementController`, while keeping `Flows` and `Media` separate.
3. **Internal API Only**: Use these services only within other backend processes and do not expose them via HTTP. (Rejected based on user request).

## Recommendation
**Option 1: Granular Controllers**.
This approach provides the best clarity and maintainability. Each controller will map directly to its corresponding service, making it easy to find and update logic. It also keeps the codebase clean as the number of endpoints grows.

## Acceptance criteria
- [ ] New controllers created: `AssetController`, `FlowsController`, `MediaController`, `OnboardingController`, `ProfileController`, `AnalyticsController`.
- [ ] Routes registered in `routes.php` under the `whatsapp` prefix.
- [ ] Endpoints support basic CRUD or action for each service method.
- [ ] Validation implemented for request payloads (especially for Profile updates and Flow creation).
- [ ] Successful integration test or manual verification for each endpoint.
