# Fordewind Reflection Checklist

## Evidence

- Current user dialogue: corrections, requested constraints, and acceptance criteria.
- Git evidence: `git status --short`, branch/base, changed files, relevant diffs.
- Verification evidence: `task test`, `task pint`, `docker compose config`, or recorded reasons they were not run.
- AI traceability: relevant files in `docs/ai-artifacts/`.

## Reuse filter

Keep a recommendation only when it is reusable, specific, measurable, and supported by evidence. Do not promote a single missed command, an unavailable external service, or an individual preference into permanent process policy.

## Preferred targets

1. `AGENTS.md` for project-wide rules.
2. Existing `.agents/skills/fordewind-*` files for workflow-specific guidance.
3. `docs/ai-artifacts/prompt-history.md` for AI prompt traceability.

## Useful commands

```bash
git status --short
git branch --show-current
git log --oneline --decorate -10
git diff --stat main...HEAD
git diff main...HEAD -- AGENTS.md .agents docs/ai-artifacts
```
