---
name: hubspot-workflow-actions
description: "Use when creating, reviewing, validating, versioning, deploying, or integrating HubSpot custom workflow actions, including hsmeta files, enrolled CRM object context, input and output contracts, signatures, asynchronous blocking, callbacks, Laravel intake endpoints, and destructive component changes."
argument-hint: "Describe the HubSpot workflow action, payload contract, metadata, or deployment change."
---

# HubSpot Workflow Actions

Use this skill for reusable HubSpot custom workflow-action work across projects. Treat the HubSpot action metadata, the runtime request payload, the backend response, and the deployment lifecycle as one contract.

## First classify the change

Identify which boundary is changing before editing:

- **Metadata:** `*-hsmeta.json`, action URL, object types, inputs, outputs, labels, publication.
- **Execution payload:** portal identity, workflow context, enrolled object, callback ID, configured fields.
- **Backend intake:** signature verification, validation, tenant resolution, idempotency, queue dispatch.
- **Async completion:** blocked response, callback completion, retries, expiration, failure branch.
- **Versioning:** action UID, schema compatibility, migration, deprecation, removal.

Do not claim an end-to-end workflow is implemented when only metadata or a synchronous proof of concept exists.

## Locate the project boundary

Start at the HubSpot project root containing `hsproject.json`:

```text
project/
├── hsproject.json
└── src/
    └── app/
        └── workflow-actions/
            └── <action>-hsmeta.json
```

Read the project README, `hsproject.json`, the nearest action metadata, the backend route, and focused tests before changing a contract. Use the package directory that owns `package.json` for JavaScript validation.

## Metadata contract

A workflow action normally defines:

- A unique `uid` and `type: "workflow-action"`.
- An exact public HTTPS `actionUrl`.
- `supportedClients` including `WORKFLOWS`.
- `objectTypes` for the enrolled CRM object. Use the representation required by the installed Projects schema and confirm it with `hs project validate`.
- `inputFields` for values configured by the workflow author. Keep this empty when the backend can derive everything from the enrolled object context.
- `objectRequestOptions.properties` for properties to include from the enrolled object. The enrolled object ID is supplied separately as `object.objectId`.
- Bounded, string-compatible `outputFields` for values that later workflow actions consume.
- English labels for the declared inputs and outputs. Some Projects schema versions require empty `inputFieldLabels` and `inputFieldDescriptions` even when `inputFields` is empty.
- An explicit `isPublished` decision. Keep a new action unpublished until its contract and test-account flow are ready unless the release requirement says otherwise.

Do not put the enrolled Deal or Contact ID in `inputFields` merely to identify the record. That creates a user-editable duplicate of HubSpot's trusted object context.

### Reserved response controls

These are HubSpot control keys, not application-owned output fields:

```json
{
  "outputFields": {
    "hs_execution_state": "BLOCK",
    "hs_expiration_duration": "PT15M"
  }
}
```

Use `hs_execution_state` with `BLOCK`, `SUCCESS`, `FAIL_CONTINUE`, or the version-supported async value. Use `hs_expiration_duration` only with an ISO 8601 duration. Do not declare these as custom business outputs unless the installed schema explicitly requires that behavior. Keep application outputs named without the reserved `hs_` prefix, such as `taskId`, `status`, `summary`, and `resultJson`.

## Runtime request contract

A custom action execution request contains the following conceptual shape:

```json
{
  "callbackId": "execution-id",
  "origin": {
    "portalId": 123,
    "actionDefinitionId": 456,
    "actionDefinitionVersion": 1
  },
  "context": {
    "source": "WORKFLOWS",
    "workflowId": 789
  },
  "object": {
    "objectId": 904,
    "objectType": "DEAL",
    "properties": {
      "hs_object_id": "904"
    }
  },
  "inputFields": {}
}
```

Treat `origin.portalId`, `callbackId`, `context`, and `object.objectId` as external input that must be validated. The signed request is the source of trusted identity; ordinary input fields must not select a tenant or authorize a mutation. Normalize the external payload into a small internal DTO before business logic.

