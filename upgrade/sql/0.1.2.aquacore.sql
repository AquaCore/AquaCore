CREATE TABLE IF NOT EXISTS `#tasks` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _name VARCHAR(255) NOT NULL,
  _title VARCHAR(255) NOT NULL,
  _description VARCHAR(255) NOT NULL,
  _expression MEDIUMTEXT NOT NULL,
  _last_run DATETIME DEFAULT NULL,
  _next_run DATETIME NOT NULL,
  _running ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _enabled ENUM('y','n') NOT NULL DEFAULT 'y',
  _error_message VARCHAR(255) NOT NULL DEFAULT '',
  _plugin_id INT UNSIGNED NULL,
  PRIMARY KEY ( id ),
  UNIQUE INDEX `_#tasks__name_IN` ( _name ),
  INDEX `_#tasks__status_IN` ( _enabled, _running )
) ENGINE = InnoDB
  DEFAULT CHAR SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#task_log` (
  id BIGINT UNSIGNED NOT NULL,
  _task_id INT UNSIGNED NOT NULL,
  _start DATETIME NOT NULL,
  _end DATETIME NOT NULL,
  _run_time TIME NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _output_short VARCHAR(255) NOT NULL,
  _output_full TEXT,
  INDEX `_#tasks__task_id_IN` ( _task_id ),
  INDEX `_#tasks__date_IN` ( _start, _end )
) ENGINE = MyIsam
  DEFAULT CHAR SET = utf8
  COLLATE = utf8_unicode_ci;
