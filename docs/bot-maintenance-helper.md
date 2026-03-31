# Bot Maintenance Helper

This repo now includes a local helper scaffold at:

- `tools/bot_maintenance_helper.php`

It is designed to be the execution bridge for the admin bots page:

- `index.php?n=admin&sub=bots`

## Current Contract

The helper accepts JSON shaped like:

```json
{
  "action": "fresh_reset",
  "site": "SPP-Armory-Website",
  "requested_at": "2026-03-31T12:00:00-05:00",
  "payload": {
    "realm_id": 1,
    "realm_name": "SPP-Classic",
    "execute": false
  }
}
```

Supported actions:

- `status`
- `reset_forum_realm`
- `fresh_reset`
- `rebuild_site_layers`

## Auth

If the `SPP_BOT_HELPER_TOKEN` environment variable is set, the helper expects:

- `Authorization: Bearer <token>`

If the token is blank, auth is effectively disabled for local scaffolding and manual CLI use.

## Execute Safety

Real execution is guarded by:

- local flag file `core/cache/bot_maintenance_execute_enabled.flag`
- payload or CLI `execute = true`

Without both, the helper returns dry-run plans only.

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

- `status`
- `reset_forum_realm` dry-run preview
- `reset_forum_realm` execute path for shared realm forum cleanup with preserved official seed posts/topics
- `fresh_reset` dry-run phase plan
- `rebuild_site_layers` dry-run recommended command list

## What Still Needs Host Wiring

Still not executed by the helper:

- world service restart
- bot repopulation trigger
- config edits in `aiplayerbot.conf`
- full realm-side bot account/character wipe and recreation
- cache filesystem purge outside simple website-visible paths

## Intended Fresh Reset Phases

`fresh_reset` is scaffolded around these phases:

1. `reset_forum_realm`
2. clear bot website state
3. clear bot character/guild/db-store state
4. host repopulate / restart
5. rebuild site layers

The helper currently reports those phases and the relevant counts, but does not yet perform the full end-to-end reset automatically.
