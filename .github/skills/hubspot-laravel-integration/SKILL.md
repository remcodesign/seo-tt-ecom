---
name: hubspot-laravel-integration
description: 'Build and maintain HubSpot integrations in this Laravel application. Use for HubSpot CRM UI extensions, app cards, action or agent-tool endpoints, webhooks, request signatures, Laravel AI quote workflows, OpenRouter fallback behavior, Livewire admin test tools, logging, and HubSpot CLI project development.'
argument-hint: 'Describe the HubSpot workflow, endpoint, extension, or CLI task.'
---

# HubSpot Laravel Integration

## Purpose

Use this skill for the integration boundary between HubSpot and this Laravel 13 application. The project currently uses a thin signed HTTP API, Laravel services, Laravel AI agents, and an admin-only Livewire console. Keep the human-triggered workflow and autonomous agent workflow separate.

## Project Goal

The target proof of concept is a Smart Quote card shown to a sales user on a HubSpot Deal:

1. The React card reads the Deal and its associated contact.
2. The card sends the contact email to Laravel for deterministic VIP and customer-value rules.
3. Laravel returns VIP status, lifetime value, and allowed discount.
4. Laravel generates a short quote pitch, using safe fallback text before an optional AI provider.
5. The card displays the result and lets the sales user copy the pitch through a HubSpot action.

The first milestone is the complete fallback flow. Add OpenRouter or another AI provider only after the card, association lookup, signed API calls, error states, and copy action work end to end. The AI may propose wording, but it must never decide the discount or customer eligibility.

## Two Independent Projects

This repository is intentionally a monorepo-style workspace containing two technically independent projects:

```text
seo_tt_ecom/
├── app/                 # Laravel backend
├── config/
├── routes/
├── tests/
└── hubspot-smart-quote/ # Separate HubSpot CLI / React project
```

Keep HubSpot dependencies in `hubspot-smart-quote`, not in the Laravel root `package.json`. The HubSpot project owns the React card, CRM context, `hubspot.fetch`, `permittedUrls`, and clipboard/UI actions. Laravel owns API endpoints, signatures, validation, customer rules, AI, and logging. Keep `node_modules`, CLI profiles, and local secrets out of Git. Laravel deployment should not execute the HubSpot project as part of its application build.

The card is installed on Deal records (`objectTypes: ["deals"]`). It reads Deal properties with the HubSpot UI Extensions SDK and obtains the first associated contact through the CRM association hook. A Deal without an associated contact must produce a clear empty state rather than an invalid API request.

## Source Of Truth

Before changing code, inspect the nearest existing implementation and tests:

- `app/Http/Controllers/Api/HubSpot/`
- `app/Http/Middleware/VerifyHubSpotRequestSignature.php`
- `app/Data/HubSpot/Requests/`
- `app/Services/HubSpot/`
- `app/Ai/Agents/HubSpot/`
- `app/Livewire/Admin/HubSpot/`
- `tests/Feature/Api/HubSpot/`
- `tests/Feature/Admin/Livewire/AdminLivewireHubSpotTest.php`
- `config/hubspot.php`, `config/ai.php`, and the dedicated `hubspot` and `ai` log channels
- `docs/private/docs/hubspot/3-idea-how-to.md` and `docs/private/docs/hubspot/4-idea-hubspot-project-raw.md` for the end-to-end POC goal and separate project boundary

The idea document describes possible MCP and Breeze-agent extensions, but those are not automatically implemented by the current code. Do not invent an MCP route or claim that HubSpot writes a generated pitch back to a CRM property unless the API contract and persistence path are explicitly provided.

## Architecture Rules

### Workflow action handoff

For a custom workflow action, use the reusable contract guidance in
`hubspot-workflow-actions` and keep these phases distinct:

1. HubSpot metadata declares the enrolled object, action URL, inputs, outputs,
    and publication state.
2. Laravel verifies the documented signature and resolves the portal tenant.
3. Laravel validates `callbackId`, workflow context, object type, and
    `object.objectId` before creating an idempotent task.
4. The intake response is fast and bounded. Slow CRM, AI, inventory, note, and
    callback work belongs to the queue worker.
5. The worker completes the blocked action with a safe result and an explicit
    success or failure state.

Do not treat a valid `hsmeta` file, a signed route, or a `501` placeholder as an
end-to-end implementation. Record current behavior and target behavior
separately, and keep action versions compatible during migrations.

### Tenant-scoped CRM access

