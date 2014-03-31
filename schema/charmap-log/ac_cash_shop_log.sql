CREATE TABLE IF NOT EXISTS `ac_cash_shop_log` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address VARCHAR(46) NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  username VARCHAR(23) NOT NULL,
  total INT UNSIGNED NOT NULL,
  items SMALLINT UNSIGNED NOT NULL,
  amount SMALLINT UNSIGNED NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY ( id ),
  INDEX `_ac_cash_shop_log__account_id_IN` ( account_id )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
