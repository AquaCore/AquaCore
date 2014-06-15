ALTER TABLE `#content`
  CHANGE COLUMN `_title` `_title` TEXT NOT NULL,
  CHANGE COLUMN `_content` `_content` MEDIUMTEXT NOT NULL,
  CHANGE COLUMN `_plain_content` `_plain_content` MEDIUMTEXT NOT NULL;

ALTER TABLE `#tasks`
  CHANGE COLUMN `_expression` `_expression` VARCHAR(255) NOT NULL,
  CHANGE COLUMN `_error_message` `_error_message` VARCHAR(255) DEFAULT NULL;