Use an authorized per-portal HubSpot connection for server-side CRM reads and
writes. Prefer OAuth with encrypted token storage and refresh handling over an
API-key shortcut. Read enrolled CRM context deterministically, request
properties explicitly, paginate association results, and use batch reads when
they reduce rate-limit pressure. Keep the CRM client responsible for transport
and response normalization; keep SKU, quantity, inventory, and note
idempotency rules in Laravel services.

For current HubSpot behavior, verify the exact endpoint, scope, signature,
callback, and object payload with Context7's official HubSpot documentation and
an isolated Developer Test Account before implementation. Do not infer a
`callbackUrl` or tenant from ordinary workflow input fields.

### Keep the workflows separate

- A human-facing CRM action or UI extension should call a focused REST endpoint for a fast response.
- An autonomous HubSpot agent or MCP client may use a separate agent-tool or MCP boundary with explicit authentication and tool schemas.
- Do not make a traditional React or CRM button call MCP directly. MCP is an LLM-to-tools interface; use the normal backend endpoint for deterministic human actions.
- If MCP is requested, use the project's installed Laravel MCP support and its current documentation. Do not hand-roll a partial JSON-RPC dispatcher when the framework integration can expose tools safely.

### Request boundary

HubSpot endpoints belong under `routes/api.php`, usually in a dedicated `hubspot` prefix and protected by signature middleware. Keep controllers invokable, `readonly`, and thin:

1. Accept a typed Spatie Laravel Data request DTO.
2. Let the service perform business logic.
3. Return a small JSON response.

Follow the current pattern:

```php
Route::prefix('hubspot')
    ->middleware('hubspot.signature')
    ->group(function (): void {
        Route::post('/customer-check', CustomerCheckController::class);
        Route::post('/quote-pitch', QuotePitchController::class);
    });
```

Use explicit DTO validation for every external field. Validate strings, email addresses, maximum lengths, numeric minimums, and bounded percentages before service code runs. Keep external names such as `deal_name` in request DTOs and map them to descriptive service arguments.

The frontend must call the Laravel endpoints through HubSpot's `hubspot.fetch`, never native browser `fetch`. Every exact HTTPS endpoint used by the card must be listed in the HubSpot app metadata under `permittedUrls.fetch`; never use `*`, a local DDEV hostname, or an HTTP URL. The Laravel host must be publicly reachable over HTTPS through a deployment or a temporary tunnel.

## Request Signature Security

Do not copy a signature algorithm blindly. First identify which HubSpot product and signature version sends the request, then implement that version from the current HubSpot documentation.

- Modern HubSpot request validation can use versioned headers such as `X-HubSpot-Signature-v3` and `X-HubSpot-Request-Timestamp`.
- Other HubSpot surfaces use `X-HubSpot-Signature` with v1 or v2 semantics.
- Validate the required headers, configured client secret, timestamp freshness, canonical URL, HTTP method, raw request body, and the exact version-specific digest/encoding.
- Use a constant-time comparison such as `hash_equals`.
- Reject missing, malformed, stale, or mismatched signatures with 403.
- Never log the client secret or complete authorization headers.
- During local HubSpot CLI proxy development, requests may be unsigned by default. Use the documented local secret injection mechanism when signature validation itself must be tested; do not weaken production middleware just to make local development work.

The current project middleware uses a five-minute replay window and the v3 request formula. Treat that as a project implementation detail, not as a universal HubSpot rule. Add or adjust tests whenever the target HubSpot surface changes.

## Services And AI

Keep deterministic business rules in ordinary services. For example, the customer-check service returns a typed, documented array containing VIP status, lifetime value, allowed discount, reason, and source.

For generated quote text:

- Put model instructions in a dedicated `Agent` class under `app/Ai/Agents/HubSpot/`.
- Instruct the agent to use only supplied facts, avoid invented details or guarantees, return plain text, and enforce a short output limit.
- Isolate provider calls in `OpenRouterService`.
- Treat missing API keys, missing model configuration, provider exceptions, empty output, and overlong output as normal fallback conditions.
- Keep a deterministic `QuotePitchService` fallback so the HubSpot workflow remains usable without an external model.
- Pass explicit model and timeout configuration; do not hardcode secrets or provider credentials.
- Log provider name, model, usage, and output length, but avoid unnecessary customer PII and never log prompts containing secrets.

