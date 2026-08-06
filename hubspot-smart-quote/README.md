# HubSpot Smart Quote

Standalone HubSpot CLI project for a Deal record card. It is intentionally separate from the Laravel application in the repository root.

## Current milestone

The card runs in mock mode by default:

- Deal: `VIP Website Renewal`
- Amount: `12000`
- Contact: `vip@example.test`
- VIP result: `15%` allowed discount and lifetime value `4500`
- Quote text: deterministic fallback text

No Laravel endpoint, HubSpot account, client secret, or AI provider is required for this milestone. The mock API and pure quote logic are covered by Vitest.

## Project layout

```text
hubspot-smart-quote/
├── hsproject.json
├── package.json
└── src/app/
    ├── app-hsmeta.json
    └── cards/
        ├── mock-data.ts
        ├── quote-client.ts
        ├── quote-client.test.ts
        ├── quote-logic.ts
        ├── quote-logic.test.ts
        ├── smart-quote-card-hsmeta.json
        └── smart-quote-card.tsx
```

## Local checks

```bash
cd hubspot-smart-quote
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

1. Set the same host in `src/app/cards/config.ts`.
2. Replace both placeholder URLs in `src/app/app-hsmeta.json` with the exact customer-check and quote-pitch URLs.
3. Set `MOCK_MODE` in `src/app/cards/config.ts` to `false`.
4. Confirm the HubSpot app client secret is configured in Laravel, never in this project.
5. Run `npm run validate`, then `hs project validate` and `hs project dev`.

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
