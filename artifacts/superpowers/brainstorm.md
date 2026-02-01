## Goal

Implement support for Meta Tech Provider registration and capability requirements in the `laravel-whatsapp` package. This includes streamlining customer onboarding via Embedded Signup, managing assets, registration, business profiles, media, flows, and analytics.

## Constraints

- Must remain compatible with existing `laravel-whatsapp` structures.
- Must follow Meta's technical requirements for Tech Providers.
- Should be easy for developers to integrate into their own Laravel applications.
- Handle multi-tenant scenarios (multiple WABAs/Tokens) if applicable.

## Known context

- The package already handles basic message sending and webhook verification.
- Template management is partly implemented.
- `WhatsappAccount` and `WhatsappToken` models exist.
- Documentation for Meta Cloud API and Business Management API is key.

## Risks

- Meta's API frequently changes (versioning).
- Embedded Signup requires specific Meta App configurations (permissions, features).
- Webhook scaling for many customers.
- Security of PIN management and access tokens.
- Complexity of WhatsApp Flows (interactive JSON structures).

## Options (2–4)

1. **Monolithic Approach**: Add all features directly into the main `WhatsAppService`.
   - _Pros_: Simple discovery.
   - _Cons_: Service class will become bloated.
2. **Domain-Driven Services**: Separate features into dedicated services (e.g., `OnboardingService`, `FlowsService`, `MediaService`).
   - _Pros_: Cleaner code, easier to maintain.
   - _Cons_: More files to manage.
3. **Trait-Based Service**: Use traits in `WhatsAppService` to group related functionality.
   - _Pros_: Keeps API surface single-point but organized code.
   - _Cons_: Traits can become "magic" and hard to track.

## Recommendation

Option 2: **Domain-Driven Services**. Given the breadth of requirements (Flows, Onboarding, Assets, Profile, Media), separate services will be much cleaner. I'll create a namespace `ESolution\WhatsApp\Services\TechProvider` and put these specialized services there. The main `WhatsAppService` can act as a gateway or developers can use these specialized services directly.

## Acceptance criteria

- Developers can trigger an Embedded Signup flow and receive the necessary assets (WABA ID, Token).
- Phone numbers can be listed and retrieved with metadata (status, quality).
- Business profile fields can be updated via the API.
- Webhooks correctly ingest status updates and incoming messages for any connected WABA.
- Media can be uploaded and retrieved/deleted.
- WhatsApp Flows (interactive forms) can be created, updated, and published.
- Analytics and health status are available via the Graph API integration.