The expected result shape is small and explicit, for example `text`, `provider`, `generated`, and nullable `model`. Do not let raw provider response objects leak through the HTTP or Livewire boundary.

## Admin Test Surface

When adding manual testability, keep it behind the existing admin authorization pattern:

- Put the console and logs pages under the authenticated admin web route group.
- Check the admin role in `mount()` and redirect unauthorized users to the admin login route.
- Keep Livewire form state explicit and validate it again at action time.
- Provide separate actions for deterministic customer checks, pitch generation, and clearing results.
- Show whether the AI provider is configured, but never display API keys.
- Restrict the log viewer to the dedicated `hubspot` and `ai` channels; do not expose the general application log by default.

## Test Account And Demo Data

Use a HubSpot Developer Test Account or equivalent isolated developer account, never a normal production portal. Keep the Laravel client secret, HubSpot account IDs, public backend URL, and optional AI key in local or deployment secrets.

The reference demo uses deterministic test data:

- VIP contact: `vip@example.test`
- Unknown contact: `unknown@example.test`
- VIP Deal: `VIP Website Renewal`, amount `12000`
- Unknown Deal: `Unknown Website Renewal`, amount `8000`

Create and associate the contacts and Deals before debugging the card. An association is part of the demo contract because the Deal normally does not contain the contact email directly.

## Testing Workflow

Every HubSpot change must have focused Pest coverage:

1. For API tests, configure a test client secret and generate the exact signed raw JSON request used by the middleware.
2. Assert valid signed requests, missing or invalid signatures, stale timestamps, and DTO validation errors.
3. Test deterministic service output independently of external providers.
4. For AI paths, call `QuotePitchAgent::fake()->preventStrayPrompts()` and assert both fallback and configured-provider behavior.
5. Assert that the agent prompt contains the relevant supplied facts, not only the final generated text.
6. Test admin authorization and the Livewire console/log page behavior.

Run the narrow affected test first, then the project's normal checks:

```bash
php artisan test --compact tests/Feature/Api/HubSpot/HubSpotControllerTest.php
php artisan test --compact tests/Feature/Admin/Livewire/AdminLivewireHubSpotTest.php
ddev composer format-basic
```

Do not send real OpenRouter requests from tests. Keep fake credentials and test-only CRM data such as `vip@example.test` out of production configuration.

## HubSpot CLI Workflow

For a separate HubSpot project containing UI extensions or app configuration:

```bash
hs init
hs project dev
hs project validate
hs project upload
```

Use `hs project dev` for the local CRM proxy and live extension iteration. Source-code changes can refresh automatically; configuration changes may require an explicit upload. Validate before uploading. In CI, upload from the checked-out local project directory rather than assuming HubSpot pulls directly from Git.

Keep test accounts or developer accounts separate from production. Use environment-specific CLI profiles and never commit secrets, client secrets, or access tokens.

For the Laravel side, use a public HTTPS deployment such as Laravel Cloud for a stable demo, or a DDEV share/tunnel for local iteration. Confirm `/up`, the signed API endpoints, `APP_URL`, writable logs, and the relevant environment variables before installing the card. If the frontend is served from a different origin, configure narrowly scoped CORS for the HubSpot UI extension origin; do not solve browser problems by disabling signature validation or allowing all origins.

## Documentation Checks

For current HubSpot behavior, consult the official developer documentation before implementing a new surface:

- [Request validation](https://developers.hubspot.com/docs/apps/developer-platform/build-apps/authentication/request-validation)
- [UI extension data fetching and local development](https://developers.hubspot.com/docs/apps/developer-platform/add-features/ui-extensions/fetching-data)
- [HubSpot CLI project commands](https://developers.hubspot.com/docs/developer-tooling/local-development/hubspot-cli/project-commands)
- [Agent tools](https://developers.hubspot.com/docs/apps/developer-platform/add-features/agent-tools/reference)

Verify the signature version, endpoint payload, CLI command, and account prerequisites against the current product documentation instead of relying on the idea document or an old code sample.

## Common Mistakes

- Putting business rules in controllers or Livewire components.
- Calling an LLM without a deterministic fallback.
- Sending unsigned local requests through production-style exceptions.
- Assuming every HubSpot request uses the same signature header or formula.
- Hand-rolling an MCP endpoint without implementing the full protocol and authentication contract.
- Logging complete request bodies, secrets, or unnecessary customer data.
- Returning provider-specific response objects to HubSpot.
- Testing only the happy path or allowing real provider calls during Pest runs.