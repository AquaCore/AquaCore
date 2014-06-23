ALTER TABLE `#content_type`
  DROP COLUMN `_feed`,
  ADD COLUMN `_search` ENUM('y', 'n') NOT NULL DEFAULT 'y' AFTER `_permission`;

INSERT IGNORE INTO `#content_type_filters` VALUES (1, 'FeedFilter', 'a:0:{}');
