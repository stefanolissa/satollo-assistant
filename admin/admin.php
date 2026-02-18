<?php

defined('ABSPATH') || exit;

// TODO: Move on activate
wp_mkdir_p(WP_CONTENT_DIR . '/cache/assistant');

add_action('admin_menu', function () {

    add_menu_page(
            'Assistant', 'Assistant', 'administrator', 'assistant',
            function () {
                include __DIR__ . '/index.php';
            },
            'dashicons-smiley', 6
    );

    add_submenu_page(
            'assistant', 'Settings', 'Settings', 'administrator', 'assistant-settings',
            function () {
                include __DIR__ . '/settings.php';
            }
    );

    add_submenu_page(
            'admin.php', 'Chat', 'Chat', 'administrator', 'assistant-chat',
            function () {
                include __DIR__ . '/chat.php';
            }
    );
});

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/agent.php';

add_action('wp_ajax_assistant_message', function () {

    try {
        $agent = AssistantAgent::make($_POST['category']);
        $agent->observe(new NeuronAI\Observability\LogObserver(new AssistantLogger()));

        $response = $agent->chat(
                new \NeuronAI\Chat\Messages\UserMessage($_POST['message'])
        );

        $content = $response->getContent();

        echo wp_json_encode(['reply' => $content]);
    } catch (Exception $e) {
        echo wp_json_encode(['reply' => $e->getMessage()]);
    }
    die();
});

