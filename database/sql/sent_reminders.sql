-- Import this in phpMyAdmin into the NEW (app) database, only if you cannot run `php artisan migrate`.
CREATE TABLE IF NOT EXISTS `sent_reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_type` varchar(20) NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `expires_on` date NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sent_reminders_item_type_item_id_expires_on_unique` (`item_type`,`item_id`,`expires_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
