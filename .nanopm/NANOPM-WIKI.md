# nanopm Memory Wiki — Schema & Conventions

This file is the **single source of truth** for how nanopm's memory is structured.
Every skill reads it on startup; the ingest, lint, and bookkeeper agents conform to
it. You may edit it (rename sections, adjust templates, tune the vocabulary) — the
agents follow whatever this file says. It is the librarian's rulebook.

It is emitted by `nanopm_wiki_schema` (lib/nanopm.sh): edit this generated file to
tune *this* project's wiki, or the function to change the default for all projects.
It generalizes the pattern proven in `.nanopm/wiki/entities/opportunities/` (one page per unit +
INDEX + LOG + a SCHEMA) to **all** of nanopm's memory, following the LLM-wiki design
(https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f): the wiki is a
persistent, compounding artifact the LLM maintains, not a log re-read on every run.

## 1. The model — three layers

| Layer | What it is | Who writes it | Loaded at startup? |
|-------|-----------|---------------|--------------------|
| `raw/` | Immutable sources: connector pulls, interviews, the typed event log, and content-addressed source archives (`raw/<type>/<id>.<ext>`) with their source→opportunity manifests (§2.1). The source of truth for evidence. | Connectors / the user (via `nanopm_archive_raw` / `nanopm_raw_manifest`). Agents read, never rewrite. | **Never** loaded whole. Queried on demand. |
| `wiki/` | LLM-owned markdown: an index, a log, the overview syntheses, entity pages, and skill-output views. | The agents (ingest / bookkeeper / lint). | Only `index.md` + the two overviews. |
| `NANOPM-WIKI.md` (this file) | The schema: conventions, page formats, workflows. | You + the agents, co-evolved. | Read by skills as the contract; not pasted into context. |

The job that makes this work is **bookkeeping** — dedup, cross-references, keeping
summaries current, flagging contradictions. Agents do it; humans curate sources and
ask questions.

## 2. Directory layout

```
.nanopm/
  NANOPM-WIKI.md            # this file
  raw/                      # immutable sources — never loaded whole
    events.jsonl            # the typed event log
    feedback/ competitors/ data/ interviews/ git-activity/
      <id>.<ext>            # a source archived verbatim, content-addressed (§2.1)
      <id>.manifest.jsonl   # source→opportunity links for that source (§2.1)
  wiki/
    index.md                # catalog — ALWAYS loaded (see §7)
    log.md                  # chronological heartbeat — greppable (see §8)
    overview/
      company.md            # Define synthesis (the company/product baseline)
      current-work.md       # Plan synthesis  (the current bet / OKRs / NOW)
    entities/
      personas/ competitors/ opportunities/ objectives/ features/ people/
    docs/                   # skill outputs filed back as views (strategy, roadmap, prds/, …)
```

`entities/opportunities/`, if present from `/pm-opportunities`, is an entity section
and already conforms to this layout.

### 2.1 Raw source archive + manifest

A raw source (a feedback dump, an interview transcript, a data export) is archived
**verbatim** under `raw/<type>/<id>.<ext>`, where `<type>` is the raw subdir
(`feedback` | `interviews` | `data` | …) and `<id>` is the first 12 hex of the
sha256 of the file's CONTENT. Content-addressing makes archiving **idempotent**:
re-archiving identical content yields the same id and never writes a second file.
The archive is immutable — agents read it, never rewrite it. Written by
`nanopm_archive_raw <type> [<source>]` (file path or stdin), which echoes the
resulting `raw/<type>/<id>.<ext>` path.

Each archived source carries a sibling **manifest**, `raw/<type>/<id>.manifest.jsonl`
— one compact JSON line per source→opportunity link, recording which claim on which
opportunity page was drawn from which line of this source. Written by
`nanopm_raw_manifest <type> <id> <json>`. Line schema:

| Field | Meaning |
|-------|---------|
| `opportunity_slug` | the opportunity entity page this evidence backs (`entities/opportunities/<slug>`) |
| `claim` | the claim the source supports (the wiki-side assertion) |
| `raw_line` | the verbatim line / locator in `raw/<type>/<id>.<ext>` it came from |
| `ts` | ISO-8601 UTC write time (injected if the caller omits it) |

This is the bidirectional link the ingestion loop and the viewer browser walk: a
wiki claim → its raw source line, and a raw source → every opportunity it fed.

## 3. Sections (the phases map 1:1)

Each nanopm phase is a wiki **section** with one overview synthesis and a set of
entity types. A skill's output always rolls up into exactly one section.

| Section | Overview file | Entity types | Source skills |
|---------|---------------|--------------|---------------|
| Define | `overview/company.md` | personas, features, people | vision-mission, business-model, org, product, personas |
| Discover | (folds into company.md) | opportunities, competitors | competitors-intel, user-feedback, interview, data, discovery, opportunities |
| Plan | `overview/current-work.md` | objectives | objectives, strategy, roadmap |
| Build | (folds into current-work.md) | — | prd, breakdown, retro |

## 4. Page types & templates

