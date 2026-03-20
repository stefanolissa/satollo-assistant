<?php

/**
 * Plugin Name: Satollo Assistant
 * Description: Assistant based on AI to interact with your WP abilities
 * Version: 0.0.7
 * Author: Stefano Lissa
 * Author URI: https://www.satollo.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: satollo-assistant
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Plugin URI: https://www.satollo.net/plugins/assistant
 * Update URI: satollo-assistant
 */
defined('ABSPATH') || exit;

if (version_compare(wp_get_wp_version(), '6.9', '<=')) {
    return;
}

define('ASSISTANT_VERSION', '0.0.7');
define('ASSISTANT_CACHE_DIR', __DIR__ . '/cache');

//register_activation_hook(__FILE__, function () {
//    //require_once __DIR__ . '/admin/activate.php';
//});

//register_deactivation_hook(__FILE__, function () {
//});

add_action('init', function () {
    if (!is_admin() && (!defined('REST_REQUEST') || !REST_REQUEST)) {
        add_shortcode('assistant', function ($attrs, $content) {
            if (!is_user_logged_in()) {
                return '<p style="padding: 1em; background-color: #eee"><strong>To use the assistant you need to '
                . '<a href="' . site_url('wp-login.php') . '?redirect_to=/assistant/assistant">log in</a> '
                . 'or <a href="' . site_url('wp-login.php') . '?action=register">create an account</a>.</strong></p>';
            }

            $categories = wp_parse_list($attrs['categories'] ?? ['assistant']);

            ob_start();
            include __DIR__ . '/includes/chat.php';
            return ob_get_clean();
        });
    }

    if (wp_doing_ajax()) {
        add_action('wp_ajax_assistant_prompt', 'assistant_ajax_prompt');
        add_action('wp_ajax_nopriv_assistant_prompt', 'assistant_ajax_prompt');
        require_once __DIR__ . '/includes/agent.php';
    }
});

require_once __DIR__ . '/includes/abilities.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/admin.php';
}

if (is_admin() || defined('DOING_CRON') && DOING_CRON) {
    require_once __DIR__ . '/includes/repo.php';
}
