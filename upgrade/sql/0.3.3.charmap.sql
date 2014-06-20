ALTER TABLE #db#`ac_cash_shop`
  ADD COLUMN `sold_unique` BIGINT UNSIGNED NOT NULL DEFAULT '0' AFTER `sold`;