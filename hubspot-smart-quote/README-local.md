# HubSpot Smart Quote

Standalone HubSpot CLI project for a private Deal record sidebar card. The project targets HubSpot platform `2026.03` and remains separate from the Laravel application in the repository root.

The card reads Deal properties and the first associated Contact through the HubSpot UI Extensions SDK. A sales user explicitly starts the customer check and quote-pitch requests; the card sends those requests to the configured Laravel API through `hubspot.fetch`.

## Project layout

- `hubspot-smart-quote/`: HubSpot CLI project root; run `hs project` commands here.
- `hubspot-smart-quote/src/app/`: HubSpot app metadata and source directory.
- `hubspot-smart-quote/src/app/cards/`: React/TypeScript card package; run npm commands here.

The card is registered at `crm.record.sidebar` for Deal records only (`objectTypes: ["deals"]`). The app uses private static authentication with read scopes for Deals and Contacts.

## Validate the project

Install dependencies and run the card checks from the directory that owns `package.json`:

```bash
cd hubspot-smart-quote/src/app/cards
npm install
npm run validate
npm run validate_hs
```

`npm run validate` runs ESLint, TypeScript, and Vitest. `npm run validate_hs` runs those checks and then `hs project validate`. The root Laravel `package.json` and build process are not used for this project.

To validate only the HubSpot project metadata and structure:

```bash
cd hubspot-smart-quote
hs project validate
```

## Local HubSpot development

Install and authenticate the official HubSpot CLI with a HubSpot Developer Test Account or another isolated test account:

```bash
npm install -g @hubspot/cli
hs init
hs account list
```

Start local development from the HubSpot project root. Replace the example account IDs with the project and test-account IDs from your HubSpot developer account:

```bash
cd hubspot-smart-quote
hs project dev \
  --project-account xxx \
  --testing-account xxx \
  --debug
```

Use `hs project upload` to create an account build and `hs project deploy` only when that build is ready to be activated:

```bash
cd hubspot-smart-quote
hs project upload
hs project deploy
```

### Removing a HubSpot component

Renaming a component metadata file and changing its `uid` makes HubSpot treat the change as a removal of the old component and an addition of a new component. For example, changing `warehouse-recommendation-v1` to `warehouse-recommendation-v2` can produce a destructive-action warning for the deployed `v1` component.

Before removing the old component, confirm that no active workflows still use it. Upload the project without automatically deploying the build so the removal can be reviewed:

```bash
cd hubspot-smart-quote
hs project upload --skip-auto-deploy
```

Review the build warning and note the build ID. Deploy the build with `--force` only after confirming that removing the old component is intentional:

```bash
hs project deploy --build=<build-id> --force
```

The `--force` flag acknowledges the removal warning. It does not migrate existing workflows or preserve references to the removed component. If the existing component must remain in use, keep its metadata filename and `uid`, and update it in place instead of renaming it.

Keep the existing card contract when changing metadata: Deal object type, sidebar location, card entrypoint, required CRM read scopes, and exact HTTPS `permittedUrls.fetch` entries.

## Laravel API configuration

The card currently uses the Laravel Cloud API configured in `src/app/cards/api-config.ts`:

```text
https://seo-tt-ecom-production-0oxgks.laravel.cloud/api/hubspot
```

If the backend host or endpoint paths change:

1. Update `src/app/cards/api-config.ts`.
2. Keep the exact customer-check and quote-pitch URLs in `src/app/app-hsmeta.json` synchronized with the adapter paths.
3. Confirm the Laravel client secret is configured on the backend, never in this project.
4. Run `npm run validate_hs` from `src/app/cards`, then use `hs project dev` from `hubspot-smart-quote`.

All external requests use `hubspot.fetch`; do not replace it with browser `fetch`. Fetch permissions must remain exact HTTPS URLs and must never use wildcards, localhost, HTTP, tokens, or secrets.

## Test coverage and manual checks

The automated Vitest tests cover:

- VIP and unknown customer mock rules
- CRM association response shapes
- Deals without an associated contact
- CRM amount normalization
- Real adapter payload construction
- Non-success HTTP responses

Use test doubles in Vitest; do not call the real Laravel API from automated tests. For manual verification, use an isolated HubSpot Developer Test Account and check:

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

Use this when the change needs requirements clarified, behavior decomposed into testable parts, or an implementation plan and validation criteria captured as specs:

```txt
.github/skills/specs-as-code-architect/SKILL.md
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

- Run focused Vitest tests first when applicable. Use `hs project dev` for local CRM iteration
- !IMPORTANT Never use `hs project upload` or `hs project deploy` on your own, that is for me to call on the CLI (and I will only do it when the task requires an account operation and the metadata and HTTPS configuration are ready.)

For Laravel-side changes, run only the affected Laravel tests and the repository's documented Laravel checks in addition to the HubSpot checks. Do not run Laravel formatting or the root frontend build for a card-only change.

### 6. Tests

- Update or add focused Vitest coverage for changed card, adapter, and quote-logic behavior.
- Cover both success and failure paths, including missing contact associations and non-success HTTP responses.
- Keep tests deterministic and never make real provider or HubSpot API calls.
- Confirm `npm run validate` and, when relevant, `hs project validate` pass before finishing.

### 7. Need more info? use `context 7`

---

> **The job to be done:**
