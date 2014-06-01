RENAME TABLE `#language_words` TO `#phrases`;
RENAME TABLE `#mail_cc` TO `#mail_recipient`;

ALTER TABLE `#tasks`
  CHANGE `_description` `_description` TEXT DEFAULT NULL,
  ADD COLUMN `_protected` ENUM('y', 'n') NOT NULL DEFAULT 'n' AFTER `_enabled`;
ALTER TABLE `#permissions`
  ADD COLUMN `_name` VARCHAR(255) NOT NULL AFTER `_permission`,
  ADD COLUMN `_description` TEXT DEFAULT NULL AFTER `_name`;
ALTER TABLE `#phrases`
  CHANGE `_word` `_phrase` TEXT NOT NULL,
  DROP FOREIGN KEY `_#language_words__language_id_FK`,
  DROP COLUMN `_language_id`;
ALTER TABLE `#mail_queue`
  DROP COLUMN `_to_address`,
  DROP COLUMN `_to_name`;
ALTER TABLE `#mail_recipient`
  CHANGE `_bcc` `_type` ENUM('to', 'cc', 'bcc') NOT NULL DEFAULT 'bcc',
  DROP FOREIGN KEY `_#mail_cc__mail_id_FK`,
  ADD CONSTRAINT `_#mail_recipient__mail_id_FK` FOREIGN KEY ( _mail_id ) REFERENCES `#mail_queue` ( id )
    ON DELETE CASCADE
    ON UPDATE NO ACTION;

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

DROP TABLE `#language_locales`;
DROP TABLE `#languages`;
DROP TABLE `#email_translations`;
DROP TABLE `#emails`;
