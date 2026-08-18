# Behaviour-Driven OpenSpec Schema

`behaviour-driven` is a proposal-to-tasks workflow for changes where contributor
intent, observable behaviour, and technical design should all be captured before
implementation.

It keeps specs mergeable by default OpenSpec archive by generating
`specs/<capability>/spec.md` files. The Markdown headings are the OpenSpec
wrapper; the content inside each requirement and scenario should be written in
Gherkin style with `GIVEN`, `WHEN`, and `THEN` steps.

- Good fit: product or platform changes with meaningful observable behaviour
  that benefits from being specified before it is built.
- Not a good fit: small tactical fixes, docs-only changes, or dependency bumps.
- Needs durable architecture decisions too? Use
  [`intent-driven`](../intent-driven/README.md) — the same workflow plus a
  per-change ADR review artifact and durable repository-level ADR files.

## Activate

Set this in `openspec/config.yaml`:

```yaml
schema: behaviour-driven
```

No other keys are required to activate the schema.

## Stage Gates

Artifact order:

```text
proposal -> (specs, design) -> tasks
```

`specs` and `design` each require only the proposal and can proceed in
parallel; `tasks` requires both.

Gate expectations:

- `proposal` states why the change matters and lists the capabilities that need
  behaviour specs.
- `specs` creates one OpenSpec Markdown delta file per capability at
  `specs/<capability>/spec.md`.
- `design` explains the implementation approach.
- `tasks` are planned only after proposal, specs, and design are complete.

## Spec Format

Use OpenSpec Markdown delta headers so archive can merge the change:

```md
## ADDED Requirements

### Requirement: User data export
The system SHALL let a user export their own saved data.

#### Scenario: Successful CSV export
- **GIVEN** a user has saved data
- **WHEN** the user exports their data as CSV
- **THEN** the system provides a CSV file containing the user's data
```

Requirements use `### Requirement:` with a SHALL/MUST description; scenarios use
exactly four hashtags (`#### Scenario:`) with `GIVEN`/`WHEN`/`THEN` steps. Every
requirement needs at least one scenario. `MODIFIED` entries copy the entire
existing requirement block from `openspec/specs/<capability>/spec.md` before
editing, so no detail is lost at archive time.

This is the same spec format the `intent-driven` schema uses.

## Executable Specs Are Skill-Provided

This schema describes the artifact workflow only. It does not define a
fenced-Gherkin format, extract `.feature` files, run an acceptance suite, or
enforce specs/code zone isolation. All of that is provided by the opt-in
[`spec-as-source`](https://github.com/intent-driven-dev/skills/tree/main/.agents/skills/spec-as-source)
skill, which treats `spec.md` as the executable source of truth and overrides
this schema's `spec.md` and `tasks.md` templates with its own references when
active. The skill also owns the two rules that used to be stated here —
acceptance tests always pass, and specs and code are never modified together in
one unit of work (`tasks.md` exempt).

`spec-as-source` requires the `gherkin-authoring` and
[`acceptance-test-authoring`](https://github.com/intent-driven-dev/skills/tree/main/.agents/skills/acceptance-test-authoring)
skills, the latter of which owns runner setup, extraction, linting, reports, and
the `stack:` key those need.

Adopt the skill when you want specs executed as acceptance tests; use the schema
alone when you want the artifact discipline without the test harness.

## Validate

```bash
openspec schema validate behaviour-driven
```

## Associated Skills

This schema declares its companion skills in `skills.txt`; they are installed automatically by Step 6 of `AGENT_INSTALL.md` into `.agents/skills/`, sourced from [intent-driven-dev/skills](https://github.com/intent-driven-dev/skills).

- `acceptance-test-authoring` — acceptance-suite setup: Gherkin extraction, effective-spec composition, runners for both stacks, linting, and reports.
- `gherkin-authoring` — writing and reviewing Gherkin/BDD scenarios.
- `glossary` — keeping domain/technical terms consistent across artifacts.
- `openspec-git-discipline` — git hygiene for OpenSpec propose/apply/archive workflows.
- `spec-as-source` — opt-in workflow making `spec.md` the executable source of truth: fenced-Gherkin authoring, acceptance-first task ordering, and specs/code zone isolation.

For more schemas, refer to https://github.com/intent-driven-dev/openspec-schemas.
