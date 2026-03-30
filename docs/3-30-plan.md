# 3-30 Plan

## First Checks

- Browser-test [admin.members.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.members.php)
  - open a member detail page
  - try member search
  - try a harmless profile save
  - confirm ban/unban and password reset routes still render notices correctly

- Browser-test [admin.forum.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.forum.php)
  - open categories
  - open a forum
  - open a topic
  - rename one category/forum and confirm delete links still route correctly

- Browser-test [admin.realms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.realms.php)
  - load realm list
  - open edit form
  - confirm create/update/delete routes still work with CSRF

- Quick forum sanity pass
  - confirm real account posters still open profiles
  - confirm placeholder authors like `SPP Team` / `Web Team` are plain text and no longer route to blank pages

## Next Refactor Targets

1. [admin.cleanup.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.cleanup.php)
   Split into helper/action/read files.

2. [admin.botevents.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.botevents.php)
   Apply the same controller-helper pattern after cleanup.

3. [admin.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/admin/admin.chartools.php)
   Extract selection/setup helpers and thin the controller/template boundary.

## Notes

- `forum` is through the Phase 2 pattern.
- `account` is largely through it as well.
- `admin.members`, `admin.forum`, and `admin.realms` are already split into helper/action/read layers.
- If a new blank page appears, check for old template variable names left behind after refactors before assuming the route logic is broken.
