DROP TABLE IF EXISTS `t_log`;

CREATE TABLE `t_log` (
  `log_id`       INT(11)                    DEFAULT NULL
  COMMENT 'log id',
  `request_id`   CHAR(32)
                 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_at` DOUBLE                     DEFAULT NULL,
  `level`        TINYINT(3)                 DEFAULT NULL,
  `category`     VARCHAR(255)
                 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `log_time`     DOUBLE                     DEFAULT NULL,
  `prefix`       TEXT COLLATE utf8mb4_unicode_ci,
  `message`      TEXT COLLATE utf8mb4_unicode_ci,
  `rotate`       TINYINT(3)                 DEFAULT NULL,
  UNIQUE KEY `PRIMARY KEY` (`request_id`, `log_id`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_log_level` (`level`),
  KEY `idx_log_category` (`category`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;