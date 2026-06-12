-- Migration 010: fix featured_image URLs that were generated with the
-- requesting page's script path baked into APP_URL (bug in the
-- base-path detection in config/app.php, fixed alongside this migration).
-- e.g. https://host/departments/marketing.php/uploads/marketing/foo.png
--   -> https://host/uploads/marketing/foo.png
UPDATE `blog_posts`
SET `featured_image` = REPLACE(`featured_image`, '/departments/marketing.php/uploads/', '/uploads/')
WHERE `featured_image` LIKE '%/departments/marketing.php/uploads/%';
