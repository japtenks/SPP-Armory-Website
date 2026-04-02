# Route Inventory

This document is the current route source-of-truth snapshot for the site after the conservative navigation cleanup. The public router lives in [index.php](/C:/Git/SPP-Armory-Website/index.php) and is fed by [core/default_components.php](/C:/Git/SPP-Armory-Website/core/default_components.php) plus `components/*/main.php`.

Status meanings:

- `active`: component and end page exist, and the route is intended.
- `hidden`: route is real, but not exposed as a normal main-nav entry.
- `weak-landing`: route exists, but the landing page is minimal or effectively empty.
- `retired-redirect`: old route is still accepted, but immediately redirects to a supported destination.
- `phantom`: route family or endpoint is declared but has no real endpoint implementation.
- `internal`: not part of the normal public router surface.
- `module`: dynamically registered by module bootstrapping.

## Public Router

| Route key | Access | Component file | Template file | Menu exposure | Status | Recommended action |
| --- | --- | --- | --- | --- | --- | --- |
| `frontpage/index` | public | `components/frontpage/frontpage.index.php` | `templates/offlike/frontpage/frontpage.index.php` | `1-menuNews` | active | keep |
| `account/index` | public | `components/account/account.index.php` | `templates/offlike/account/account.index.php` | none | active | keep |
| `account/login` | public | `components/account/account.login.php` | `templates/offlike/account/account.login.php` | none | active | keep |
| `account/register` | public | `components/account/account.register.php` | `templates/offlike/account/account.register.php` | `2-menuAccount` | active | keep |
| `account/manage` | public | `components/account/account.manage.php` | `templates/offlike/account/account.manage.php` | `2-menuAccount` | active | keep |
| `account/view` | protected | `components/account/account.view.php` | `templates/offlike/account/account.view.php` | none | hidden | keep |
| `account/activate` | public | `components/account/account.activate.php` | `templates/offlike/account/account.activate.php` | `2-menuAccount` | active | keep |
| `account/restore` | public | `components/account/account.restore.php` | `templates/offlike/account/account.restore.php` | `2-menuAccount` | active | keep |
| `account/pms` | protected | `components/account/account.pms.php` | `templates/offlike/account/account.pms.php` | `2-menuAccount` | active | keep |
| `account/userlist` | public route, admin-linked menu | `components/account/account.userlist.php` | `templates/offlike/account/account.userlist.php` | `2-menuAccount` | hidden | keep, review privilege mismatch later |
| `account/chartools` | public | `components/account/account.chartools.php` | `templates/offlike/account/account.chartools.php` | none | hidden | keep |
| `account/charcreate` | public | `components/account/account.charcreate.php` | `templates/offlike/account/account.charcreate.php` | `2-menuAccount` | active | keep |
| `forum/index` | public | `components/forum/forum.index.php` | `templates/offlike/forum/forum.index.php` | `6-menuForums` | active | keep |
| `forum/post` | public | `components/forum/forum.post.php` | `templates/offlike/forum/forum.post.php` | none | hidden | keep |
| `forum/viewforum` | public | `components/forum/forum.viewforum.php` | `templates/offlike/forum/forum.viewforum.php` | none | active | keep |
| `forum/viewtopic` | public | `components/forum/forum.viewtopic.php` | `templates/offlike/forum/forum.viewtopic.php` | none | active | keep |
| `server/index` | public | `components/server/server.index.php` | `templates/offlike/server/server.index.php` | none | active | keep |
| `server/connect` | public | `components/server/server.connect.php` | `templates/offlike/server/server.connect.php` | `3-menuGameGuide` | active | keep as the primary start page |
| `server/botcommands` | public | `components/server/server.botcommands.php` | `templates/offlike/server/server.botcommands.php` | `3-menuGameGuide` | active | keep server-owned, guide-grouped in navigation |
| `server/wbuffbuilder` | public | `components/server/server.wbuffbuilder.php` | `templates/offlike/server/server.wbuffbuilder.php` | none | active | keep |
| `server/chars` | public | `components/server/server.chars.php` | `templates/offlike/server/server.chars.php` | `7-menuArmory` | active | keep |
| `server/character` | public | `components/server/server.character.php` | `templates/offlike/server/server.character.php` | none | active | keep |
| `server/guilds` | public | `components/server/server.guilds.php` | `templates/offlike/server/server.guilds.php` | `7-menuArmory` | active | keep |
| `server/guild` | public | `components/server/server.guild.php` | `templates/offlike/server/server.guild.php` | none | active | keep |
| `server/realmstatus` | public | `components/server/server.realmstatus.php` | `templates/offlike/server/server.realmstatus.php` | `4-menuWorkshop` | active | keep |
| `server/honor` | public | `components/server/server.honor.php` | `templates/offlike/server/server.honor.php` | `7-menuArmory` | active | keep |
| `server/playersonline` | public | `components/server/server.playersonline.php` | no dedicated template, shared render path | none | hidden | keep |
| `server/playermap` | public | `components/server/server.playermap.php` | `templates/offlike/server/server.playermap.php` | `4-menuWorkshop` | active | keep |
| `server/talents` | public | `components/server/server.talents.php` | `templates/offlike/server/server.talents.php` | `7-menuArmory` | active | keep |
| `server/items` | public | `components/server/server.items.php` | `templates/offlike/server/server.items.php` | `7-menuArmory` | active | keep |
| `server/item` | public | `components/server/server.item.php` | `templates/offlike/server/server.item.php` | none | active | keep |
| `server/marketplace` | public | `components/server/server.marketplace.php` | `templates/offlike/server/server.marketplace.php` | `7-menuArmory` | active | keep |
| `server/statistic` | public | `components/server/server.statistic.php` | `templates/offlike/server/server.statistic.php` | `4-menuWorkshop` | active | keep |
| `server/ah` | public | `components/server/server.ah.php` | `templates/offlike/server/server.ah.php` | `4-menuWorkshop` | active | keep |
| `server/sets` | public | `components/server/server.sets.php` | `templates/offlike/server/server.sets.php` | `4-menuWorkshop` | active | keep |
| `server/downloads` | public | `components/server/server.downloads.php` | `templates/offlike/server/server.downloads.php` | `4-menuWorkshop` | active | keep |
| `server/realmlist` | public | `components/server/server.realmlist.php` | no dedicated template | none | hidden | keep |
| `server/itemtooltip` | public | `components/server/server.itemtooltip.php` | no dedicated template | none | hidden | keep |
| `server/rules` | public | `components/server/server.rules.php` | no dedicated template | `2-menuAccount` | hidden | keep |
| `community/index` | public legacy landing | `components/community/community.index.php` | `templates/offlike/community/community.index.php` | none | active | keep as retirement landing page |
| `community/teamspeak` | public legacy URL | `components/community/community.teamspeak.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `community/index` |
| `community/donate` | public legacy URL | `components/community/community.donate.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `community/index` |
| `community/chat` | public legacy URL | `components/community/community.chat.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `community/index` |
| `whoisonline/index` | public retired landing | `components/whoisonline/whoisonline.index.php` | `templates/offlike/whoisonline/whoisonline.index.php` | none | active | keep as retirement landing page |
| `gameguide/index` | public retired family URL | `components/gameguide/gameguide.index.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `server/connect` |
| `gameguide/connect` | public retired family URL | `components/gameguide/gameguide.connect.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `server/connect` |
| `media/index` | public legacy landing | `components/media/media.index.php` | `templates/offlike/media/media.index.php` | none | active | keep as retirement landing page |
| `media/wallp` | public legacy URL | `components/media/media.wallp.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `media/index` |
| `media/screen` | public legacy URL | `components/media/media.screen.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `media/index` |
| `media/addgalscreen` | public legacy URL | `components/media/media.addgalscreen.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `media/index` |
| `media/addgalwallp` | public legacy URL | `components/media/media.addgalwallp.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `media/index` |
| `statistic/index` | public legacy URL | `components/statistic/main.php` | no end page, immediate redirect | none | retired-redirect | keep redirect to `server/statistic` |
| `admin/index` | admin-only | `components/admin/admin.index.php` | `templates/offlike/admin/admin.index.php` | `2-menuAccount` | active | keep |
| `admin/members` | admin-only | `components/admin/admin.members.php` | `templates/offlike/admin/admin.members.php` | context only | active | keep |
| `admin/config` | admin-only | `components/admin/admin.config.php` | `templates/offlike/admin/admin.config.php` | context only | active | keep |
| `admin/realms` | admin-only | `components/admin/admin.realms.php` | `templates/offlike/admin/admin.realms.php` | context only | active | keep |
| `admin/forum` | admin-only | `components/admin/admin.forum.php` | `templates/offlike/admin/admin.forum.php` | context only | active | keep |
| `admin/backup` | admin-only | `components/admin/admin.backup.php` | `templates/offlike/admin/admin.backup.php` | context only | active | keep |
| `admin/identities` | admin-only | `components/admin/admin.identities.php` | `templates/offlike/admin/admin.identities.php` | context only | active | keep |
| `admin/botevents` | admin-only | `components/admin/admin.botevents.php` | `templates/offlike/admin/admin.botevents.php` | context only | active | keep |
| `admin/botrotation` | admin-only | `components/admin/admin.botrotation.php` | `templates/offlike/admin/admin.botrotation.php` | context only | active | keep |
| `admin/playerbots` | admin-only | `components/admin/admin.playerbots.php` | `templates/offlike/admin/admin.playerbots.php` | context only | active | keep |
| `admin/bots` | admin-only | `components/admin/admin.bots.php` | `templates/offlike/admin/admin.bots.php` | context only | active | keep |
| `admin/viewlogs` | admin-only | `components/admin/admin.viewlogs.php` | `templates/offlike/admin/admin.viewlogs.php` | none | active | keep |
| `admin/chartools` | admin-only | `components/admin/admin.chartools.php` | `templates/offlike/admin/admin.chartools.php` | none | active | keep |
| `admin/chartransfer` | admin-only | `components/admin/admin.chartransfer.php` | `templates/offlike/admin/admin.chartransfer.php` | none | active | keep |
| `admin/updatefields` | admin-only | `components/admin/admin.updatefields.php` | `templates/offlike/admin/admin.updatefields.php` | none | active | keep |
| `admin/news_add` | admin-only legacy URL | `components/admin/admin.news_add.php` | no template, immediate redirect | none | retired-redirect | keep redirect to forum new-topic flow |
| `admin/news` | admin-only legacy URL | `components/admin/admin.news.php` | no template, immediate redirect | none | retired-redirect | keep redirect to forum news board |
| `admin/commands` | admin-only legacy URL | `components/admin/admin.commands.php` | no template, immediate redirect | none | retired-redirect | keep redirect to forum commands board |

## Internal And Non-Public Families

| Route key | Access | Component file | Template file | Menu exposure | Status | Recommended action |
| --- | --- | --- | --- | --- | --- | --- |
| `html/index` | internal | `components/html/html.index.php` | `templates/offlike/html/html.index.php` | none | internal | keep internal |
| `html/notice` | internal | `components/html/html.notice.php` | no public template | none | internal | keep internal |
| `modules/*` | module | `components/modules/*` | module-owned | module-defined | module | inventory per module if needed |
| `armory/index` | removed phantom route | no router-backed component main | `templates/offlike/armory/armory.talents.php` is standalone debt | none | phantom | keep out of public router |

## Backlog

Low risk:

- Keep `html` internal and avoid advertising it as a public route family.
- Continue pruning stale dotted-route references from legacy navigation layers.
- Decide when the retired `media` landing page can be removed entirely.
- Decide when the retired `gameguide` family can be removed entirely after redirects are no longer needed.
- Decide when the retired admin alias redirects can be removed entirely.
- Decide when the retired `whoisonline` landing page can be removed entirely.
- Decide when the retired `statistic` wrapper route can be removed entirely.

Medium risk:

- Tighten route privilege mismatches such as `account/userlist`.
- Decide when the retired `community` landing page can be removed entirely.

Deferred:

- Consolidate duplicate nav systems so `core/default_components.php` is the only user-facing menu source.
- Decide whether any standalone armory functionality should return as a real routed family.
