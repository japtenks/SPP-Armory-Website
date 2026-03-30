# Forum Module Inventory

This document describes the current forum feature structure after the Phase 2 pilot refactor.

## Routes

The forum routes are registered in [main.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/main.php).

Active routes:

- `index.php?n=forum`
  - sub-route key: `index`
  - controller: [forum.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.index.php)
  - template: [forum.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.index.php)
  - purpose: forum category and forum listing

- `index.php?n=forum&sub=viewforum`
  - sub-route key: `viewforum`
  - controller: [forum.viewforum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.viewforum.php)
  - template: [forum.viewforum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.viewforum.php)
  - purpose: topic listing for a single forum

- `index.php?n=forum&sub=viewtopic`
  - sub-route key: `viewtopic`
  - controller: [forum.viewtopic.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.viewtopic.php)
  - template: [forum.viewtopic.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.viewtopic.php)
  - purpose: topic thread view

- `index.php?n=forum&sub=post`
  - sub-route key: `post`
  - controller: [forum.post.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.post.php)
  - template: [forum.post.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.post.php)
  - purpose: composer UI and forum write actions

Removed route:

- `index.php?n=forum&sub=attach`
  - removed as orphaned/dead functionality during cleanup

## Controller Layout

Page controllers:

- [forum.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.index.php)
  - orchestrates forum index loading
  - delegates forum/category list shaping to read helpers

- [forum.viewforum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.viewforum.php)
  - orchestrates topic list loading for one forum
  - delegates mark-read preparation and topic row shaping to read helpers

- [forum.viewtopic.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.viewtopic.php)
  - orchestrates topic page state, pagination, mark-read behavior, and topic metadata
  - delegates post hydration/render prep to read helpers

- [forum.post.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.post.php)
  - orchestrates posting context and eligibility checks
  - delegates mutations to post action helpers
  - reuses shared read helpers for reply-context post rendering

Compatibility shim:

- [forum.func.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.func.php)
  - no longer acts as the real implementation bucket
  - now loads the split helper modules below

Legacy placeholder:

- [forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.php)
  - currently empty
  - candidate for future forum module bootstrap or can be removed if proven unused

## Helper Modules

Data access:

- [forum.data.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.data.php)
  - forum/topic/post lookup helpers
  - last-topic/last-post lookups
  - post position lookup

Posting and write guards:

- [forum.guard.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.guard.php)
  - action URL signing helpers
  - post validation rules
  - duplicate/cooldown protections
  - unread/view counter helpers

Forum scope and character rules:

- [forum.scope.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.scope.php)
  - realm/expansion scope checks
  - guild recruitment eligibility
  - active recruitment thread lookup
  - character validity and selected-character resolution

Avatar and presentation support:

- [forum.avatar.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.avatar.php)
  - portrait lookup/cache helpers
  - fallback avatar helpers

Read-side assembly:

- [forum.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.read.php)
  - topic post hydration for thread/reply context
  - forum index assembly
  - `viewforum` mark-read setup
  - `viewforum` topic row assembly

Write-side action handlers:

- [forum.post.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.post.actions.php)
  - moderation actions
  - new topic submission
  - reply submission

## Templates

Live templates under `templates/offlike/forum/`:

- [forum.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.index.php)
- [forum.viewforum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.viewforum.php)
- [forum.viewtopic.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.viewtopic.php)
- [forum.post.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/forum/forum.post.php)

Removed template:

- `forum.attach.php`
  - deleted during orphan cleanup

## Current Boundaries

Read-heavy flow:

- `forum.index.php`
- `forum.viewforum.php`
- `forum.viewtopic.php`
- `forum.read.php`

Write-heavy flow:

- `forum.post.php`
- `forum.post.actions.php`
- `forum.guard.php`

Shared policy and identity rules:

- `forum.scope.php`
- `forum.data.php`
- `forum.avatar.php`

## Known Remaining Cleanup Targets

- Move topic-page mark-read/update logic from [forum.viewtopic.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.viewtopic.php) into a dedicated read helper.
- Decide whether [forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/forum/forum.php) should become a real forum bootstrap or be removed.
- Consider adding a similar inventory document for `account` once that module becomes the next pilot.
