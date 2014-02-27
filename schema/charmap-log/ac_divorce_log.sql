CREATE TABLE IF NOT EXISTS `ac_divorce_log` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  char_id INT UNSIGNED NOT NULL,
  partner_id INT UNSIGNED NOT NULL,
  child_id INT UNSIGNED NOT NULL,
  keep_child ENUM('y', 'n') NOT NULL,
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ( id ),
  INDEX `_ac_divorde_log__char_id_IN` ( char_id )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
