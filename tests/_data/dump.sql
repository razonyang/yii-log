DROP TABLE IF EXISTS `t_log`;

CREATE TABLE `t_log` (
  `id`           CHAR(36)
                 COLLATE utf8mb4_unicode_ci      NOT NULL,
  `application`  CHAR(64)
                 COLLATE utf8mb4_unicode_ci      NOT NULL DEFAULT ''
  COMMENT 'application name',
  `route`        CHAR(255)
                 COLLATE utf8mb4_unicode_ci      NOT NULL DEFAULT ''
  COMMENT 'requested route',
  `exit_status`  INT(11)                         NOT NULL DEFAULT '0'
  COMMENT 'exit status',
  `url`          TEXT COLLATE utf8mb4_unicode_ci NOT NULL
  COMMENT 'request url',
  `method`       CHAR(7)
                 COLLATE utf8mb4_unicode_ci      NOT NULL DEFAULT ''
  COMMENT 'request method',
  `ip`           CHAR(45)
                 COLLATE utf8mb4_unicode_ci      NOT NULL DEFAULT ''
  COMMENT 'IP address',
  `user_agent`   TEXT COLLATE utf8mb4_unicode_ci NOT NULL
  COMMENT 'user agent',
  `raw_body`     TEXT COLLATE utf8mb4_unicode_ci NOT NULL
  COMMENT 'raw body',
  `status`       INT(11)                         NOT NULL DEFAULT '0'
  COMMENT 'HTTP response status',
  `status_text`  CHAR(128)
                 COLLATE utf8mb4_unicode_ci      NOT NULL DEFAULT ''
  COMMENT 'HTTP response status text',
  `requested_at` DOUBLE                          NOT NULL
  COMMENT 'request time',
  `context`      TEXT COLLATE utf8mb4_unicode_ci NOT NULL
  COMMENT 'context',
  PRIMARY KEY (`id`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_application` (`application`),
  KEY `idx_route` (`route`),
  KEY `idx_method` (`method`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `t_log_message`;

CREATE TABLE `t_log_message` (
  `log_id`       CHAR(36)
                 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_at` DOUBLE                     NOT NULL
  COMMENT 'request time',
  `message_id`   INT(11)                    NOT NULL
  COMMENT 'message id',
  `level`        TINYINT(3)                 DEFAULT NULL,
  `category`     VARCHAR(255)
                 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_time` DOUBLE                     DEFAULT NULL,
  `prefix`       TEXT COLLATE utf8mb4_unicode_ci,
  `message`      TEXT COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`log_id`, `message_id`),
  KEY `idx_level` (`level`),
  KEY `idx_category` (`category`),
  KEY `idx_message_time` (`message_time`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;