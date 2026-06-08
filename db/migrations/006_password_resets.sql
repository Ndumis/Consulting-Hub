-- Migration 006: password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT         NOT NULL,
    token      VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME    NOT NULL,
    used_at    DATETIME    NULL,
    created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token   (token),
    INDEX idx_expires (expires_at)
);
