# Menu Inventory

This document captures the current menu layers and how they relate to real router-backed pages.

## Active Top-Level Menu Source

The active main menu source is [core/default_components.php](/C:/Git/SPP-Armory-Website/core/default_components.php). These groups are current:

| Group | Link target | Actual route target | Status | Notes |
| --- | --- | --- | --- | --- |
| `1-menuNews` | `index.php` / forum archive | `frontpage/index`, `forum/viewforum` | live | current entry menu |
| `2-menuAccount` | account and admin links | mixed `account/*`, `server/rules`, `admin/index` | live | privilege-gated items |
| `3-menuGameGuide` | connect and bot guide | `server/connect`, `server/botcommands` | live | nav group over two server-owned guide pages |
| `4-menuWorkshop` | server tools | `server/realmstatus`, `playermap`, `statistic`, `ah`, `sets`, `downloads` | live | current workshop group |
| `6-menuForums` | forum links | `forum/index`, `forum/viewforum` | live | forum home + archive |
| `7-menuArmory` | armory-style server pages | `server/chars`, `guilds`, `honor`, `talents`, `items`, `marketplace` | live | server-backed, not standalone `armory` |
| `8-menuSupport` | bug tracker and external links | `forum/viewforum` + external URLs | live | mixed internal/external support |

## Compatibility Leftovers

The old menu ids have been normalized out of the active component registry. What remains is legacy routing compatibility, not active menu metadata:

| Legacy item | Seen in | Status | Recommended action |
| --- | --- | --- | --- |
| `gameguide` route family | redirect-only compatibility routes | retired | keep only for compatibility redirects to `server/connect` |

## Legacy Navigation Layers

These layers are not the routing source-of-truth and should be treated as secondary compatibility surfaces:

| Layer | File | Status | What changed in this pass |
| --- | --- | --- | --- |
| Legacy sitemap JS embedded in PHP | [core/common.php](/C:/Git/SPP-Armory-Website/core/common.php) | legacy-menu-only | stale dotted route links were remapped to current router-backed URLs |
| Legacy standalone nav tree | [js/navtree-main.js](/C:/Git/SPP-Armory-Website/js/navtree-main.js) | legacy-menu-only | top-level sections were updated to point at live current routes and current external support links |

## Conservative Cleanup Results

- The phantom public `armory` route family was removed from the main router allow-list.
- No live current main-nav item should point at a phantom route after this pass.
- Stale no-op toggle blocks were removed or remapped to current menu groups.
- Legacy nav layers were kept, but their obviously dead dotted-route links were replaced with live route targets.
- The `media` menu group was removed from active navigation.
- `media/wallp`, `media/screen`, `media/addgalscreen`, and `media/addgalwallp` now redirect to `media/index`.
- `community/teamspeak`, `community/donate`, and `community/chat` now redirect to `community/index`.
- `gameguide/index` and `gameguide/connect` now behave as retired compatibility redirects to `server/connect`.
- `statistic/index` now behaves as a retired compatibility redirect to `server/statistic`.
- `admin/news`, `admin/news_add`, and `admin/commands` now behave as retired admin compatibility redirects to forum destinations.
- Active component menu metadata now matches the current top-level groups instead of the old `4-menuInteractive` / `4-menuGameGuide` labels.

## Follow-Up Backlog

Low risk:

- Remove alias-only admin route entries that only forward users elsewhere.

Medium risk:

- Decide whether community pages deserve a real top-level menu group again.
- Collapse the duplicated legacy sitemap definitions into one maintained source.

Deferred:

- Larger route-family consolidation and layout restructuring once the route inventory is stable.
