CREATE TABLE IF NOT EXISTS `ac_cash_shop_items` (
  id BIGINT UNSIGNED NOT NULL,
  item_id SMALLINT UNSIGNED NOT NULL,
  amount SMALLINT UNSIGNED NOT NULL,
  item_price INT UNSIGNED NOT NULL,
  PRIMARY KEY ( id, item_id ),
  CONSTRAINT `_ac_cash_shop_items__id_FK` FOREIGN KEY ( id ) REFERENCES `ac_cash_shop_log` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
