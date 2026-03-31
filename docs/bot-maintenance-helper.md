# Bot Maintenance Scripts

This repo now includes dedicated one-purpose maintenance scripts for the admin bots page:

- `tools/bot_maintenance_status.php`
- `tools/reset_forum_realm.php`
- `tools/fresh_bot_reset.php`
- `tools/rebuild_bot_site_layers.php`

They back:

- `index.php?n=admin&sub=bots`

## Current Contract

Supported scripts:

- `bot_maintenance_status.php`
- `reset_forum_realm.php --realm=<id> --execute [--dry-run]`
- `fresh_bot_reset.php --realm=<id> --execute [--dry-run]`
- `rebuild_bot_site_layers.php --realm=<id>`

## Execute Safety

Real execution uses:

- CLI `--execute`
- optional CLI `--dry-run` for preview-only mode

Without `--execute`, the script reports that execution was not requested.

## Realm Forum Mapping

Forum reset is realm-scoped by shared forum IDs in `classicrealmd`, not by separate forum DBs:

- realm `1` -> forum `2` (`Classic`)
- realm `2` -> forum `3` (`The Burning Crusade`)
- realm `3` -> forum `4` (`Wrath of the Lich King`)

This matches `DB Updates/seed_web_forums_default_state.sql`.

## Preserved Forum Seed Content

Forum reset preserves official seeded content where:

- topic author is `SPP Team` or `web Team`
- `topic_poster_id = 0`

and for posts:

- post author is `SPP Team` or `web Team`
- `poster_id = 0`
- `poster_character_id` is `0` or `NULL`

These rows are treated as official site/forum seed content, not disposable bot chatter.

## What Works Today

Implemented now:

- `bot_maintenance_status.php`
- `reset_forum_realm.php --execute --dry-run` dry-run preview
- `reset_forum_realm.php --execute` for shared realm forum cleanup with preserved official seed posts/topics
- `fresh_bot_reset.php --execute --dry-run` dry-run phase plan
- `rebuild_bot_site_layers.php` recommended command list

## What Still Needs Host Wiring

Still not executed by the scripts:

- world service restart
- bot repopulation trigger
- config edits in `aiplayerbot.conf`
- full realm-side bot account/character wipe and recreation
- cache filesystem purge outside simple website-visible paths

## Intended Fresh Reset Phases

`fresh_bot_reset.php` is scaffolded around these phases:

1. `reset_forum_realm`
2. clear bot website state
3. clear bot character/guild/db-store state
4. host repopulate / restart
5. rebuild site layers

The scripts currently report those phases and the relevant counts, but do not yet perform the full end-to-end reset automatically.