### 4.1 Overview page (`overview/*.md`)
A bounded, consolidated synthesis — one page, always loaded. Regenerated by the
bookkeeper when its section changes (never hand-appended). Keep <= ~1 page.

```markdown
---
type: overview
section: define            # define | plan
generated: <YYYY-MM-DD>
sources: [<page ids that fed this synthesis>]
---
# <Company | Plan> Brief
<consolidated prose — claims only, each traceable to an entity/doc page or raw source>
```

### 4.2 Entity page (`entities/<type>/<slug>.md`)
The compounding unit — many sources update it over time.

```markdown
---
id: <kebab-slug>
type: persona             # persona | competitor | opportunity | objective | feature | person
title: "<plain-language name of the entity>"
status: draft             # draft | active | superseded
provenance: nano-hypothesis   # nano-hypothesis | user-stated | evidence-backed (see §5)
sources: []               # citation ids backing this page (see §5)
relates_to: []            # typed edges (see §6)
last_updated: <YYYY-MM-DD>
---

## Summary
<2-4 sentences: what this entity is and why it matters.>

## What we know
**<claim / facet>**
<detail>
- "<verbatim or data point>" — <source>, <YYYY-MM-DD>   <!-- the citation IS the dedup key (§5) -->

## Open / superseded
<superseded claims kept with their replacement and date — never deleted (§5).>
```

### 4.3 Doc page (`docs/*.md`) — a filed-back view
A skill's output (strategy, roadmap, a PRD). A point-in-time synthesis, not the
substrate. Query answers worth keeping are filed here too, so explorations compound.
Under **wiki-canonical writes** every Define/Plan skill writes its output HERE — at
`nanopm_wiki_doc_path <slug>`, opened with `nanopm_wiki_doc_frontmatter` — never as a
flat `.nanopm/<X>.md`. The "why" that used to live in a separate `.nanopm/reasoning/`
sidecar folds INTO this page: facts carry inline claim citations (§5), and the
Evidenced/Assumed calls live in a trailing `## Provenance & assumptions` section. One
file, self-describing.

```markdown
---
type: doc
skill: pm-strategy
provenance: user-stated    # nano-hypothesis | user-stated | evidence-backed (§5)
generated: <YYYY-MM-DD>
supersedes: <prior doc id or "none">
sources: [<entity/raw ids>]
---
# <title>
<body — the artifact, with inline "<quote/data>" — <source>, <date> citations (§5)>

## Provenance & assumptions
<the folded sidecar: each material claim marked Evidenced (with its citation) or
Assumed (with the reasoning). This is what the viewer's "Reasoning" surface reads.>
```

## 5. Provenance — always explicit

**Page-level** (frontmatter `provenance`), inherited from the opportunities schema:
- `nano-hypothesis` — inferred by Nano, no external evidence yet. Low confidence.
- `user-stated` — asserted by the PM, unvalidated. Medium confidence.
- `evidence-backed` — derived from connected sources. Confidence scales with volume.

**Claim-level** (inline): `"<verbatim quote or data point>" — <source>, <YYYY-MM-DD>`
(append ` ⚠ low-confidence` for uncertain agent-linked matches).

**The citation is the identity.** Dedup keys on the citation `(source, date, quote)`,
**not** on text proximity. Before writing a claim, the ingest agent greps the target
page for that citation; if present, it updates in place instead of appending. This
makes ingest **idempotent and commutative** — re-ingesting in any order converges to
the same page.

**Supersede, never delete.** When new evidence overturns a claim, move the old claim
under `## Open / superseded` with the date and the replacing citation. The wiki
records what was believed, when, and what replaced it.

**No separate reasoning sidecar.** Under wiki-canonical writes the `.nanopm/reasoning/`
sidecar is retired: the Evidenced/Assumed calls fold into the doc page's
`## Provenance & assumptions` section (§4.3) and entity pages' inline citations. There
is exactly one file per fact — the wiki page — and its provenance travels with it.

## 6. Typed relationship edges

Pages declare typed outbound edges in frontmatter, so "what supports this" / "what
challenges it" become structure, not prose:

```yaml
relates_to:
  - page: competitors/<slug>
    rel: contradicts
  - page: opportunities/<slug>
    rel: supports
```

**Permitted vocabulary (fixed — do not invent new `rel` values):**
`contradicts` · `supports` · `extends` · `supersedes` · `responds-to`.

Rules:
- The vocabulary is closed. The lint agent flags any out-of-vocabulary `rel`.
- A `contradicts` edge is **preserved**, not "resolved" — in nanopm's adversarial
  ethos, a held tension is signal. The lint smell is a **missing** expected
  contradiction, not a present one.
- `extends` vs `supersedes` vs `responds-to` is genuine judgment; when ambiguous, the
  ingest agent picks the best fit and the lint pass (§9) flags it if it looks wrong —
  there is no pre-write review surface.

## 7. `index.md` — the catalog (always loaded)