The action receives the object enrolled in the workflow, not an arbitrary globally last-created or last-updated record. A Deal-created workflow supplies the Deal that enrolled in that execution. Re-enrollment rules determine whether later updates trigger another execution.

## Backend boundary

For a Laravel or similar backend:

1. Verify the HubSpot signature using the documented version and exact request URL, method, raw body, timestamp, and configured secret.
2. Reject missing, malformed, stale, or mismatched signatures before controller or service work.
3. Resolve the verified portal to exactly one enabled tenant before reading or mutating tenant data.
4. Validate callback ID, object type, object ID, workflow context, and any configured inputs with a typed request DTO.
5. Use idempotency keyed by portal, action version, callback or execution ID, and the canonical input hash.
6. Keep the synchronous action URL fast: validate, persist or find the task, dispatch work, and return the bounded response.
7. Do not call AI, inventory systems, slow CRM APIs, or other remote services while holding a database transaction.
8. Redact tokens, raw prompts, complete signature material, provider bodies, and unnecessary CRM data from logs and workflow outputs.

Controllers should remain thin. Services own tenant-aware business rules and side effects. Database mutations belong in transactions; external calls happen outside transactions with explicit idempotency and compensation behavior.

## Asynchronous actions

For slow work, return HTTP 2xx with a blocked output:

```json
{
  "outputFields": {
    "hs_execution_state": "BLOCK",
    "hs_expiration_duration": "PT15M",
    "taskId": "task-01J...",
    "status": "accepted"
  }
}
```

The worker should claim the task, process it outside the intake request, persist a bounded redacted result, and complete the callback with `SUCCESS` or `FAIL_CONTINUE`. Retry transient network and 5xx callback failures with bounded backoff. Do not blindly retry 4xx contract or authorization failures. Duplicate intake requests, workers, and callbacks must be harmless.

Do not confuse a blocked action with a durable queue. The backend still needs durable task state, expiration handling, retry policy, and callback audit data.

## Versioning and removal

Treat changes to input names, output names, types, required flags, meaning, object type, action URL, or callback behavior as contract changes.

- Preserve the existing UID and metadata path for compatible updates.
- For a breaking contract, publish a new action version with a new UID and migrate workflows deliberately.
- Keep both versions supported during migration when rollback is required.
- Before removing a component, confirm no active workflows use it.
- Upload with auto-deploy disabled:
  - `hs project upload --skip-auto-deploy`, inspect the destructive diff, 
  - and use `hs project deploy --build=<id> --force` only when removal is intentional.
    - `--force` acknowledges removal; it does not migrate workflow references.

Never rename a deployed metadata file casually. A local rename can be interpreted as remote removal plus addition.

## Validation and testing

Run from the correct project directories:

```bash
cd <hubspot-project>
hs project validate

cd src/app/<package-with-package-json>
npm run validate
```

For backend changes, run the narrowest feature tests first, then formatting and static analysis. Test at least:

- Valid signed request and exact payload normalization.
- Missing, invalid, stale, altered-body, and URL-mismatch signatures.
- Unknown, disabled, and missing portal-to-tenant mappings.
- Object ID/type validation and missing required execution context.
- Duplicate execution idempotency and no duplicate CRM mutation.
- Fast blocked response without AI or slow external calls.
- Callback success, bounded transient retry, permanent 4xx failure, expiration, and `FAIL_CONTINUE`.
- Bounded output and redaction of secrets, prompts, stack traces, and provider details.

Use a HubSpot Developer Test Account or isolated test account. Never run automated tests against production HubSpot or a real AI provider.

## Sources

The Context7 research snapshot is in [`references/hubspot-workflow-actions-context7.md`](references/hubspot-workflow-actions-context7.md). Use the linked official documentation for current schema and API behavior because HubSpot can change platform versions and callback details.
