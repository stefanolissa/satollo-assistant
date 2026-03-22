<?php

defined('ABSPATH') || exit;

$version = get_option('assistant_version');
if (ASSISTANT_VERSION !== $version) {
    include_once __DIR__ . '/includes/activate.php';
    update_option('assistant_version', ASSISTANT_VERSION, false);
}

add_action('admin_menu', function () {

    add_menu_page(
            'Assistant', 'Assistant', 'administrator', 'assistant',
            function () {
                $subpage = $_GET['subpage'] ?? '';
                switch ($subpage) {
                    case 'chat':
                        include __DIR__ . '/chat.php';
                        break;
                    case 'settings':
                        include __DIR__ . '/settings.php';
                        break;
                    case 'list':
                        include __DIR__ . '/assistants/list.php';
                        break;
                    case 'edit':
                        include __DIR__ . '/assistants/edit.php';
                        break;
                    default:
                        include __DIR__ . '/index.php';
                }
            },
            'dashicons-smiley', 6
    );
});

if (wp_doing_ajax()) {
    add_action('wp_ajax_assistant_message', 'assistant_ajax_admin_prompt');
    require_once __DIR__ . '/agent.php';
}

require_once __DIR__ . '/includes/php-api.php';
require_once __DIR__ . '/includes/abilities.php';
