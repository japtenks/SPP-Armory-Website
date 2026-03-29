CREATE TABLE IF NOT EXISTS `website_account_profiles` (
  `account_id` INT(11) UNSIGNED NOT NULL,
  `character_id` INT(11) UNSIGNED DEFAULT NULL,
  `character_name` VARCHAR(12) DEFAULT NULL,
  `display_name` VARCHAR(12) DEFAULT NULL,
  `avatar` VARCHAR(60) DEFAULT NULL,
  `signature` TEXT DEFAULT NULL,
  `hideemail` TINYINT(1) NOT NULL DEFAULT 1,
  `hideprofile` TINYINT(1) DEFAULT 0,
  `hidelocation` TINYINT(1) NOT NULL DEFAULT 1,
  `theme` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
  `background_mode` VARCHAR(20) NOT NULL DEFAULT 'as_is',
  `background_image` VARCHAR(60) DEFAULT NULL,
  `secretq1` VARCHAR(300) NOT NULL DEFAULT '0',
  `secretq2` VARCHAR(300) NOT NULL DEFAULT '0',
  `secreta1` VARCHAR(300) NOT NULL DEFAULT '0',
  `secreta2` VARCHAR(300) NOT NULL DEFAULT '0',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `website_account_profiles` (
  `account_id`,
  `character_id`,
  `character_name`,
  `display_name`,
  `avatar`,
  `signature`,
  `hideemail`,
  `hideprofile`,
  `hidelocation`,
  `theme`,
  `background_mode`,
  `background_image`,
  `secretq1`,
  `secreta1`
)
SELECT
  wa.`account_id`,
  wa.`character_id`,
  wa.`character_name`,
  wa.`display_name`,
  wa.`avatar`,
  wa.`signature`,
  wa.`hideemail`,
  wa.`hideprofile`,
  wa.`hidelocation`,
  wa.`theme`,
  COALESCE(wa.`background_mode`, 'as_is'),
  wa.`background_image`,
  wa.`secretq1`,
  wa.`secreta1`
FROM `website_accounts` wa
LEFT JOIN `website_account_profiles` wap ON wap.`account_id` = wa.`account_id`
WHERE wap.`account_id` IS NULL;

INSERT INTO `website_account_profiles` (`account_id`)
SELECT a.`id`
FROM `account` a
LEFT JOIN `website_account_profiles` wap ON wap.`account_id` = a.`id`
WHERE wap.`account_id` IS NULL;
