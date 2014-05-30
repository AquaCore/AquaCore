CREATE TABLE IF NOT EXISTS `#mail_queue` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  _from_name VARCHAR(255) DEFAULT NULL,
  _from_address VARCHAR(255) DEFAULT NULL,
  _to_name VARCHAR(255) DEFAULT NOT NULL,
  _to_address VARCHAR(255) DEFAULT NOT NULL,
  _priority ENUM('high', 'normal', 'low') NOT NULL DEFAULT 'normal',
  _subject TEXT,
  _content TEXT,
  _date DATETIME NOT NULL,
  _status ENUM('pending', 'processing'),
  PRIMARY KEY ( id )
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `#mail_cc` (
  _mail_id INT UNSIGNED NOT NULL,
  _name VARCHAR(255),
  _address VARCHAR(255) NOT NULL,
  _bcc ENUM('y', 'n') NOT NULL DEFAULT 'y',
  PRIMARY KEY ( _mail_id, _address ),
  CONSTRAINT `_#mail_cc__mail_id_FK` FOREIGN KEY ( _mail_id ) REFERENCES `#mail_queue` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

INSERT IGNORE INTO `#tasks` VALUES
 (1, 'BulkMailTask', 'Bulk mail', 'Send queued bulk emails.', '*/3 * * * *', NULL, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 'n', 'y', '', NULL);
;
