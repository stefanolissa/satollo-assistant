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

define('ASSISTANT_VERSION', '0.0.5');

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

/**
 * Update
 */
add_filter('update_plugins_satollo_assistant', function ($update, $plugin_data, $plugin_file, $locales) {

    $data = get_option('assistant_update_data');
    if ($data->updated < time() - WEEK_IN_SECONDS || isset($_GET['force-check'])) {
        $data = null;
    }

    if (!$data) {
        $response = wp_remote_get('https://www.satollo.net/repo/assistant/assistant.json');
        $data = json_decode(wp_remote_retrieve_body($response));
        if (is_object($data)) {
            $data->updated = time();
            update_option('assistant_update_data', $data);
        }
    }

    if (isset($data->version)) {

        $update = [
            'version' => $data->version,
            'slug' => 'assistant',
            'url' => 'https://www.satollo.net/plugins/assistant',
            'package' => 'https://www.satollo.net/repo/assistant/assistant.zip'
        ];
        return $update;
    } else {
        return false;
    }
}, 0, 4);


add_filter('plugins_api', 'assistant_plugin_api_hook', 20, 3);

function assistant_plugin_api_hook($res, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== 'assistant') {
        return $res;
    }

    $response = wp_remote_get('https://www.satollo.net/repo/assistant/CHANGELOG.md');
    $changelog = '';
    if (wp_remote_retrieve_response_code($response) == '200') {
        $changelog = wp_remote_retrieve_body($response);
        $changelog = preg_replace('/^### (.*$)/m', '<h4>$1</h4>', $changelog);
        $changelog = preg_replace('/^## (.*$)/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^# (.*$)/m', '', $changelog);
        $changelog = preg_replace('/^- (.*$)/m', '- $1<br>', $changelog);
        $changelog = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $changelog);
        $changelog = wpautop($changelog, false);
        $changelog = wp_kses_post($changelog);
    }

    $response = wp_remote_get('https://www.satollo.net/repo/assistant/README.md');
    $readme = '';
    if (wp_remote_retrieve_response_code($response) == '200') {
        $readme = wp_remote_retrieve_body($response);
        $readme = preg_replace('/^### (.*$)/m', '<h4>$1</h4>', $readme);
        $readme = preg_replace('/^## (.*$)/m', '<h3>$1</h3>', $readme);
        $readme = preg_replace('/^- (.*$)/m', '- $1<br>', $readme);
        //$readme = preg_replace('/^# (.*$)/m', '<h2>$1</h2>', $readme);
        $readme = preg_replace('/^# (.*$)/m', '', $readme);
        $readme = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $readme);
        $readme = wpautop($readme, false);
        $readme = wp_kses_post($readme);
    }

    $res = new stdClass();
    $res->name = 'Assistant';
    $res->slug = 'assistant';
    $res->version = ASSISTANT_VERSION;
    $res->author = '<a href="https://www.satollo.net">Stefano Lissa</a>';
    $res->homepage = 'https://www.satollo.net/plugins/assistant';
    $res->download_link = 'https://www.satollo.net/repo/assistant/assistant.zip';

    // This creates the tabs in the popup (Description, Installation, Changelog)
    $res->sections = array(
        'description' => $readme,
        //'installation' => 'Upload the zip and activate it. Simple!',
        'changelog' => $changelog,
            //'custom_tab'   => 'You can even add extra tabs here.'
    );

    $res->banners = [
        'low' => 'https://www.satollo.net/repo/assistant/banner.png',
        'high' => 'https://www.satollo.net/repo/assistant/banner.png'
    ];

    $res->icons = [
        '1x' => 'https://www.satollo.net/repo/assistant/icon.png',
        '2x' => 'https://www.satollo.net/repo/assistant/icon.png'
    ];

    return $res;
}

