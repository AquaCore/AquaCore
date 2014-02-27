CREATE TABLE IF NOT EXISTS ac_online_stats (
  players INT UNSIGNED NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY ( `date` )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED;

CREATE EVENT IF NOT EXISTS ac_online_stats_event
  ON SCHEDULE
  EVERY 30 MINUTE
  DISABLE
  DO INSERT INTO ac_online_stats
     SELECT COUNT(1), NOW() FROM `char` WHERE online = 1;
