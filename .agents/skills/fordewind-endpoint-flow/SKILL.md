---
name: fordewind-endpoint-flow
description: Add or modify Fordewind JSON endpoints through Form Request or Data DTO, Action, Controller, route, and PHPUnit tests, while preserving anonymous session-based voting behaviour.
---

# Fordewind Endpoint Flow

## Overview

Use this skill when adding or changing a JSON endpoint for the voting page or statistics page.

## Route Decision

- Rendered pages belong in `routes/web.php`.
- The anonymous voting flow depends on Laravel session state and CSRF protection. Its JSON endpoints must use the `web` middleware, normally as prefixed routes in `routes/web.php`.
- A stateless public statistics endpoint may use `routes/api.php` only when its route registration and middleware are intentionally configured.
- Do not introduce Passport, Sanctum, or policies unless a real authenticated requirement is added.

## Core Flow

1. Define the endpoint contract.
- Specify path, method, input, response shape, validation failures, and empty-state response before implementation.

2. Validate and normalize input.
- Create a Form Request in `app/Http/Requests` when the endpoint has request validation.
- Use a `spatie/laravel-data` class in `app/Data` for a typed request-independent input or output boundary.
- Do not pass `Illuminate\\Http\\Request` beyond the controller.

3. Implement the business operation.
- Place endpoint behaviour in an `app/Actions/*Action.php` class with one public `run(...)` entrypoint.
- Extract a focused class into `app/Tasks` only when more than one Action needs it. Tasks use the same `run(...)` entrypoint shape, contain no business logic, and must never call an Action; an Action may call Tasks.
- Use Eloquent relationships and transactions where appropriate; avoid N+1 queries and return an explicit domain result or Data object.

4. Wire the controller and route.
- Use a controller in `app/Http/Controllers` to coordinate validation, Action invocation, and response serialization.
- Register a named route with the narrowest suitable middleware.
- For vote submission, retain CSRF and validate that both submitted cars belong to the selected model and are distinct.

5. Test the complete contract.
- Add a Feature test for success, validation errors, empty data/state, and database side effects.
- Cover session-cycle rules and race-sensitive vote validation with Unit tests when they are non-trivial.

6. Validate.
- Run `task test -- --filter=<ChangedTest>`.
- Run `task pint` when formatting has not already been applied.

## Output Contract

Report the route path and name, request/Data/Action/Controller files, middleware choice, relevant session or validation rule, tests, and exact verification commands.

## References

- [Endpoint checklist](references/checklist.md)
