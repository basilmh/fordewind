# Agent Configuration

Project-specific skills are stored in `.agents/skills/`. They are intentionally scoped to the Fordewind application and must not assume the modular structure, Passport authentication, PostgreSQL, Redis, or fintech conventions of other projects.

| Skill | Use for |
| --- | --- |
| `$fordewind-migrations` | Schema changes, safe data corrections, and migration review. |
| `$fordewind-endpoint-flow` | Laravel JSON endpoints for voting and statistics. |
| `$fordewind-tests` | PHPUnit unit and feature tests. |
| `$fordewind-reflect` | A post-task retrospective based on dialogue and Git changes. |

Each skill includes a concise checklist and project-local references. Update an existing skill when a reusable workflow changes; do not add a skill for a single task.
