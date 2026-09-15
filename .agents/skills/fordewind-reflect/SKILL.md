---
name: fordewind-reflect
description: Reflect on a Fordewind coding session using the dialogue, Git changes, test evidence, and AI artefacts to propose focused reusable improvements.
---

# Fordewind Reflect

## Goal

Use this skill for `/reflect`, a retrospective, or a request to improve future agent workflow. Produce an actionable engineering review, not a narrative summary.

## Workflow

1. Collect evidence.
- Review the current dialogue for user corrections, scope changes, and explicit quality expectations.
- Inspect `git status` and compare the relevant change against a user-provided base branch; otherwise use `main` when it exists.
- Read only the relevant entries in `docs/ai-artifacts/` and distinguish documented facts from assumptions.

2. Extract reusable signals.
- Prioritize rules that improve Laravel boundaries, import safety, migration discipline, endpoint contracts, test reliability, Docker reproducibility, or AI-artifact quality.
- Tie every accepted signal to a dialogue event or changed file.

3. Filter aggressively.
- Keep only guidance that applies across future Fordewind tasks, can be expressed as a concrete rule, and prevents a likely repeated failure.
- Exclude one-off task details, temporary infrastructure availability, and subjective style preferences without delivery impact.

4. Recommend the smallest appropriate documentation change.
- Prefer `AGENTS.md` for cross-cutting rules.
- Update one of `.agents/skills/fordewind-*` for a repeated workflow.
- Update `docs/ai-artifacts/` only for traceability process rules.
- Create a new skill only after a distinct pattern recurs and cannot fit the existing four skills.

## Output Contract

Return these sections in order:

1. `Reusable Signals` — three to seven evidence-backed observations.
2. `Recommended Doc Changes` — target path, section, concise replacement/addition, expected impact.
3. `Skill Strategy` — update an existing skill or justify a new one.
4. `Discarded As Non-Reusable` — intentionally excluded details.
5. `Priority Next Steps` — no more than three ordered steps.

## References

- [Reflection checklist](references/checklist.md)
