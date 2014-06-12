ALTER TABLE `#users`
  ADD COLUMN `_profile_url` VARCHAR(255) DEFAULT NULL AFTER `_avatar`;
ALTER TABLE `#smileys`
  CHANGE COLUMN `_text` `_text` VARCHAR(32) NOT NULL;
ALTER TABLE `#categories`
  CHANGE COLUMN `_image` `_image` VARCHAR(32) DEFAULT NULL;
