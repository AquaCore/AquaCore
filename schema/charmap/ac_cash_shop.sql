CREATE TABLE IF NOT EXISTS ac_cash_shop (
  item_id SMALLINT UNSIGNED NOT NULL,
  category VARCHAR(255),
  `order` SMALLINT UNSIGNED NOT NULL,
  price SMALLINT UNSIGNED NOT NULL,
  sold BIGINT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY ( item_id ),
  INDEX _ac_cash_shop__category_IN ( category ),
  INDEX _ac_cash_shop__order_IN ( order )
) ENGINE = MyIsam;