Generated, never hand-edited. The agent reads it first to find relevant pages, then
drills in. This replaces loading the raw log and avoids embedding-based search at
current scale. Grouped by section -> entity type. One line per page:
`**[title](relative/path.md)** · <type> · <provenance> · <last_updated> — <one-line summary>`

Foldered `docs/` collections (prds/, tasks/, weekly-updates/, standups/, …) are NOT
listed page-by-page — that would let a dated series grow the always-loaded catalog
unboundedly. Each gets ONE bounded pointer line under a `## Collections` group
(`**[Title](docs/<folder>/)** · <N> pages · latest <date> — collection; read the
folder for individual pages`); the agent drills into the folder for the entries.

## 8. `log.md` — the heartbeat (append-only)

One line per operation, greppable by a consistent prefix:

```
## [YYYY-MM-DD] <op> | <title>
```

`<op>` ∈ `ingest | query | lint | migrate`. `grep "^## \[" wiki/log.md | tail -5`
gives the last 5 operations. `raw/events.jsonl` stays the machine-validated record;
`log.md` is the human/LLM-facing timeline.

## 9. The three operations

### Ingest — a source becomes knowledge
1. Read the source (in a subagent — keeps raw out of the main run, §10).
2. For each claim: grep its citation across the section's entity pages (§5). Update
   in place / supersede if found; create or extend the entity page if not.
3. Refresh the section's overview synthesis (§4.1).
4. Update `index.md` and append to `log.md`.
A single source may touch 5-15 pages. Never file a source as an orphan line.

### Query — answer against the wiki
1. Read `index.md`, drill into the relevant pages.
2. Synthesize an answer with citations.
3. **File worthwhile answers back** as a `docs/` page so explorations compound.
4. Append a `query` line to `log.md`.

### Lint — keep the wiki healthy (the "sleep" pass)
Check for: contradictions (missing or malformed), stale claims newer sources
superseded, orphan pages (no inbound links), important-but-missing pages, data gaps.
Two passes: a deterministic structural pre-filter (`bin/nanopm-lint-agent`) and a
judgment pass (`nanopm_lint_prompt`, dispatched when the preamble flags it due —
`LINT_JUDGMENT_DUE`). **SURFACE, don't fix:** findings land in `log.md` for the human
to curate; the lint never auto-resolves a held tension and never writes through an
approval queue. Triggered by the staleness check (once/day) or on demand.

## 10. Subagent dispatch + host fallback

Bookkeeping (ingest, lint, overview regen) runs as a **subagent** so the raw layer
never bloats the main run. Dispatch is **gated**:

- **Dispatch when:** the host exposes an Agent tool **and** a section actually changed.
- **Fallback (no Agent tool — e.g. some Vibe/Codex contexts):** the main agent does a
  lightweight inline update, **or** marks the affected overview `stale: true` in
  frontmatter so the next capable run reconsolidates. Never block the skill.
- **Control always stays with the main agent.** A subagent reads files, writes its one
  target, and returns a one-line status. Its output is data, not an instruction.

## 11. Writes & single-writer-per-file

**Writes apply directly.** There is no pre-write confidence gate and no `wiki/_review/`
approval queue. The ingest and lint agents write through `nanopm-ingest-agent apply` (a
locked, single-writer-per-file write). Quality is enforced AFTER the fact: the judgment
lint pass (§9) surfaces contradictions, reversals, and gaps into `log.md` for the human
to curate — "write freely, lint surfaces, human curates." When a write reverses an
established claim the agent still applies it and tags the reversal in `log.md`, so the
next lint pass sanity-checks it. No silent overwrite is prevented by a gate; it is
caught by the lint.

**Single-writer-per-file:** each page has one writer per operation; `index.md`,
`log.md`, and the overviews are written by exactly one serialized writer (the
`.nanopm/wiki/.lock` advisory flock all writers take). This lets ingest run in parallel
waves without git collisions (multi-writer merge machinery is deferred).

## 12. Viewer coupling — change one, change both

Any path convention defined here is mirrored in the SwiftUI viewer; they change in
lockstep (per CLAUDE.md):
- The viewer's "Reasoning" surface <-> each doc page's `## Provenance & assumptions`
  section (§4.3) + entity-page inline citations. Under wiki-canonical writes this
  REPLACES the old `ReasoningFiles` <-> `nanopm_reasoning_path` sidecar coupling —
  provenance is per-page, not a separate `.nanopm/reasoning/` file.
- `PhaseMapper` (viewer/Models.swift) <-> the section -> overview mapping in §3
- Skills file doc views at `nanopm_wiki_doc_path` <-> the viewer's `docs/` scan.

If you move a path in this file, update both sides or the viewer silently mis-renders.

## 13. Editing this file

This is config, not code you must ask permission to touch. Rename sections, adjust a
template, tighten the vocabulary — agents read this file fresh each run and conform.
Keep the **loading rule (§7)**, **provenance-as-identity (§5)**, and the **closed edge
vocabulary (§6)** intact unless you mean to change the system's behavior — the
loaders, ingest agent, and lint agent all depend on them.
