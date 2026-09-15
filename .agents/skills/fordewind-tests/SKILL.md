---
name: fordewind-tests
description: Write and review deterministic PHPUnit tests for Fordewind using Laravel factories, an isolated MySQL test database, session-aware feature tests, and focused Task commands.
---

# Fordewind Tests

## Overview

Use this skill for new or changed PHPUnit tests in this repository.

## Test Environment

- `phpunit.xml` and `task test` run against the dedicated `mysql-test` service. `docker/scripts/test.sh` maps `DB_TEST_*` from `.env` to the process `DB_*` values.
- The test database has its own Docker volume and is never the development `mysql` service.
- This project has no mandatory global seed baseline. Create only the records required by the scenario using factories.
- Use `RefreshDatabase` in Feature tests and every database-backed test class so each test has an isolated schema/data state.

## Workflow

1. Choose the suite.
- Unit: deterministic rules, pair-selection algorithms, Data mapping, and isolated Actions.
- Feature: HTTP contract, validation, session state, Eloquent persistence, and statistics aggregation.

2. Place the test.
- `tests/Unit/*Test.php` for isolated behaviour.
- `tests/Feature/*Test.php` for framework, HTTP, database, or session behaviour.

3. Create deterministic state.
- Prefer model factories and explicit factory states over hand-written inserts.
- Create only the cars, model values, years, photos, and votes the scenario needs.
- Use fixed values or seeded Faker where random values affect the assertion.
- Put every fixture that creates database records, source files, request payloads, or fake responses into a named private helper method. Keep test methods focused on scenario setup through helpers, action, and assertions.

4. Assert observable behaviour.
- HTTP tests assert status, JSON contract, and DB effects.
- Pair-cycle tests assert distinct IDs, membership in the chosen model, and no repeated photo before exhaustion.
- Statistics tests assert filters, row vote counts, and filtered total vote count.
- Use fakes for notifications, queues, events, storage, mail, and HTTP only when the code under test touches them.

5. Name tests clearly.
- Test methods must use descriptive `camelCase` names. Do not use snake_case or `test...` prefixes.
- Mark every test method with `#[Test]` and describe its expected behaviour with `#[TestDox('...')]`.
- Use PHPUnit data providers for stable scenario matrices, with human-readable case keys.

6. Run targeted verification.
- `task test -- --filter=ClassOrMethod`
- `task test` before handoff when the change affects shared behaviour.

## Output Contract

State test suite and path, database reset strategy, factory/fake strategy, cases covered, and exact command results.

## References

- [Testing patterns](references/patterns.md)
