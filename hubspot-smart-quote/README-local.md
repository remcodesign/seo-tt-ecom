# HubSpot Smart Quote

Standalone HubSpot CLI project for a Deal record card. It is intentionally separate from the Laravel application in the repository root.

## Local checks

```bash
cd hubspot-smart-quote/src/app/cards
npm install
npm run validate
```

`validate` runs ESLint, TypeScript, and Vitest. The root Laravel `package.json` is not used by this project.

## HubSpot CLI workflow

Install and authenticate the official CLI only when a HubSpot developer or test account is available:

```bash
npm install -g @hubspot/cli
hs init
hs account list
hs project validate
hs project dev
```

## Dev mode via dev test user - find via number below dev-tester in the Hubspot interface

```bash
hs project dev \
  --project-account xxx \
  --testing-account xxx \
  --debug
```

Use `hs project upload` and `hs project deploy` only after replacing the placeholder Laravel URLs in `src/app/app-hsmeta.json` and `src/app/cards/smart-quote-card.tsx`.

The project was shaped to the current Projects CLI format. If `hs project validate` reports a generated metadata difference, accept the CLI-generated metadata shape and preserve the existing card contract: Deal object type, sidebar location, card entrypoint, and exact HTTPS `permittedUrls.fetch` entries.

## Switching to Laravel

When the backend is publicly reachable over HTTPS:

1. Set the same host in `src/app/cards/api-config.ts`.
2. Replace both placeholder URLs in `src/app/app-hsmeta.json` with the exact customer-check and quote-pitch URLs.
3. Confirm the HubSpot app client secret is configured in Laravel, never in this project.
4. Run `npm run validate`, then `hs project validate` and `hs project dev`.

The real path uses `hubspot.fetch`, so HubSpot supplies the request signing headers. Do not replace it with browser `fetch` and do not add wildcard URLs.

## Test scenarios

The current automated tests cover:

- VIP and unknown customer mock rules
- CRM association response shapes
- Deals without an associated contact
- CRM amount normalization
- Deterministic fallback pitch generation
- Real adapter payload construction
- Non-success HTTP responses

The manual HubSpot test matrix remains:

- Deal with VIP contact
- Deal with unknown contact
- Deal without a contact association
- Laravel endpoint unavailable
- Copy action from an explicit button click

<!-- --------------------------------------------------------------- -->

## Pre-prompt (paste at top of every new chat)

---

> **VERY IMPORTANT — read and follow these instructions in order:**

### 1. Load HubSpot project context

This is a standalone HubSpot CLI project inside a larger Laravel repository. Start in `hubspot-smart-quote/` and read:

```txt
hubspot-smart-quote/README-local.md
hubspot-smart-quote/hsproject.json
hubspot-smart-quote/src/app/app-hsmeta.json
hubspot-smart-quote/src/app/cards/
```

Do not assume the root Laravel `package.json`, routes, or build process applies to this project.

### 2. Activate relevant skills

Always use:

```txt
.github/skills/hubspot-development/SKILL.md
```

Also use this when a change crosses the Laravel API boundary, request signing, AI, admin tooling, or HubSpot backend integration:

```txt
.github/skills/hubspot-laravel-integration/SKILL.md
```

Use the current official HubSpot developer documentation when behavior depends on a specific CLI command, UI Extensions SDK API, CRM hook, request-signature version, or metadata schema.

> for more direct documentation, use the tool 'Context7'

### 3. Inspect the nearest implementation

Before editing, check the sibling implementation and its tests. Preserve the existing card contract:

- Deal record sidebar card and `objectTypes: ["deals"]`
- React/TypeScript component conventions
- `hubspot.fetch` for HTTP requests, never native `fetch`
- Exact HTTPS URLs in `permittedUrls.fetch`
- Mock mode and deterministic fallback behavior
- Explicit user interaction for clipboard actions

Keep CRM data access, quote rules, HTTP adapters, and presentation logic separate. Do not add backend endpoints, MCP routes, persistence, or AI provider calls unless the task explicitly requires them.

### 4. Core rules

- Keep changes small, typed, and consistent with the existing HubSpot project.
- Do not add dependencies without explicit approval; keep them in this project, not the Laravel root.
- Do not commit account IDs, client secrets, access tokens, API keys, `node_modules`, or local CLI profiles.
- Keep production URLs exact and narrowly scoped; never use wildcard, localhost, or HTTP entries in `permittedUrls.fetch`.
- Keep deterministic customer eligibility and discount rules outside the AI layer.
- Handle missing associations, unavailable endpoints, non-success responses, and empty results as explicit UI states.
- Use a HubSpot Developer Test Account or isolated test account, never a production portal.

### 5. Validate the change

Run commands from the directory that owns the relevant `package.json`:

```bash
cd hubspot-smart-quote/src/app/cards
npm run validate
hs project validate
```

Run focused Vitest tests first when applicable. Use `hs project dev` for local CRM iteration, and use `hs project upload` or `hs project deploy` only when the task requires an account operation and the metadata and HTTPS configuration are ready.

For Laravel-side changes, run only the affected Laravel tests and the repository's documented Laravel checks in addition to the HubSpot checks. Do not run Laravel formatting or the root frontend build for a card-only change.

### 6. Tests

- Update or add focused Vitest coverage for changed card, adapter, and quote-logic behavior.
- Cover both success and failure paths, including missing contact associations and non-success HTTP responses.
- Keep tests deterministic and never make real provider or HubSpot API calls.
- Confirm `npm run validate` and, when relevant, `hs project validate` pass before finishing.

---

> **The job to be done:**
