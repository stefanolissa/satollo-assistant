<?php

defined('ABSPATH') || exit;

global $wpdb, $charset_collate;

if (WP_DEBUG) {
    error_log('Assistant > Activating');
}

require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // Isn't there a constant for the admin inclusion path?

$sql = "CREATE TABLE `" . $wpdb->prefix . "assistant_assistants` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` int NOT NULL DEFAULT 0,

            `name` varchar(200) NOT NULL DEFAULT '',
            `description` varchar(500) NOT NULL DEFAULT '',
            `categories` varchar(1000) NOT NULL DEFAULT '',
            `instructions` TEXT,
            PRIMARY KEY (`id`),
            KEY `name` (`name`)
            ) $charset_collate;";

dbDelta($sql);
if ($wpdb->last_error) {
    error_log('Assistant > ' . $wpdb->last_error);
}

// Cleanup process
//if (!wp_next_scheduled('assistant_clean_logs') && (!defined('WP_INSTALLING') || !WP_INSTALLING)) {
//    wp_schedule_event(time() + 30, 'daily', 'assistant_clean_logs');
//}
