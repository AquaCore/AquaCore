CREATE TABLE IF NOT EXISTS `ac_login_settings` (
  `key` VARCHAR(255) NOT NULL,
  val MEDIUMTEXT,
  PRIMARY KEY ( `key` )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

TRUNCATE TABLE `ac_login_settings`;
