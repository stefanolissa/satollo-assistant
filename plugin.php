<?php

/**
 * Plugin Name: Assistant
 * Description: Assistant based on AI to interact with your WP abilities
 * Version: 0.0.6
 * Author: satollo
 * Author URI: https://www.satollo.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: assistant
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Plugin URI: https://www.satollo.net/plugins/assistant
 * Update URI: satollo-assistant
 */
defined('ABSPATH') || exit;

define('ASSISTANT_VERSION', '0.0.6');

//register_activation_hook(__FILE__, function () {
//    require_once __DIR__ . '/admin/activate.php';
//});
//register_deactivation_hook(__FILE__, function () {
//});

if (is_admin()) {
    require_once __DIR__ . '/admin/admin.php';
    // Test abilities
    //require_once __DIR__ . '/includes/abilities.php';
}

if (is_admin() || defined('DOING_CRON') && DOING_CRON) {
    require_once __DIR__ . '/includes/repo.php';
}
