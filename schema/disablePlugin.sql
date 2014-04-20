DROP PROCEDURE IF EXISTS disablePlugin;
CREATE PROCEDURE disablePlugin (IN pluginId INT)
  BEGIN

    DECLARE tbl VARCHAR(32);
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR SELECT _table FROM `#content_type` WHERE _plugin_id = pluginId AND _table != NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SET FOREIGN_KEY_CHECKS = 0;
    OPEN cur;

    drop_tables: LOOP
      FETCH cur INTO tbl;
      IF done THEN
        LEAVE drop_tables;
      END IF;
      SET @qry = CONCAT('DROP TABLE IF EXISTS `', tbl ,'`');
      PREPARE stmt FROM @qry;
      EXECUTE stmt;
    END LOOP;

    CLOSE cur;
    SET FOREIGN_KEY_CHECKS = 1;

    CREATE TEMPORARY TABLE __contentTypes ENGINE = Memory
        SELECT id
        FROM `#content_type`
        WHERE _plugin_id = pluginId;
    CREATE TEMPORARY TABLE __categories ENGINE = Memory
        SELECT id
        FROM `#categories`
        WHERE _type IN ( SELECT * FROM __contentTypes );
    CREATE TEMPORARY TABLE __content ENGINE = Memory
        SELECT _uid
        FROM `#content`
        WHERE _type IN ( SELECT * FROM __contentTypes );
    CREATE TEMPORARY TABLE __comments ENGINE = Memory
        SELECT id
        FROM `#comments`
        WHERE _content_id IN ( SELECT * FROM __content );
    DELETE FROM `#comment_meta` WHERE _comment_id IN ( SELECT * FROM __comments );
    DELETE FROM `#comments` WHERE id IN ( SELECT * FROM __comments );
    DELETE FROM `#content_meta` WHERE _content_id IN ( SELECT * FROM __content );
    DELETE FROM `#tags` WHERE _content_id IN ( SELECT * FROM __content );
    DELETE FROM `#content_relationship` WHERE _content_id IN ( SELECT * FROM __content );
    DELETE FROM `#content_categories` WHERE _content_id IN ( SELECT * FROM __content );
    DELETE FROM `#content` WHERE _uid IN ( SELECT * FROM __content );
    DELETE FROM `#category_meta` WHERE _category_id IN ( SELECT * FROM __categories );
    DELETE FROM `#categories` WHERE _type IN ( SELECT * FROM __contentTypes );
    DELETE FROM `#content_type_filters` WHERE _type IN ( SELECT * FROM __contentTypes );
    DELETE FROM `#content_type_fields` WHERE _type IN ( SELECT * FROM __contentTypes );
    DELETE FROM `#content_type` WHERE id IN ( SELECT * FROM __contentTypes );
    DROP TEMPORARY TABLE __contentTypes;
    DROP TEMPORARY TABLE __categories;
    DROP TEMPORARY TABLE __content;
    DROP TEMPORARY TABLE __comments;
    DELETE FROM `#role_permissions`
    WHERE _permission IN (
      SELECT id FROM `#permissions`
      WHERE _plugin_id = pluginId
    );
    DELETE FROM `#permissions` WHERE _plugin_id = pluginId;
    DELETE FROM `#language_words` WHERE _plugin_id = pluginId;
    DELETE FROM `#emails` WHERE _plugin_id = pluginId;
    DELETE FROM `#plugin_settings` WHERE _plugin_id = pluginId;
    DELETE FROM `#tasks` WHERE _plugin_id = pluginId;
    UPDATE `#plugins` SET _enabled = 'n' WHERE id = pluginId;

  END;
