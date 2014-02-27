CREATE TABLE IF NOT EXISTS `ac_char_map_settings` (
  `key` VARCHAR(255) NOT NULL,
  val MEDIUMTEXT,
  PRIMARY KEY ( `key` )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
