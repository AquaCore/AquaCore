CREATE TABLE IF NOT EXISTS `ac_ban_log` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  banned_account INT UNSIGNED NOT NULL,
  type ENUM('permanent', 'temporary', 'unban') NOT NULL,
  unban_date DATETIME DEFAULT NULL,
  `date` DATETIME NOT NULL,
  reason TEXT,
  PRIMARY KEY ( id ),
  INDEX `_ac_ban_log__banned_account_IN` ( banned_account )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
