# Laratrac — Agent customization guide

This document briefs a coding agent on how to tune laratrac for a specific
Laravel codebase. Read it once, then act on it.

## What laratrac does

Laratrac wraps [deptrac](https://github.com/deptrac/deptrac) to produce two
things:

- **`.laratrac/metadata.json`** — a layered map of the app (which classes
  belong to which layer, which layers depend on which) for AI agents to
  reason over.
- **`.laratrac/diagram.mmd`** — a Mermaid flowchart of the same graph, for
  humans.

It is **not** an arch-testing tool. The default ruleset is fully permissive
— every layer can depend on every other — because the goal is to *describe*
the architecture, not enforce it.

## Default behaviour (zero-config)

When no `deptrac.yaml` exists at the project root, laratrac uses an in-memory
default tuned for stock Laravel: it auto-detects layers from directories
under `app/` (Controllers, Middleware, Requests, Resources, Models, Jobs,
Events, Listeners, Mail, Notifications, Policies, Providers, Console,
Services, Actions, Repositories, Observers, Rules, Casts, Exceptions). Only
layers whose directory exists on disk are included.

This is good enough for most apps. **Customize when:**

- The app uses a non-standard layout (e.g. modules under `src/`,
  domain-driven groupings, Laravel packages in `packages/`).
- Layers should be split further (e.g. break `Service` into `Service\Billing`
  and `Service\Auth`) or merged.
- You want a layer based on something other than directory (e.g. anything
  extending `Illuminate\Database\Eloquent\Model`, anything tagged with a
  PHP attribute, anything implementing a contract).

## Inspecting current state

Run these to see what laratrac sees right now:

```bash
# The full metadata, printed inline
php artisan laratrac:json --stdout

# What's NOT covered by any layer (dead-give-away of missing layers)
vendor/bin/deptrac debug:unassigned

# What classes belong to a specific layer
vendor/bin/deptrac debug:layer Controller

# Which layers does Foo\Bar belong to?
vendor/bin/deptrac debug:token "App\\Models\\User" class-like
```

If laratrac is using its in-memory default, the last three commands need
the materialized config — run `php artisan laratrac:init` first.

## Customizing

```bash
# Materialize the auto-detected default to disk
php artisan laratrac:init
```

This writes `deptrac.yaml`. Edit it freely; laratrac picks it up on the next
run. Re-run `laratrac:json --stdout` to verify the new shape.

### deptrac.yaml structure (cheat sheet)

```yaml
deptrac:
  paths:
    - app/                    # directories deptrac scans
  layers:
    - name: Controller
      collectors:
        - type: directory
          value: app/Http/Controllers/.*    # regex on file path
    - name: Model
      collectors:
        - type: extends
          value: Illuminate\Database\Eloquent\Model   # recursive subclass match
    - name: Action
      collectors:
        - type: classNameRegex
          value: '#.*\\Action$#'           # FQCN match (note the # delimiters)
  ruleset:
    Controller: [Model, Service]   # Controller may depend on Model, Service
    Model: ~                       # Model may depend on nothing
```

### Useful collectors

- `directory` — file path regex (most common)
- `classLike` / `class` / `interface` / `trait` — class-name regex by kind
- `classNameRegex` — full FQCN regex (you supply the regex delimiters)
- `extends` — recursive subclasses of the given class
- `implements` — recursive implementers of an interface
- `uses` — classes using a trait
- `attribute` — classes/methods with a given PHP 8 attribute
- `bool` — combine others with `must` / `must_not`

Full reference: <https://github.com/deptrac/deptrac/blob/main/docs/collectors.md>

## Suggested workflow for an agent

1. Run `php artisan laratrac:json --stdout` and inspect the output.
2. Run `vendor/bin/deptrac debug:unassigned` (after `laratrac:init` if
   needed). If many classes are unassigned, the layer set is incomplete.
3. Look at the project's `app/`, `src/`, `modules/`, etc. trees and identify
   conceptual groupings the default missed.
4. Edit `deptrac.yaml` — add layers, adjust collectors. Keep the ruleset
   permissive unless the user explicitly asks for enforcement.
5. Re-run `php artisan laratrac:json --stdout`. The dependency edge counts
   should now reflect the new layer slicing.
6. Once it looks right, run `php artisan laratrac:json` (no `--stdout`) and
   `php artisan laratrac:mermaid` to write the artifacts.

## Conventions

- Output goes to `.laratrac/` at the project root.
- The ruleset is permissive by design. Don't tighten it unless asked.
- One layer per logical concept. Layers should be coarse enough that the
  graph is readable (~5–25 layers is the sweet spot).
