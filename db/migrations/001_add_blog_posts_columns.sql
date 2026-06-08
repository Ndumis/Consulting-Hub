-- Migration: Add missing columns to blog_posts so it fully replaces marketing_blog_posts
-- Run this against your kconsulting database before deploying the updated PHP code.

ALTER TABLE `blog_posts`
    ADD COLUMN IF NOT EXISTS `client_id`   INT          DEFAULT NULL AFTER `views`,
    ADD COLUMN IF NOT EXISTS `campaign_id` INT          DEFAULT NULL AFTER `client_id`,
    ADD COLUMN IF NOT EXISTS `author_id`   INT          DEFAULT NULL AFTER `campaign_id`,
    ADD COLUMN IF NOT EXISTS `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Optional: add foreign key indexes for performance
ALTER TABLE `blog_posts`
    ADD INDEX IF NOT EXISTS `idx_bp_client_id`   (`client_id`),
    ADD INDEX IF NOT EXISTS `idx_bp_campaign_id` (`campaign_id`),
    ADD INDEX IF NOT EXISTS `idx_bp_author_id`   (`author_id`);

-- After verifying the new code works, you can drop the old table:
-- DROP TABLE IF EXISTS `marketing_blog_posts`;
