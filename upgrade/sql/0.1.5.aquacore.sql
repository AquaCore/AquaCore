ALTER TABLE `#comments` ADD COLUMN `_content_type` INT UNSIGNED NOT NULL AFTER `_content_id`;
ALTER TABLE `#comments` DROP INDEX `_#comments__content_id_IN`;
ALTER TABLE `#comments` ADD INDEX `_#comments__content_id_IN` ( _content_type, _content_id );

UPDATE `#comments` comments
INNER JOIN `#content` content
ON content._uid = comments._content_id
SET _content_type = content._type
;

UPDATE `#content_type`
SET `_adapter` = NULL
WHERE id IN( 1, 2 )
;