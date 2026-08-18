# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Musique Approximative — a daily music-sharing/playlist site. **Legacy PHP stack: Symfony 1.5 + Doctrine 1.4 on PHP 7.4.** Modern Symfony/Doctrine patterns do not apply here; check the existing code before assuming APIs.

Project language and docs are in French. Conventional Commits messages are in French (see `CONTRIBUTING.adoc`, changelog sections defined in `release-please-config.json`).

## Dev environment

Everything runs in Docker. Source lives under `src/` and is volume-mounted into the `php` container; PHP's built-in server runs on container port 8000, exposed as `http://localhost:8001` and proxied by Nginx at `http://localhost:8080`.

```bash
./start-dev.sh            # first-time setup: creates etc/musiqueapproximative.localhost/.env, docker-compose up -d
make start                # same, without the .env bootstrap
make stop                 # docker-compose stop
make attach               # shell into the php container (preferred for running symfony commands)
make logs                 # docker-compose logs -f
```

To run any `php symfony …` command below, either prefix with `docker-compose exec php` or use `make attach` to get a shell.

## Profiles and configuration

The repo supports multiple deployment targets through `etc/<PROFILE>/.env` files (e.g. `musiqueapproximative.localhost`, `www.musiqueapproximative.net`, `quickoschantenoel.musiqueapproximative.net`). The root `Makefile` defaults to `PROFILE := www.musiqueapproximative.net` — override with `make <target> PROFILE=musiqueapproximative.localhost` when needed.

Inside `src/`, `make configure` performs `envsubst` on every `*-dist` file using the variables from `.env`, producing the non-dist version (e.g. `app.yml-dist` → `app.yml`). **Never edit the generated non-dist files directly**; edit the `-dist` template and re-run `make configure`. The non-dist outputs are gitignored.

## Commands

### Tests

