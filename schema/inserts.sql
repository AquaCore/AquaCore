INSERT IGNORE INTO `#roles` VALUES
 (1, 'Guest', NULL, NULL, 'y', 'n', NULL)
,(2, 'User', NULL, NULL, 'y', 'y', NULL)
,(3, 'Administrator', '16723522', NULL, 'y', 'y', NULL)
;

REPLACE INTO `#permissions` VALUES
 (1, 'register-account', NULL)
,(2, 'comment', NULL)
,(3, 'rate', NULL)
,(4, 'view-admin-cp', NULL)
,(5, 'edit-cp-user', NULL)
,(6, 'edit-server-user', NULL)
,(7, 'view-user-items', NULL)
,(8, 'ban-cp-user', NULL)
,(9, 'ban-server-user', NULL)
,(10, 'view-cp-logs', NULL)
,(11, 'view-server-logs', NULL)
,(12, 'edit-cp-settings', NULL)
,(13, 'edit-server-settings', NULL)
,(14, 'create-pages', NULL)
,(15, 'publish-posts', NULL)
,(16, 'manage-roles', NULL)
,(17, 'manage-plugins', NULL)
;

REPLACE INTO `#role_permissions` VALUES
 (2, 1, 'n')
,(2, 2, 'n')
,(2, 3, 'n')
,(3, 1, 'y')
,(3, 2, 'y')
,(3, 3, 'y')
,(3, 4, 'y')
,(3, 5, 'y')
,(3, 6, 'y')
,(3, 7, 'y')
,(3, 8, 'y')
,(3, 9, 'y')
,(3, 10, 'y')
,(3, 11, 'y')
,(3, 12, 'y')
,(3, 13, 'y')
,(3, 14, 'y')
,(3, 15, 'y')
,(3, 16, 'y')
,(3, 17, 'y')
;

INSERT IGNORE INTO `#content_type` VALUES
 (1, 'news', NULL, 'Post', NULL)
,(2, 'page', NULL, 'Page', NULL)
;

TRUNCATE `#content_type_filters`;
INSERT INTO `#content_type_filters` VALUES
 (1, 'CategoryFilter', 'a:0:{}')
,(1, 'CommentFilter', 'a:0:{}')
,(1, 'FeaturedFilter', 'a:0:{}')
,(1, 'RatingFilter', 'a:0:{}')
,(1, 'ScheduleFilter', 'a:0:{}')
,(1, 'TagFilter', 'a:0:{}')
,(2, 'RatingFilter', 'a:0:{}')
,(2, 'RelationshipFilter', 'a:0:{}')
;

INSERT IGNORE INTO `#content` VALUES
 (1, 2, 1 , NULL, NOW(), NULL, 0, 'tos', 'Terms of Service', '', '', 'y', 0)
;
INSERT IGNORE INTO `#content_meta` VALUES
 (1, 'rating-disabled', 'b:1;')
;
