# Repository Guidelines

## Project Structure

- Laravel application code is in `app/`; routes, migrations, factories, seeders and tests remain in their standard Laravel directories.
- Keep HTTP concerns in `app/Http`, business operations in `app/Actions`, and typed data contracts in `app/Data`.
- Frontend entry points live in `resources/js`: jQuery is used only for voting, Vue only for statistics.
- Docker files are maintained in `docker/`; project operations are exposed through `Taskfile.yml`.
- AI-assisted planning, prompts and verification reports are stored in `docs/ai-artifacts/`; record only artefacts that actually influenced the project.
- Project-agent skills live in `.agents/skills/`: use `$fordewind-migrations`, `$fordewind-endpoint-flow`, `$fordewind-tests`, or `$fordewind-reflect` for their corresponding workflows.

## Commands

- `cp .env.example .env` and `task init` initialize a local installation.
- `task up`, `task stop`, `task logs -- app`, `task ps` manage Docker services.
- `task artisan -- route:list`, `task composer -- require vendor/package`, and `task bash` run application-container commands.
- `task migrate`, `task seed`, `task import`, `task test`, `task pint`, `task ide-helper`, and `task build` run the primary development workflows.

## Architecture

- Follow `Controller -> Action -> Data` for HTTP workflows. Controllers validate/authorize requests and delegate only.
- Do not pass `Illuminate\\Http\\Request` outside a controller. Use Form Requests and explicit scalar arguments or `spatie/laravel-data` classes.
- Each Action has one public entrypoint, `run(...)`. Extract a reusable Task only when multiple Actions need the same operation.
- Classes in `app/Tasks` follow the Action `run(...)` shape, but contain no business logic. An Action may call a Task; a Task must never call an Action.
- Use `spatie/laravel-data` for request-independent input/output DTOs and API response shapes; avoid untyped associative arrays at layer boundaries.
- Keep Eloquent queries and transactions close to the Action that owns the use case. Prevent N+1 queries and aggregate vote totals in SQL.

## Style And Tests

- Follow `.editorconfig` and format PHP with Laravel Pint (`task pint` / `task pint-fix`).
- Use PHP 8.3+ typed declarations and PSR-4 naming. Name application classes by role: `*Action`, `*Data`, `*Request`, `*Controller` and `*Test`.
- Put unit tests in `tests/Unit` and HTTP/database behaviour in `tests/Feature`.
- Every behaviour change needs a deterministic test; run tests through `task test` so they execute in the project container.

## Safety

- Prefer `task` commands to ad-hoc Docker commands so operations stay project-scoped.
- Do not add secrets, local `.env` files, database dumps or source image archives to Git.
- Treat `task fresh` and `task prune` as destructive local-development operations.
