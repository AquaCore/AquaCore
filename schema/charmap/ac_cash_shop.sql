CREATE TABLE IF NOT EXISTS ac_cash_shop_categories (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT,
  slug VARCHAR(255) NOT NULL,
  description TEXT,
  `order` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY ( id )
) ENGINE = MyIsam;

CREATE TABLE IF NOT EXISTS ac_cash_shop (
  item_id SMALLINT UNSIGNED NOT NULL,
  category_id SMALLINT UNSIGNED,
  `order` SMALLINT UNSIGNED NOT NULL,
  price SMALLINT UNSIGNED NOT NULL,
  sold BIGINT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY ( item_id ),
  INDEX _ac_cash_shop__category_IN ( category_id ),
  INDEX _ac_cash_shop__order_IN ( `order` )
) ENGINE = MyIsam;
