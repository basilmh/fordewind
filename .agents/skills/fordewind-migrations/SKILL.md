---
name: fordewind-migrations
description: Write and review safe Laravel migrations for Fordewind, including MySQL schema changes, guarded data fixes, rollback policy, and the separation of JSON import from migrations.
---

# Fordewind Migrations

## Overview

Use this skill for database schema changes and exceptional one-time data fixes. The primary import of auction-car JSON data belongs in the `cars:import` Artisan command, not a migration.

## Workflow

1. Classify the change.
- Schema: tables, columns, indexes, foreign keys, or constraints.
- Data: a narrowly scoped, one-time correction of existing application data.
- Import: external JSON and image ingestion. Implement or change the import command instead of a migration.

2. Generate migrations through Laravel.
- Run `task artisan -- make:migration <descriptive_name>`.
- Keep all project migrations in `database/migrations`.

3. Name and scope the migration.
- Schema migration names describe the structure change.
- Data-only migration names include `_data_`.
- Do not reference application enums, models, Actions, or DTO classes from a migration. Use stable table names and explicit scalar values.

4. Implement safely.
- Add short Russian `->comment(...)` annotations to business columns when they clarify a domain concept; omit technical fields such as timestamps.
- Add indexes for foreign keys and actual query/filter patterns.
- Every migration must decide whether its `up()` operation is still applicable. Define a private `canRunMigration(): bool` helper when the condition is more than one expression, and call it as the first statement of `up()` with an early `return`.
- For a table create, `canRunMigration()` returns `! Schema::hasTable('table_name')`. For an alter/drop, it checks the required table and column/index exist. For a data migration, it checks required tables and returns `true` only when the target rows still need changing.
- Keep the guard local and deterministic: use `Schema` and explicit `DB::table(...)->exists()` queries, never models, Actions, DTOs, seeders, or environment-dependent business services. Do not add a public `shouldRun()` method.
- Do not reuse an `up()` guard in `down()` when it tests the pre-change state. Give `down()` its own safe `Schema::has*` early return or use an explicitly safe `drop*IfExists` operation.
- Use `chunkById()` for large backfills, `upsert()` or `updateOrInsert()` for idempotent data writes, and a transaction when partial completion would corrupt a logical unit.
- Keep import-specific filesystem operations out of migrations.

5. Define rollback.
- Implement an explicit reversible `down()` where feasible.
- If irreversible by design, retain an explicit comment in `down()` explaining why.

6. Validate.
- Run `task migrate` against the local MySQL container.
- Run focused tests with `task test -- --filter=<AffectedTest>`.

## Output Contract

When proposing or implementing a migration, report:

- migration path and schema/data classification;
- `canRunMigration()` condition and the early-return behaviour in `up()`;
- affected indexes and constraint implications;
- rollback behavior;
- exact verification commands.

## References

- [Migration patterns](references/patterns.md)
