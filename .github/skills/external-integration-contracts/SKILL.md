---
name: external-integration-contracts
description: "Use when designing, reviewing, or implementing a boundary between an application and an external system, including webhooks, REST APIs, OAuth, queues, callbacks, provider adapters, and asynchronous jobs."
argument-hint: "Describe the external system, request flow, callback, or failure contract that needs to be made reliable."
---

# External Integration Contracts

Use this skill for any integration where an external system can trigger work,
receive a result, or mutate state through the application. The same principles
apply to CRM APIs, payment providers, webhooks, SaaS APIs, AI providers, message
queues, and callback endpoints.

## Map the boundary before coding

Identify these facts first:

- who initiates the request;
- which system owns each piece of data;
- how tenant, account, user, object, and execution identity are established;
- which credentials or signatures authenticate the request;
- whether the operation is synchronous or asynchronous;
- which side effects are allowed;
- what constitutes success, retry, permanent failure, and expiration.

Classify each fact as `Implemented`, `Target`, or `Verify`. A schema validator,
route, SDK client, or mock is not evidence that the remote runtime contract has
been proven.

## Contract card

Write one card for every direction of communication:

| Field | Rule |
| --- | --- |
| Endpoint | Exact method and HTTPS URL or event name |
| Request | Typed, bounded payload with a real fixture |
| Response | Envelope, status codes, and side effects |
| Authentication | Signature, OAuth, JWT, mTLS, or explicit none |
| Identity | Tenant/account and correlation or idempotency key |
| Timeout | Per-request and whole-operation budget |
| Retry | Only transient categories, with bounded backoff |
| Duplicate | Exact behavior for repeated request, worker, and callback |
| Redaction | Secrets, tokens, PII, prompts, and provider bodies excluded |
| Source | Versioned official docs, repository behavior, or isolated evidence |

Keep transport concerns in an adapter or client. Normalize external data at the
boundary into application-owned DTOs. Do not spread third-party property names,
status codes, or response parsing throughout domain services.

## Authentication and tenant safety

Authenticate before trusting identity or reading tenant data. For signed
webhooks, validate the documented version using the exact method, URL, raw body,
timestamp, and secret. For OAuth, use the authorization flow required by the
provider, encrypt per-tenant tokens, refresh them safely, and record consent and
scope changes.

Never select a tenant from an ordinary user-editable field. Resolve the external
identity through a verified account or portal mapping before creating tasks,
querying data, or performing mutations. Reject unknown, disabled, stale,
malformed, and unauthorized requests before side effects.

## Synchronous and asynchronous design

Use a fast synchronous boundary for authentication, validation, idempotency,
persistence, and dispatch. Move slow reads, provider calls, AI, writes, and
callbacks to a durable worker. Return a small accepted or blocked response when
the external contract supports it.

Persist the task before dispatching work. Define a task lifecycle such as:

```text
accepted -> processing -> succeeded
                    \-> failed
                    \-> expired
```

Make worker claims, state transitions, external mutations, and callbacks
idempotent. Do not hold a database transaction open while waiting on network or
provider work. If an external mutation cannot be idempotent, use a durable
correlation marker or an explicit reconciliation step.

## Rate limits and retries

Separate these categories:

- validation, authentication, authorization, and contract errors: fail without
  blind retry;
- rate limiting: respect provider retry headers and use bounded backoff;
- network failures and 5xx responses: retry within the operation expiration;
- timeout: retry only when the operation is safe to repeat;
- unknown outcome after a mutation: reconcile before repeating.

The total budget includes queue delay, all downstream calls, backoff, and final
callback delivery. Record attempts and stable failure categories, not raw
provider exceptions.

## Testing the real boundary

Tests must invoke the real production controller, job, service, or adapter.
Fake only remote systems and nondeterministic infrastructure. Assert:

- exact method, URL, headers, and bounded payload;
- signature or OAuth behavior;
- tenant and correlation propagation;
- validation and redaction;
- queue dispatch and task state;
- retry and no-retry categories;
- duplicate request, worker, mutation, and callback behavior;
- safe user-visible or external output.

Use Laravel HTTP and queue fakes, AI fakes, or the equivalent framework tools.
Never call production accounts, live AI providers, or real customer data in
ordinary tests. Keep isolated account checks in a separate integration or manual
release flow.

## Verification workflow

Before implementation, consult the owning framework's current documentation
and the external platform's official documentation. For this repository use
Laravel Boost for Laravel APIs and Context7 for third-party HubSpot or provider
contracts. Confirm the exact payload, callback path, scopes, version, and rate
limit rather than relying on an old idea document.

After implementation:

1. run the narrowest boundary test;
2. run schema or metadata validation from the package that owns it;
3. run formatting and static analysis when code changed;
4. verify the flow in an isolated external account when publication or remote
   behavior is part of the claim;
5. report remaining `Target` and `Verify` items explicitly.
