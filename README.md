# cluster-ui

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Unofficial package](https://img.shields.io/badge/status-unofficial-orange.svg)](#)

Dashboard, Profile, Settings, and Users pages for a CodeIgniter 4 +
[Shield](https://github.com/codeigniter4/shield) + [Settings](https://github.com/codeigniter4/settings)
app, built on [Tabler](https://tabler.io/) and [Apache ECharts](https://echarts.apache.org/).

*Unofficial — not affiliated with, or endorsed by, the CodeIgniter Foundation.*

## Features

- **Dashboard** — a live network graph, a per-table size/records/traffic sunburst, and a
  per-node activity tree (each with a data table underneath), all powered by
  [ad-go/cluster](https://github.com/ad-go/cluster) if it's installed — silently absent
  otherwise, no configuration needed to opt out
- **Settings** — site title/logo/footer/accent color, plus a per-node table (type, URL, FTP
  credentials) for the nodes ad-go/cluster knows about; every field autosaves, no save button
- **Profile** — avatar, name, email, phone, password, all reactive/autosaving as well
- **Users** — Shield-backed create/edit/delete/ban with group assignment, confirmation modals
  instead of native `confirm()`
- Dark/light theme, English/Romanian language switcher, resizable/responsive layout
- If ad-go/cluster is installed with more than one public peer, an "Auto" switch and
  "Switch server" dropdown surface its real cross-node SSO handoff in the UI

## Requirements

- A CodeIgniter 4 app
- [Shield](https://github.com/codeigniter4/shield) — needed for authentication itself: login/
  logout, the Users page's group/ban management, and the account this UI's Profile page edits
- [Settings](https://github.com/codeigniter4/settings) — needed for every autosaving field this
  UI has (the Settings page itself, plus the per-node Nodes/Databases tables), stored per-context
  so each node keeps its own values
- Tabler's CSS/JS under `public/assets/tabler/` — not bundled here, self-host it from its own
  CDN (Apache ECharts, under `public/assets/echarts/`, ships committed inside
  [ad-go/cluster](https://github.com/ad-go/cluster) instead, when that package is installed)
- SVG flags for the language switcher under `public/assets/flags/` — e.g. from
  [flag-icons](https://github.com/lipis/flag-icons)

## Installation

`app/` and `public/assets/` are meant to be laid on top of your CodeIgniter 4 app root,
overwriting any files at the same path — not autoloaded from `vendor/`.

**From an archive** — download a [release](https://github.com/ad-go/cluster-ui/releases) zip
(or `main`), extract it, copy `app/` and `public/assets/` into your app root, then:

```console
php spark migrate
```

**With Composer** — not on Packagist, so it needs a repository entry pointing at GitHub first
(`"type": "git"`, not `"vcs"` — the latter hands resolution to Composer's `GitHubDriver`,
which needs `api.github.com` and is rate-limited to 60 unauthenticated requests/hour/IP,
found live 2026-08-20 to silently fall back to a `git clone`-over-SSH once exhausted and fail
outright with no SSH key configured; plain `"git"` is a bare HTTPS clone, no API involved at
all). Composer 2.2+ will also ask you to trust this package's own plugin:

```console
composer config repositories.ad-go-cluster-ui git https://github.com/ad-go/cluster-ui.git
composer config allow-plugins.ad-go/cluster-ui true
composer require ad-go/cluster-ui:@dev
```

That's the only install step needed — the bundled Composer plugin copies `app/`/
`public/assets/` into your project root and runs `php spark migrate` for you, on install and
on every later update.

See this package's own source under `app/Controllers/` and `app/Views/` for how the Settings
Nodes/Databases tables, and account ban/unban actually work under the hood — the in-app docs
page this section used to point to was removed 2026-08-20.

## License

MIT — see [LICENSE](LICENSE).
