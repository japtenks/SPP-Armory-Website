ALTER TABLE `website_accounts`
  ADD COLUMN `background_mode` VARCHAR(20) NOT NULL DEFAULT 'as_is' AFTER `theme`,
  ADD COLUMN `background_image` VARCHAR(60) DEFAULT NULL AFTER `background_mode`;
