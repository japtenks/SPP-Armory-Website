-- Run this only after the website_account_profiles migration has been verified.
-- It removes legacy profile/settings columns that are no longer used by the app.

ALTER TABLE `website_accounts`
  DROP COLUMN `character_id`,
  DROP COLUMN `character_name`,
  DROP COLUMN `fname`,
  DROP COLUMN `lname`,
  DROP COLUMN `city`,
  DROP COLUMN `display_name`,
  DROP COLUMN `avatar`,
  DROP COLUMN `gender`,
  DROP COLUMN `homepage`,
  DROP COLUMN `icq`,
  DROP COLUMN `aim`,
  DROP COLUMN `location`,
  DROP COLUMN `gmt`,
  DROP COLUMN `signature`,
  DROP COLUMN `hideemail`,
  DROP COLUMN `hideprofile`,
  DROP COLUMN `hidelocation`,
  DROP COLUMN `theme`,
  DROP COLUMN `background_mode`,
  DROP COLUMN `background_image`,
  DROP COLUMN `msn`,
  DROP COLUMN `yahoo`,
  DROP COLUMN `skype`,
  DROP COLUMN `secretq1`,
  DROP COLUMN `secreta1`;
