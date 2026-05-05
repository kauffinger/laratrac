# laratrac

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kauffinger/laratrac.svg?style=flat-square)](https://packagist.org/packages/kauffinger/laratrac)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/laratrac/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kauffinger/laratrac/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/kauffinger/laratrac/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/kauffinger/laratrac/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kauffinger/laratrac.svg?style=flat-square)](https://packagist.org/packages/kauffinger/laratrac)

Help coding agents understand your Laravel app. Laratrac wraps
[deptrac](https://github.com/deptrac/deptrac) to produce a JSON map of your
app's layers and their dependencies — drop the file in front of Claude /
Cursor / Codex and it can navigate the codebase without guessing.

## Install

```bash
composer require kauffinger/laratrac
```

## Use

Zero config: laratrac auto-detects layers from `app/` (Controllers, Models,
Jobs, Events, Listeners, Mail, Notifications, Policies, Services, Actions,
Repositories, …). Only directories that exist on disk are included.

```bash
# JSON for agents — writes .laratrac/metadata.json
php artisan laratrac:json

# Mermaid diagram for humans — writes .laratrac/diagram.mmd
php artisan laratrac:mermaid
```

`--stdout` prints to stdout instead of writing a file. `--out=path` overrides
the destination. `laratrac:json` also accepts `--mode=graph_only` for a
slimmer output without per-layer class lists.

## Customize

```bash
# Materialize the auto-detected default to deptrac.yaml
php artisan laratrac:init

# Drop a markdown brief at .laratrac/AGENT_GUIDE.md and point your
# coding agent at it: "Read this and tune deptrac.yaml for our codebase."
php artisan laratrac:guide
```

Once `deptrac.yaml` exists at the project root, laratrac uses it as the
source of truth. The full deptrac collector reference (directory, extends,
implements, attribute, …) is at <https://github.com/deptrac/deptrac>.

Laratrac is **not** an architecture-testing tool — the default ruleset is
fully permissive. The goal is to *describe* the architecture, not enforce
it.

## License

[MIT](LICENSE.md)
