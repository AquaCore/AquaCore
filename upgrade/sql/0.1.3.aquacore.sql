ALTER TABLE `#content_type` ADD COLUMN `_name` VARCHAR (255) NOT NULL AFTER `_key`;
ALTER TABLE `#content_type` ADD COLUMN `_listing` ENUM ('y', 'n') NOT NULL DEFAULT 'y' AFTER `_adapter`;
ALTER TABLE `#content_type` ADD COLUMN `_feed` ENUM ('y', 'n') NOT NULL DEFAULT 'y' AFTER `_adapter`;

UPDATE `#content_type`
SET _name = 'News',
    _listing = 'y',
    _feed = 'y'
WHERE id = 1;

UPDATE `#content_type`
SET _name = 'Page',
    _listing = 'n',
    _feed = 'n'
WHERE id = 2;
