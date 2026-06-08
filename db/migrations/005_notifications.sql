-- Migration 005: notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    user_id     INT NOT NULL,
    type        VARCHAR(50)  NOT NULL DEFAULT 'info',   -- info|success|warning|leave|invoice|project|hr
    title       VARCHAR(255) NOT NULL,
    message     TEXT,
    link        VARCHAR(255),
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created   (created_at)
);
