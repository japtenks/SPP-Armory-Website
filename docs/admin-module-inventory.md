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

- `sub=keys`
  - controller: [admin.keys.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.keys.php)
  - template: [admin.keys.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.keys.php)

- `sub=langs`
  - controller: [admin.langs.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.langs.php)
  - template: [admin.langs.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.langs.php)

- `sub=realms`
  - controller: [admin.realms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.php)
  - template: [admin.realms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.realms.php)

- `sub=chartools`
  - controller: [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.php)
  - template: [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/admin/admin.chartools.php)

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

- [functionsrace.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/chartools/functionsrace.php)
  - admin-only race/faction change helpers for chartools

## Next Likely Splits

- Extract shared admin chartools selection helpers from the template/controller pair
- Continue modernizing `admin.cleanup` and `admin.botevents` to the same controller-helper pattern
