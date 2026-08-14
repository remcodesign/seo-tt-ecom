---
name: ai-workflow-contracts
description: "Use when building or reviewing a Laravel AI feature that sits inside a fixed business workflow or external action contract. Trigger for structured JSON agent output, AI-assisted selection or recommendation, provider adapters, caller-controlled timeouts, model failover, AI observability, raw-output debugging, or validating AI proposals against Laravel-owned business rules."
argument-hint: "Describe the AI workflow, structured result contract, or provider-boundary change."
---

# AI Workflow Contracts

Use this skill when AI is one bounded step inside an application workflow, especially a Laravel service called by a HubSpot action, CRM extension, webhook, or admin test surface. The workflow must remain deterministic and owned by Laravel; the model proposes, Laravel decides whether the proposal is valid.

## Documentation First

Before changing an AI workflow, verify the installed API instead of guessing:

1. Use Laravel Boost `search-docs` for the installed Laravel and Laravel AI versions. Search broad topics such as `structured output`, `agent testing`, `failover`, and `timeout`.
2. Use Context7 only for an official library source after resolving its library ID. For Laravel AI, prefer `/laravel/ai` and select the installed or compatible version when available.
3. If documentation search is unavailable, inspect the installed package source and existing project skills. Record the uncertainty in the implementation decision rather than inventing an API.

This project currently uses Laravel 13, PHP 8.4, and `laravel/ai` 0.10.x. Confirm versions with Laravel Boost before relying on examples.

## Boundary Design

Keep the fixed workflow in Laravel:

- The controller, workflow action, or Livewire action validates input and delegates to a focused service.
- The service assembles a bounded prompt from known facts and candidate data.
- The agent produces a proposal with a small, explicit schema.
- Laravel validates the proposal against authoritative application data and business invariants.
- The external caller receives a stable application-owned result, not a provider response object.

Do not let an AI agent decide authorization, discount limits, inventory safety, order splitting, persistence, or other rules that must be deterministic. Do not add an autonomous agent, MCP route, or fallback architecture merely because the idea document mentions it; implement only the requested workflow boundary.

## Workflow completion contract

When the AI step is called by a webhook, workflow action, queue job, or other
long-running process, specify the whole lifecycle rather than only the model
response:

- Define the fast intake response separately from the eventual result.
- Keep reserved transport controls, such as blocked or completed execution
    states, out of the business result DTO unless the external contract requires
    them there.
- Persist a correlation or idempotency key before dispatching work.
- Make timeout, retry, expiration, duplicate worker, and duplicate callback
    behavior explicit.
- Complete external callbacks with a bounded application result, never with a
    provider response object or raw model output.

The caller-controlled timeout is only one part of the budget. The specification
must also fit queue delay, deterministic reads, model calls, writes, callback
retries, and the external workflow expiration window.

## Structured Agent Output

For typed JSON, implement `Laravel\Ai\Contracts\HasStructuredOutput` alongside `Agent` and `Promptable`, and define the schema with `Illuminate\Contracts\JsonSchema\JsonSchema`:

```php
final class RecommendationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function schema(JsonSchema $schema): array
    {
        return [
            'selected_id' => $schema->string()->required(),
            'reason' => $schema->string()->required(),
        ];
    }
}
```

Keep the schema small and bounded. Require identifiers and explanations that the caller actually needs. State the allowed candidate identifiers and business constraints in the instructions and prompt, but treat those instructions as guidance, not validation. Models or providers may place fields differently, return extra fields, or ignore part of a schema; normalize only at the service boundary and reject proposals that cannot be validated.

When an external provider returns a compatible variant, support the smallest explicit compatibility normalization, for example a top-level `reason` and a nested `selected_item.reason`. Do not spread provider-specific parsing across controllers, Blade, or frontend code.

## Server-Authoritative Validation

For selection and recommendation workflows:

- Match the model's identifier against the supplied candidate set.
- Re-read the candidate's authoritative display name and attributes from Laravel-owned data.
- Enforce eligibility such as `available_quantity >= requested_quantity`.
- Enforce one-item or no-splitting rules in Laravel.
- Treat an invalid, missing, malformed, or ineligible AI proposal as an error.
- Do not silently select the first candidate or invent a fallback warehouse when the AI fails unless the product contract explicitly defines that fallback.

Return a stable result shape with nullable selection, explicit error state, normalized reason, and optional raw output for an authorized diagnostic surface. Keep raw provider output out of public workflow responses unless the contract explicitly requires it.

## Provider Adapter

Isolate provider calls in one service or adapter:

- Pass provider, model, and timeout explicitly at the call site.
- Allow the caller or workflow profile to control the timeout; keep configuration as the default.
- Fail over only for explicitly failoverable provider exceptions.
- Treat missing credentials, missing models, ordinary connection failures, empty output, oversized output, and malformed structured output as visible, testable errors.
- Preserve the last meaningful error for the workflow or admin surface.
- Log provider, model, usage, output length, and monotonic request duration such as `duration_ms`.
- Never log API keys, authorization headers, secrets, or unnecessary customer PII.

Provider response objects and SDK-specific metadata must not leak through controller, Livewire, HubSpot, or frontend contracts. Convert them to small arrays or DTOs at the adapter boundary.

## Testing With Pest

Use the real production service, controller, or Livewire component as the system under test. Fake only the AI provider or other external boundary:

```php
RecommendationAgent::fake([
    ['selected_id' => 'warehouse-local', 'reason' => 'Fastest eligible option.'],
])->preventStrayPrompts();
```

Cover distinct behavior, not just fixture values:

- Valid structured recommendation selects the eligible candidate.
- Stock or eligibility rules reject an ineligible model choice.
- Missing configuration exposes an error instead of silently selecting a candidate.
- Provider exceptions and failover use the intended model sequence.
- Empty, oversized, malformed, and provider-variant structured output is normalized or rejected as specified.
- The prompt contains the relevant facts and constraints.
- The final response contains the stable application-owned shape and canonical candidate data.
- Raw AI output is shown only where the diagnostic contract allows it.

Use focused Pest tests first, then the repository checks. Do not make real provider requests from tests.

## Verification

For this Laravel repository, run the affected Pest files first, then:

```bash
ddev composer format-basic
```

`ddev composer format-basic` runs the project's formatting, static analysis, frontend typecheck, and full test workflow. Keep changes scoped to the owning agent, provider adapter, service contract, and focused tests.
