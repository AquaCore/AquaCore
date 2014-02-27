CREATE TABLE IF NOT EXISTS `ac_woe_schedule` (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  start_day ENUM('sun', 'mon', 'tue', 'wed', 'thur', 'fri', 'sat') NOT NULL,
  start_time TIME,
  end_day ENUM('sun', 'mon', 'tue', 'wed', 'thur', 'fri', 'sat') NOT NULL,
  end_time TIME,
  PRIMARY KEY ( id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `ac_woe_castles` (
  schedule_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  castle TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY ( schedule_id, castle )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

