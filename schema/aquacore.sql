SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `#tasks` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _name VARCHAR(255) NOT NULL,
  _title VARCHAR(255) NOT NULL,
  _description TEXT DEFAULT NULL,
  _expression MEDIUMTEXT NOT NULL,
  _last_run DATETIME DEFAULT NULL,
  _next_run DATETIME NOT NULL,
  _running ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _enabled ENUM('y','n') NOT NULL DEFAULT 'y',
  _protected ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _error_message VARCHAR(255) NOT NULL DEFAULT '',
  _plugin_id INT UNSIGNED NULL,
  PRIMARY KEY ( id ),
  UNIQUE INDEX `_#tasks__name_IN` ( _name ),
  INDEX `_#tasks__status_IN` ( _enabled, _running )
) ENGINE = InnoDB
  DEFAULT CHAR SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#roles` (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  _name VARCHAR(255) NOT NULL,
  _color MEDIUMINT UNSIGNED NULL,
  _background MEDIUMINT UNSIGNED NULL,
  _protected ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _editable ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _description TEXT DEFAULT NULL,
  PRIMARY KEY ( id )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#permissions` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _permission VARCHAR(32) NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _description TEXT DEFAULT NULL,
  _plugin_id INT UNSIGNED NULL,
  PRIMARY KEY ( id ),
  UNIQUE INDEX `_#permissions__permission_UN` ( _permission )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#role_permissions` (
  _role_id SMALLINT UNSIGNED NOT NULL,
  _permission INT UNSIGNED NOT NULL,
  _protected ENUM('y', 'n') NOT NULL DEFAULT 'n',
  PRIMARY KEY ( _role_id, _permission ),
  INDEX `_#role_permissions__permission_IN` ( _permission ),
  CONSTRAINT `_#role_permissions__role_id_FK` FOREIGN KEY ( _role_id ) REFERENCES `#roles` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `_#role_permissions__permission_FK` FOREIGN KEY ( _permission ) REFERENCES `#permissions` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#phrases` (
  _namespace VARCHAR(32) NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _phrase TEXT,
  _plugin_id INT UNSIGNED,
  PRIMARY KEY ( _namespace, _key )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#email_templates` (
  _key VARCHAR(32) NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _default_subject TEXT NOT NULL,
  _default_body TEXT NOT NULL,
  _subject TEXT DEFAULT NULL,
  _body TEXT DEFAULT NULL,
  _alt_body TEXT DEFAULT NULL,
  _plugin_id INT UNSIGNED NULL,
  PRIMARY KEY ( _key )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#email_placeholders` (
  _email VARCHAR(32) NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _description TEXT NOT NULL,
  PRIMARY KEY ( _email, _key ),
  CONSTRAINT `_#email_placeholders__email_FK` FOREIGN KEY ( _email ) REFERENCES `#email_templates` ( _key )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#users` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _username VARCHAR(50),
  _display_name VARCHAR(50) NOT NULL,
  _password VARCHAR(64) NOT NULL,
  _email VARCHAR(60) NOT NULL,
  _avatar VARCHAR(255) NOT NULL DEFAULT '',
  _role_id SMALLINT UNSIGNED NOT NULL DEFAULT '2',
  _birthday DATE NOT NULL DEFAULT '0000-00-00',
  _status TINYINT UNSIGNED NOT NULL DEFAULT '0',
  _credits BIGINT UNSIGNED NOT NULL DEFAULT '0',
  _registration_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  _unban_date DATE,
  _agreed_tos ENUM('y', 'n') NOT NULL DEFAULT 'n',
  PRIMARY KEY ( id ),
  INDEX `_#users__status_IN` ( _status ),
  INDEX `_#users__username_IN` ( _username ),
  INDEX `_#users__display_name_IN` ( _display_name ),
  INDEX `_#users__email_IN` ( _email )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#user_meta` (
  _id INT UNSIGNED NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _val TEXT,
  _type ENUM('S', 'I', 'F', 'B', 'X') NOT NULL,
  PRIMARY KEY ( _id, _key )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#captcha` (
  id CHAR(32) COLLATE utf8_bin NOT NULL,
  _ip_address VARBINARY(16) NOT NULL,
  _code VARCHAR(50) NOT NULL,
  _date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ( _ip_address, id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#session` (
  _key CHAR(128) COLLATE utf8_bin NOT NULL,
  _ip_address VARBINARY(16) NOT NULL,
  _user_agent VARCHAR(255) NOT NULL,
  _user_id INT UNSIGNED NULL,
  _session_start DATETIME NOT NULL,
  _last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  _data LONGTEXT,
  _tmp LONGTEXT,
  _flash LONGTEXT,
  PRIMARY KEY ( _key ),
  INDEX `_#session__user_id_IN` ( _user_id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#remember_me` (
  _user_id INT UNSIGNED NOT NULL,
  _key CHAR(128) NOT NULL,
  _date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY ( _user_id, _key )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

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

CREATE TABLE IF NOT EXISTS `#error_log` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _url VARCHAR(255) NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _user_id BIGINT UNSIGNED NULL,
  _date DATETIME NOT NULL,
  _type VARCHAR(60) NOT NULL,
  _code VARCHAR(20) NOT NULL,
  _file VARCHAR(255) NOT NULL,
  _line SMALLINT UNSIGNED NOT NULL,
  _message TEXT,
  _parent INT UNSIGNED NULL,
  PRIMARY KEY ( id ),
  UNIQUE `_#error_log__parent_UN` ( _parent ),
  CONSTRAINT `_#error_log_parent_FK` FOREIGN KEY ( _parent ) REFERENCES `#error_log` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#error_trace` (
  id INT UNSIGNED NOT NULL,
  _error_id INT UNSIGNED NOT NULL,
  _file VARCHAR(255) NULL,
  _line SMALLINT UNSIGNED NULL,
  _class VARCHAR(255) NULL,
  _method VARCHAR(50) NULL,
  _type CHAR(2) NULL,
  PRIMARY KEY ( _error_id, id ),
  CONSTRAINT `_#error_trace__error_id_FK` FOREIGN KEY ( _error_id ) REFERENCES `#error_log` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#login_log` (
  _ip_address VARCHAR(46) NOT NULL,
  _username TEXT NOT NULL,
  _user_id INT UNSIGNED,
  _type TINYINT UNSIGNED NOT NULL,
  _status TINYINT UNSIGNED NOT NULL,
  _date DATETIME NOT NULL,
  INDEX `_#login_log__date_IN` ( _date ),
  INDEX `#login_log__username_IN` ( _username(255) )
) ENGINE  = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#profile_update_log` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  _user_id INT UNSIGNED NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _field ENUM('display_name', 'email', 'password', 'birthday') NOT NULL,
  _old_value VARCHAR(255) NOT NULL,
  _new_value VARCHAR(255) NOT NULL,
  _date DATETIME NOT NULL,
  PRIMARY KEY ( id ),
  INDEX `_#profile_update_log__user_id_IN` ( _user_id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#ban_log` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _user_id INT UNSIGNED NOT NULL,
  _banned_id INT UNSIGNED NOT NULL,
  _type ENUM('temporary_ban', 'permanent_ban', 'unban') NOT NULL,
  _ban_date DATETIME NOT NULL,
  _unban_date DATE,
  _reason TEXT,
  PRIMARY KEY ( id ),
  INDEX `_#ban_log__banned_id_IN` ( _banned_id ),
  INDEX `_#ban_log__ban_date_IN` ( _ban_date )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#paypal_txn` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  _sandbox ENUM('y', 'n') NOT NULL,
  _process_date DATETIME NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _user_id BIGINT UNSIGNED DEFAULT NULL,
  _credits BIGINT UNSIGNED NOT NULL,
  _credit_rate DECIMAL(11, 5) NOT NULL,
  _exchanged ENUM('y', 'n') NOT NULL,
  _item_name VARCHAR(255) NOT NULL,
  _item_number VARCHAR(10) NOT NULL,
  _quantity INT UNSIGNED NOT NULL,
  _deposited DECIMAL(13, 2) UNSIGNED NOT NULL,
  _gross DECIMAL(13, 2) UNSIGNED NOT NULL,
  _fee DECIMAL(13, 2) UNSIGNED NOT NULL,
  _currency CHAR(3) NOT NULL,
  _payment_date DATETIME NOT NULL,
  _payment_type VARCHAR(10) NOT NULL,
  _txn_type VARCHAR(20) NOT NULL,
  _txn_id VARCHAR(20) NOT NULL,
  _parent_txn_id VARCHAR(20) NOT NULL,
  _payer_status VARCHAR(10) NOT NULL,
  _payer_id VARCHAR(14) NOT NULL,
  _payer_email VARCHAR(127) NOT NULL,
  _first_name VARCHAR(64) NOT NULL,
  _last_name VARCHAR(64) NOT NULL,
  _receiver_id VARCHAR(14) NOT NULL,
  _receiver_email VARCHAR(127) NOT NULL,
  _request TEXT,
  PRIMARY KEY ( id ),
  INDEX `_#paypal_txn__user_id_IN` ( _user_id ),
  INDEX `_#paypal_txn__txn_id_IN` ( _txn_id ),
  INDEX `_#paypal_txn__parent_txn_id_IN` ( _parent_txn_id ),
  INDEX `_#paypal_txn__process_date_IN` ( _process_date )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#transfer_log` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _sender_id BIGINT UNSIGNED NOT NULL,
  _receiver_id BIGINT UNSIGNED NOT NULL,
  _amount BIGINT UNSIGNED NOT NULL,
  _date DATETIME NOT NULL,
  PRIMARY KEY ( id ),
  INDEX `_#transfer_log__date_IN` ( _date ),
  INDEX `_#transfer_log__sender_id_IN` ( _sender_id ),
  INDEX `_#transfer_log__receiver_id_IN` ( _receiver_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#mail_queue` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _from_name VARCHAR(255) DEFAULT NULL,
  _from_address VARCHAR(255) DEFAULT NULL,
  _priority ENUM('high', 'normal', 'low') NOT NULL DEFAULT 'normal',
  _subject TEXT,
  _content TEXT,
  _date DATETIME NOT NULL,
  _status ENUM('pending', 'processing'),
  PRIMARY KEY ( id )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#mail_recipient` (
  _mail_id INT UNSIGNED NOT NULL,
  _name VARCHAR(255),
  _address VARCHAR(255) NOT NULL,
  _type ENUM('to', 'cc', 'bcc') NOT NULL DEFAULT 'bcc',
  PRIMARY KEY ( _mail_id, _address ),
  CONSTRAINT `_#mail_recipi ent__mail_id_FK` FOREIGN KEY ( _mail_id ) REFERENCES `#mail_queue` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#smileys` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _file VARCHAR(32) NOT NULL,
  _text VARCHAR(255) NOT NULL,
  _order INT UNSIGNED NOT NULL,
  PRIMARY KEY ( id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_type` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _key VARCHAR(32) COLLATE utf8_bin NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _table VARCHAR(32) COLLATE utf8_bin NOT NULL,
  _adapter VARCHAR(255),
  _feed ENUM('y', 'n') NOT NULL DEFAULT 'y',
  _listing ENUM('y', 'n') NOT NULL DEFAULT 'y',
  _plugin_id INT UNSIGNED,
  PRIMARY KEY ( id ),
  UNIQUE `_#content_type__key_UN` ( _key )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_type_filters` (
  _type INT UNSIGNED NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _options TEXT,
  PRIMARY KEY ( _type, _name )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_type_fields` (
  _type INT UNSIGNED NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _alias VARCHAR(255) NOT NULL,
  _field_type ENUM('number', 'float', 'string', 'binary', 'date', 'time', 'blob', 'text', 'enum', 'set') NOT NULL,
  PRIMARY KEY ( _type, _name )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content` (
  _uid INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _type INT UNSIGNED NOT NULL,
  _author_id INT UNSIGNED NOT NULL,
  _editor_id INT UNSIGNED,
  _publish_date DATETIME NOT NULL,
  _edit_date DATETIME,
  _status TINYINT UNSIGNED NOT NULL DEFAULT '0',
  _slug VARCHAR(255) NOT NULL,
  _title TEXT,
  _content MEDIUMTEXT,
  _plain_content MEDIUMTEXT,
  _protected ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _options INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY ( _uid ),
  INDEX `_#content__status_UN` ( _status ),
  INDEX `_#content__options_UN` ( _options ),
  UNIQUE `_#content__slug_UN` ( _type, _slug ),
  FULLTEXT `_#content__search_FT` ( _title, _plain_content )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_meta` (
  _id INT UNSIGNED NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _val TEXT,
  _type ENUM('S', 'I', 'F', 'B', 'X') NOT NULL,
  PRIMARY KEY ( _id, _key )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#content_relationship` (
  _content_id INT UNSIGNED NOT NULL,
  _parent_id INT UNSIGNED NOT NULL,
  PRIMARY KEY ( _content_id ),
  INDEX `_#content_relationship__parent_id_IN` ( _parent_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#content_ratings` (
  _content_id INT UNSIGNED NOT NULL,
  _user_id INT UNSIGNED NOT NULL,
  _weight TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY ( _user_id, _content_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#categories` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _type INT UNSIGNED NOT NULL,
  _name TEXT,
  _slug VARCHAR(255) NOT NULL,
  _image TEXT,
  _description TEXT DEFAULT NULL,
  _protected ENUM('y', 'n') NOT NULL,
  _options INT UNSIGNED NOT NULL,
  PRIMARY KEY ( _type, id ),
  UNIQUE `_#categories__slug_UN` ( _type, _slug )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#category_meta` (
  _id INT UNSIGNED NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _val TEXT,
  _type ENUM('S', 'I', 'F', 'B', 'X') NOT NULL,
  PRIMARY KEY ( _id, _key )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#content_categories` (
  _content_id INT UNSIGNED NOT NULL,
  _category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY ( _content_id, _category_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `#tags` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _name VARCHAR(255) NOT NULL,
  _content_id INT UNSIGNED NOT NULL,
  PRIMARY KEY ( _content_id, id ),
  INDEX `_#tags__name_IN` ( _name )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#comments` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _content_id INT UNSIGNED NOT NULL,
  _content_type INT UNSIGNED NOT NULL,
  _root_id INT UNSIGNED NOT NULL DEFAULT '0',
  _parent_id INT UNSIGNED NOT NULL DEFAULT '0',
  _nesting_level INT UNSIGNED NOT NULL DEFAULT '0',
  _children INT UNSIGNED NOT NULL DEFAULT '0',
  _author_id INT UNSIGNED NOT NULL,
  _editor_id INT UNSIGNED,
  _status TINYINT UNSIGNED NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _anonymous ENUM('y', 'n') NOT NULL DEFAULT 'n',
  _publish_date DATETIME NOT NULL,
  _edit_date TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  _html_content MEDIUMTEXT NOT NULL,
  _bbc_content MEDIUMTEXT NOT NULL,
  _rating INT NOT NULL DEFAULT '0',
  _reports INT NOT NULL DEFAULT '0',
  _options INT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY ( id ),
  INDEX `_#comments__content_id_IN` ( _content_type, _content_id ),
  INDEX `_#comments__nesting_IN` ( _root_id, _nesting_level ),
  INDEX `_#comments__status_IN` ( _status ),
  INDEX `_#comments__publish_date_IN` ( _publish_date )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#comment_ratings` (
  _content_id INT UNSIGNED NOT NULL,
  _comment_id INT UNSIGNED NOT NULL,
  _user_id INT UNSIGNED NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _weight TINYINT NOT NULL,
  PRIMARY KEY ( _user_id, _comment_id ),
  INDEX `_#comment_ratings__content_id_IN` ( _content_id )
) ENGINE = MyIsam
  ROW_FORMAT = FIXED
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#comment_reports` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _comment_id INT UNSIGNED NOT NULL,
  _user_id INT UNSIGNED NOT NULL,
  _ip_address VARCHAR(46) NOT NULL,
  _date DATETIME NOT NULL,
  _content TEXT,
  PRIMARY KEY ( id ),
  INDEX `_#comment_reports__comment_id_IN` ( _comment_id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#comment_meta` (
  _id INT UNSIGNED NOT NULL,
  _key VARCHAR(32) NOT NULL,
  _val TEXT,
  _type ENUM('S', 'I', 'F', 'B', 'X') NOT NULL,
  PRIMARY KEY ( _id, _key )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `#plugins` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _guid VARCHAR(255) COLLATE utf8_bin NOT NULL,
  _directory VARCHAR(255) NOT NULL,
  _name VARCHAR(255) NOT NULL,
  _description TEXT,
  _author VARCHAR(255),
  _author_url VARCHAR(255),
  _plugin_url VARCHAR(255),
  _license VARCHAR(255),
  _version VARCHAR(255),
  _enabled ENUM('y', 'n') NOT NULL DEFAULT 'n',
  PRIMARY KEY ( id ),
  UNIQUE `_#plugins__guid_UN` ( _guid )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#plugin_settings` (
  _plugin_id INT UNSIGNED NOT NULL,
  _key VARCHAR(255) NOT NULL,
  _default TEXT,
  _value TEXT,
  PRIMARY KEY ( _plugin_id, _key )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
