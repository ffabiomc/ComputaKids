CREATE TABLE rate_limits (
 bucket BINARY(32) PRIMARY KEY,
 window_id BIGINT UNSIGNED NOT NULL,
 attempts INT UNSIGNED NOT NULL,
 KEY ix_window (window_id)
) ENGINE=InnoDB;
