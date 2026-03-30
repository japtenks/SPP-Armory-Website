# Admin Module Inventory

This document describes the current admin feature structure at the start of the Phase 2 admin pass.

## Routes

The admin routes are registered in [main.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/main.php).

Main active routes:

- `index.php?n=admin`
  - controller: [admin.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.index.php)
  - template: [admin.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.index.php)

- `sub=members`
  - controller: [admin.members.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.php)
  - template: [admin.members.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.members.php)

- `sub=forum`
  - controller: [admin.forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.php)
  - template: [admin.forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.forum.php)

- `sub=realms`
  - controller: [admin.realms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.php)
  - template: [admin.realms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.realms.php)

- `sub=chartools`
  - controller: [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.php)
  - template: [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.chartools.php)

- `sub=backup`
  - controller: [admin.backup.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.backup.php)
  - template: [admin.backup.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.backup.php)

- `sub=identities`
  - controller: [admin.identities.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.identities.php)
  - template: [admin.identities.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.identities.php)

## Current Heavy Controllers

Largest current admin controllers:

- [admin.members.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.php)
- [admin.forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.php)
- [admin.botevents.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.botevents.php)
- [admin.cleanup.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.cleanup.php)

## Shared Helpers

Current shared admin helpers:

- [admin.members.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.helpers.php)
  - member-account website row ensure helper
  - online/offline checks
  - character-delete table map
  - allowlists for editable admin member fields

- [admin.members.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.actions.php)
  - member search
  - password resets, ban/unban, account/profile updates
  - bot signature updates
  - character transfer/delete flows
  - inactive cleanup actions

- [admin.members.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.read.php)
  - member detail view assembly
  - member list/filter/pagination assembly

- [admin.forum.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.helpers.php)
  - forum admin action URL helper
  - category/forum field allowlists
  - forum reorder, recount, and delete helpers

- [admin.forum.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.actions.php)
  - forum/category mutation handling
  - visibility and ordering actions
  - topic/post deletion actions

- [admin.forum.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.read.php)
  - category/forum/topic admin view assembly

- [admin.realms.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.helpers.php)
  - realm type/timezone definitions
  - realm field filtering and normalization

- [admin.realms.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.actions.php)
  - create, update, and delete actions with CSRF checks

- [admin.realms.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.read.php)
  - list and edit view assembly

- [admin.cleanup.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.cleanup.helpers.php)
  - cleanup preview table helpers
  - realm-name and empty-preview helpers

- [admin.cleanup.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.cleanup.actions.php)
  - reserved action entry point for future destructive maintenance flows

- [admin.cleanup.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.cleanup.read.php)
  - preview metric assembly for orphaned rows, forum reset scope, bots, and realm reset size

- [admin.botevents.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.botevents.helpers.php)
  - CLI/bot-event command helpers
  - action URL helper for protected admin bot-event routes

- [admin.botevents.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.botevents.actions.php)
  - scan/process/skip action handling with CSRF checks

- [admin.botevents.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.botevents.read.php)
  - bot-event stats, filters, and recent-event list assembly

- [admin.chartools.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.actions.php)
  - rename and race/faction action handling

- [admin.chartools.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.read.php)
  - realm/account/character selection state assembly

- [admin.backup.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.backup.helpers.php)
  - backup output helpers and SQL export helpers

- [admin.backup.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.backup.actions.php)
  - character copy backup export handling

- [admin.backup.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.backup.read.php)
  - backup scope preview for configured copy accounts

- [admin.identities.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.identities.read.php)
  - per-realm identity coverage counts for accounts, characters, forum posts, topics, and PMs

- [functionsrace.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/chartools/functionsrace.php)
  - admin-only race/faction change helpers for chartools

## Next Likely Splits

- Continue modernizing `admin.chartools` and any remaining admin utility screens to the same controller-helper pattern

## Removed Utilities

- `admin.keys`
  - removed for the LAN-only setup

- `admin.langs`
  - removed in favor of a mostly-English site and browser translation

- `admin.donate`
  - removed as a standalone admin page
  - the useful in-game item-pack delivery flow now lives under [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.php)
