ALTER TABLE `#user_meta` ADD COLUMN `_type` ENUM ('S', 'I', 'F', 'B', 'X') NOT NULL AFTER `_val`;
ALTER TABLE `#user_meta` CHANGE `_user_id` `_id` INT UNSIGNED NOT NULL;
ALTER TABLE `#user_meta` CHANGE `_val` `_val` TEXT;
ALTER TABLE `#content_meta` ADD COLUMN `_type` ENUM ('S', 'I', 'F', 'B', 'X') NOT NULL AFTER `_val`;
ALTER TABLE `#content_meta` CHANGE `_content_id` `_id` INT UNSIGNED NOT NULL;
ALTER TABLE `#category_meta` ADD COLUMN `_type` ENUM ('S', 'I', 'F', 'B', 'X') NOT NULL AFTER `_val`;
ALTER TABLE `#category_meta` CHANGE `_category_id` `_id` INT UNSIGNED NOT NULL;
ALTER TABLE `#comment_meta` ADD COLUMN `_type` ENUM ('S', 'I', 'F', 'B', 'X') NOT NULL AFTER `_val`;
ALTER TABLE `#comment_meta` CHANGE `_comment_id` `_id` INT UNSIGNED NOT NULL;