ALTER TABLE `login` ADD COLUMN ac_user_id BIGINT UNSIGNED NULL;
ALTER TABLE `login` ADD INDEX `_login__ac_user_id_IN` ( ac_user_id );
