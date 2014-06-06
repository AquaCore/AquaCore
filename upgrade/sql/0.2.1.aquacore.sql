CREATE TABLE IF NOT EXISTS `#content_type_subscriptions` (
  _type INT UNSIGNED NOT NULL,
  _user_id INT UNSIGNED NOT NULL,
  _date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
  PRIMARY KEY ( _type, _user_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_subscriptions` (
  _content_id INT UNSIGNED NOT NULL,
  _user_id INT UNSIGNED NOT NULL,
  _date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
  _type ENUM('comments', 'replies') NOT NULL,
  PRIMARY KEY ( _content_id, _user_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

INSERT IGNORE INTO `#content_type_filters` VALUES (1, 'SubscriptionFilter', 'a:0:{}');
INSERT IGNORE INTO `#permissions` (_permission, _name, _description, _plugin_id) VALUES
 ('manage-tasks', '', '', NULL)
,('view-guilds', '', '', NULL)
,('edit-comments', '', '', NULL)
;

INSERT IGNORE INTO `#role_permissions`
  SELECT 3, id, 'y'
  FROM `#permissions`
  WHERE `_permission` IN( 'manage-tasks', 'view-guilds',  'edit-comments' );

ALTER TABLE `#tasks`
  ADD COLUMN `_logging` ENUM('y', 'n') NOT NULL DEFAULT 'y' AFTER `_enabled`;

ALTER TABLE `#content_type`
  ADD COLUMN `_item_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `_name`,
  ADD COLUMN `_permission` VARCHAR(32) DEFAULT NULL AFTER `_adapter`;

UPDATE `#content_type` SET _permission = 'publish-posts' WHERE id = 1;
UPDATE `#content_type` SET _permission = 'create-pages' WHERE id = 2;

