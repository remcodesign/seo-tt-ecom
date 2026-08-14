# HubSpot Workflow Actions: Context7 Reference

Research date: 2026-08-13

This reference summarizes the official HubSpot Developer Documentation retrieved through Context7. It is intentionally general and should be rechecked when the HubSpot platform version, Projects CLI schema, or callback API version changes.

## Official sources

- [Define a custom workflow action](https://developers.hubspot.com/docs/apps/developer-platform/add-features/custom-workflow-actions)
- [Custom action guide](https://developers.hubspot.com/docs/api-reference/latest/automation/workflow-actions/custom-action-guide)
- [Custom action reference](https://developers.hubspot.com/docs/api-reference/latest/automation/workflow-actions/custom-action-reference)
- [Batch complete callbacks](https://developers.hubspot.com/docs/api-reference/latest/automation/workflow-actions/callbacks/batch-complete-callbacks)
- [HubSpot request signature validation](https://developers.hubspot.com/docs/apps/legacy-apps/authentication/validating-requests)

## Verified execution payload

HubSpot documents an action execution request with these areas:

- `callbackId`: unique execution and callback identity.
- `origin.portalId`: source HubSpot portal identity.
- `origin.actionDefinitionId` and `origin.actionDefinitionVersion`: action definition identity.
- `context.source` and `context.workflowId`: workflow execution context.
- `object.objectId`: the enrolled CRM record ID.
- `object.objectType`: the enrolled CRM record type.
- `object.properties`: values requested through `objectRequestOptions`.
- `inputFields`: values configured by the workflow author.

The enrolled record is the record that triggered that workflow execution. It is not a query for the globally newest record. A Deal workflow should use `object.objectId` as the Deal ID and should not ask the workflow author to provide the ID as a normal input field.

## Metadata implications

`objectTypes` limits the CRM object types where an action is available. `objectRequestOptions.properties` controls which enrolled-object properties are included in the execution payload. `inputFields` are user-configurable values and may be empty when the action derives its inputs from the enrolled object and backend lookups.

Projects schema versions can impose additional metadata requirements. In the tested project schema, empty `inputFieldLabels` and `inputFieldDescriptions` maps are required when `inputFields` is empty. The installed CLI is authoritative for the final shape; run `hs project validate`.

## Response controls

HubSpot documents these special response keys inside `outputFields`:

```json
{
  "outputFields": {
    "hs_execution_state": "BLOCK",
    "hs_expiration_duration": "PT15M",
    "status": "accepted"
  }
}
```

- `hs_execution_state` controls workflow progression. Documented values include `BLOCK`, `SUCCESS`, `FAIL_CONTINUE`, and an async option depending on the API version.
- `hs_expiration_duration` is optional for a blocked action and uses ISO 8601 duration syntax.
- Application-owned outputs should use their own names, such as `status`, `summary`, `taskId`, or `resultJson`; do not treat `hs_*` control keys as domain fields.

A blocked action is completed later through the documented callback completion endpoint. The completion request must carry the callback ID and bounded output fields. Keep callback authentication, retry behavior, and idempotency explicit.

## Failure and retry behavior

The reference describes successful 2xx responses, failed 4xx responses, and retryable 5xx responses. Rate limiting has special retry handling and should respect `Retry-After`. A backend must still apply its own bounded retry policy for queue jobs, CRM calls, AI calls, and callback delivery.

## Project-specific verification note

This repository's installed HubSpot CLI accepted the current `workflow-action` metadata after these checks:

```bash
hs project validate
php -r 'json_decode(file_get_contents("src/app/workflow-actions/warehouse-recommendation-v3-hsmeta.json"), true, 512, JSON_THROW_ON_ERROR);'
```

That local result validates this project's schema, not every HubSpot platform version. Other projects must validate their own metadata with the CLI version used for deployment.
