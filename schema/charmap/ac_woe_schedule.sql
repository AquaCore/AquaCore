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
  id SMALLINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY ( id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `ac_woe_schedule_castles` (
  schedule_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  castle TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY ( schedule_id, castle )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO `ac_woe_castles` VALUES
 (0, 'Neuschwanstein')
,(1, 'Hohenschwangau')
,(2, 'Nuenberg')
,(3, 'Wuerzburg')
,(4, 'Rothenburg')
,(5, 'Repherion')
,(6, 'Eeyolbriggar')
,(7, 'Yesnelph')
,(8, 'Bergel')
,(9, 'Mersetzdeitz')
,(10, 'Bright Arbor')
,(11, 'Scarlet Palace')
,(12, 'Holy Shadow')
,(13, 'Sacred Altar')
,(14, 'Bamboo Grove Hill')
,(15, 'Kriemhild')
,(16, 'Swanhild')
,(17, 'Fadhgridh')
,(18, 'Skoegul')
,(19, 'Gondul')
,(20, 'Novice Aldebaran')
,(21, 'Novice Geffen')
,(22, 'Novice Payon')
,(23, 'Novice Prontera')
,(24, 'Himinn')
,(25, 'Andlangr')
,(26, 'Viblainn')
,(27, 'Hljod')
,(28, 'Skidbladnir')
,(29, 'Mardol')
,(30, 'Cyr')
,(31, 'Horn')
,(32, 'Gefn')
,(33, 'Bandis')
;