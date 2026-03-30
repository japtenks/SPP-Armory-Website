ALTER TABLE `website_accounts`
  ADD COLUMN `character_realm_id` INT(11) UNSIGNED DEFAULT NULL AFTER `character_name`;

ALTER TABLE `website_account_profiles`
  ADD COLUMN `character_realm_id` INT(11) UNSIGNED DEFAULT NULL AFTER `character_name`;
