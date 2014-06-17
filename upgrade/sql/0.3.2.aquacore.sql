ALTER TABLE `#comments`
  ADD COLUMN `_unique_reports` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `_reports`;