Tests use **Lime** (Symfony 1's test framework, not PHPUnit). Bootstraps live in `src/test/bootstrap/{unit,functional}.php`.

```bash
docker-compose exec php php symfony test:all
docker-compose exec php php symfony test:unit
docker-compose exec php php symfony test:functional
docker-compose exec php php symfony test:unit filter/JsonApiFilterTest   # single unit test
```

Unit tests live in `src/test/unit/`, functional tests in `src/test/functional/{admin,frontend}/`.

### Lint / syntax

CI's only code check is PHP lint:

```bash
find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;
```

Other lint (yaml, docker, shell, markdown, secrets scanning) is handled by Trunk — see `.trunk/trunk.yaml`. PHPStan is explicitly disabled.

### Symfony / Doctrine tasks

```bash
docker-compose exec php php symfony cache:clear
docker-compose exec php php symfony doctrine:build-model           # regenerate models from schema.yml
docker-compose exec php php symfony doctrine:build-forms
docker-compose exec php php symfony doctrine:data-load             # load fixtures
docker-compose exec php php symfony list                           # list all tasks
docker-compose exec php php symfony musiqueapproximative:purge-cloudflare-cache
```

Custom tasks live in `src/lib/task/` and are discovered automatically.

### Deployment

`make deploy` runs `make configure` for the target `PROFILE` then rsyncs `src/` to the remote host defined in that profile's `.env`. Default includes `--dry-run` via `RSYNC_PARAMETERS`; override for a real push: `make deploy RSYNC_PARAMETERS=`.

## Architecture

### Symfony 1 MVC layout

- `src/apps/frontend/` — public site (single app that handles all end-user traffic, including JSON endpoints).
- `src/apps/admin/` — sfGuard-protected admin UI (`post`, `sfGuardUser` modules).
- `src/config/ProjectConfiguration.class.php` — enables core plugins: `sfDoctrinePlugin`, `sfDoctrineGuardPlugin`, `sfAdminDashPlugin`, `sfFeed2Plugin`, `sfJqueryReloadedPlugin`, `sfDesastrePlugin`.
- `src/config/doctrine/schema.yml` — authoritative schema. `Post` (track metadata + markdown body, slug, relation to `sfGuardUser`) and `UserProfile` are the project-owned entities; auth/profile tables come from `sfDoctrineGuardPlugin`.
- `src/lib/model/doctrine/` — generated + hand-written models. Hand-written overrides (`Post.class.php`, `PostTable.class.php`, `UserProfile.class.php`, `UserProfileTable.class.php`) sit alongside generated `base/` classes.
- `src/lib/` also holds `filter/`, `helper/` (including `ApiResponse`, `ApiErrorResponse`, `MarkdownHelper`), `task/`, `validator/`, `vendor/` (PEAR include path is set in `ProjectConfiguration::setup`).
- `src/plugins/` — vendored plugins, committed to the repo.

### The `post` module is the whole site

Almost all routes resolve to `frontend/modules/post`. See `src/apps/frontend/config/routing.yml` and `post/actions/actions.class.php` (`executeHome`, `executeShow`, `executeList`, `executeFeed`, `executeRandom`, `executeNext`, `executePrev`, `executeMd5`, `executeOembed`). The same actions render multiple formats — templates like `showSuccess.php`, `showSuccess.json.php`, `showSuccess.csv.php`, `listSuccess.xspf.php`, `listSuccess.max.php` etc. are selected via the request format / `view.yml`.

### JSON API conventions

`JsonApiFilter` (registered in `src/apps/frontend/config/filters.yml`) rewrites the `Content-Type` of any JSON response to `application/vnd.api+json; charset=utf-8`. The response shapes themselves are **not** fully JSON:API 1.0 compliant yet — see `docs/API_CURRENT_STATE.md` (current shape per endpoint) and `docs/API_JSON_API_TARGET.md` (migration target) before changing JSON output.

Helper classes `ApiResponse` / `ApiErrorResponse` in `src/lib/helper/` are the path toward normalized responses.

### Desastre system

`sfDesastrePlugin` applies random visual/behavioral effects to pages. Recipes live under `src/web/desastres/` and are declared in `src/apps/frontend/config/desastres.yml`. Actions call `apply_desastre(...)` (loaded via the `Desastre` helper) — see how `executeShow` passes `artist`/`title`/`contributor` into it.

### Glitch logo

External service `https://gliche.constructions-incongrues.net/` is hit with `seed`, `amount`, `url` params; triggered probabilistically via `app_glitch_divisor` (1-in-N). Used in post views and RSS items.

## Commits, versioning, releases

- **Conventional Commits required.** `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`. Breaking changes use `!` or `BREAKING CHANGE:` footer.
- Release automation is **release-please** (`.github/workflows/release-please.yml`, `release-please-config.json`). It opens a release PR on every push to `main`; merging it bumps `src/VERSION`, updates `CHANGELOG.adoc`, and creates a tag. Do not hand-edit `CHANGELOG.adoc` or `src/VERSION`.
- CI on tag push (`ci.yml`) builds and publishes a Docker image to `ghcr.io/constructions-incongrues/musiqueapproximative` and creates major/minor alias tags.

## Gotchas

- **Symfony 1 is EOL.** Don't suggest modern Symfony idioms (attributes, DI container, Flex, bundles). Use `sfConfig`, `sfContext`, `loadHelpers()`, `Doctrine_Core::getTable()` patterns.
- **Doctrine 1, not Doctrine 2+.** `Doctrine_Query`, `Doctrine_Core::getTable('Post')->...`, YAML schema, act-as behaviours in schema.yml.
- After editing `schema.yml`, run `doctrine:build-model` — don't edit files under `lib/model/doctrine/base/` directly.
- Cache dir (`src/cache/`) and logs (`src/log/`) are gitignored and may need to be wiped after config changes: `docker-compose exec php php symfony cache:clear`.
- The legacy JW Player (Flash SWF) under `src/web/swf/mediaplayer-5.9/` is still referenced but broken in modern browsers; treat as legacy.
- `docs/memory-bank/README.adoc` holds a fuller project knowledge dump (in French) — consult it for domain context (contributors, desastres inventory, external services, deployment history).

## Skill routing

When the user's request matches an available skill, invoke it via the Skill tool. When in doubt, invoke the skill.

Key routing rules:

- Product ideas/brainstorming → invoke /office-hours
- Strategy/scope → invoke /plan-ceo-review
- Architecture → invoke /plan-eng-review
- Design system/plan review → invoke /design-consultation or /plan-design-review
- Full review pipeline → invoke /autoplan
- Bugs/errors → invoke /investigate
- QA/testing site behavior → invoke /qa or /qa-only
- Code review/diff check → invoke /review
- Visual polish → invoke /design-review
- Ship/deploy/PR → invoke /ship or /land-and-deploy
- Save progress → invoke /context-save
- Resume context → invoke /context-restore
- Author a backlog-ready spec/issue → invoke /spec

## Système de design

`DESIGN.md` décrit le système visuel du site tel qu'il est. Le lire avant toute
décision d'interface : polices (Arvo, Rambla), palette monochrome, grille, et le
geste central du site, l'inversion noir/blanc.

Trois points qui changent la façon de s'en servir :

- **C'est un relevé, pas une prescription.** Il consigne ce que le site fait
  aujourd'hui, y compris ses écarts. Il ne dit pas ce qu'il devrait faire.
- **La couche désastre fait partie du système.** `sfDesastrePlugin` écrase
  l'apparence de base au hasard, par conception. Ne pas signaler un désastre comme
  une incohérence visuelle.
- **Les « Écarts relevés » ne sont pas une liste de tâches.** Le premier — la
  racine `rem` annulée par `reset.css`, qui rend toute la typographie 1,6× trop
  grande — se corrige en une ligne mais change la taille de tous les textes du
  site. Ne pas le « nettoyer » au passage : c'est une décision de produit.
