# Account Module Inventory

This document describes the current account feature structure at the start of the Phase 2 pilot.

## Routes

The account routes are registered in [main.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/main.php).

Active routes:

- `index.php?n=account`
  - controller: [account.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.index.php)
  - template: [account.index.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.index.php)

- `sub=login`
  - controller: [account.login.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.login.php)
  - template: [account.login.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.login.php)

- `sub=register`
  - controller: [account.register.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.register.php)
  - template: [account.register.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.register.php)

- `sub=manage`
  - controller: [account.manage.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.manage.php)
  - template: [account.manage.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.manage.php)

- `sub=pms`
  - controller: [account.pms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.pms.php)
  - template: [account.pms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.pms.php)

- `sub=view`
  - controller: [account.view.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.view.php)
  - template: [account.view.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.view.php)

- `sub=activate`
  - controller: [account.activate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.activate.php)
  - template: [account.activate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.activate.php)

- `sub=restore`
  - controller: [account.restore.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.restore.php)
  - template: [account.restore.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.restore.php)

- `sub=userlist`
  - controller: [account.userlist.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.userlist.php)
  - template: [account.userlist.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.userlist.php)

- `sub=chartools`
  - controller: [account.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.chartools.php)
  - template: [account.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.chartools.php)

- `sub=charcreate`
  - controller: [account.charcreate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.charcreate.php)
  - template: [account.charcreate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/templates/offlike/account/account.charcreate.php)

## Current Heavy Controllers

Largest current files:

- [account.pms.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.pms.php)
- [account.manage.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.manage.php)
- [account.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.chartools.php)
- [account.charcreate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.charcreate.php)

These are the strongest candidates for future controller/helper extraction.

## Shared Helpers

Current shared account helpers:

- [account.helpers.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.helpers.php)
  - login redirect target helper
  - website account row ensure helper
  - manage-profile allowlist helper
  - avatar fallback helpers
  - account-view formatting and named PDO helper

## Account Splits

- [account.manage.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.manage.actions.php)
  - owns account settings mutations

- [account.pms.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.pms.actions.php)
  - owns PMS mutations

- [account.pms.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.pms.read.php)
  - owns PMS timeline/thread shaping

- [account.view.read.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.view.read.php)
  - owns profile assembly for `sub=view`

- [account.register.actions.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.register.actions.php)
  - owns modern registration form state, validation, IP-limit checks, and account creation submission handling

## Next Likely Splits

- Move chartools utility functions out of [account.chartools.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.chartools.php)
- Move remaining character-creation flow logic out of [account.charcreate.php](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/components/account/account.charcreate.php)
