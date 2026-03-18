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

register_activation_hook(__FILE__, function () {

    //require_once __DIR__ . '/admin/activate.php';
});

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

            // Clear the chat history
            // TODO: the chat history must be per sesison!!!

            ob_start();
            include __DIR__ . '/includes/chat.php';
            return ob_get_clean();
        });
    }

    if (wp_doing_ajax()) {
        add_action('wp_ajax_assistant_prompt', 'assistant_ajax_prompt');
        add_action('wp_ajax_nopriv_assistant_prompt', 'assistant_ajax_prompt');
    }
});

function assistant_ajax_prompt() {
    check_ajax_referer('shortcode');
    $assistant_settings = get_option('assistant_settings', []);
    if (($assistant_settings['framework'] ?? 'neuron') === 'neuron') {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/includes/agent.php';
        try {
            // TODO: From settings, get the allowed abilities or categories
            $agent = AssistantAgent::make('assistant');

            $message = wp_strip_all_tags($_POST['message'] ?? '');

            $response = $agent->chat(
                    new \NeuronAI\Chat\Messages\UserMessage($message)
            );

            $content = $response->getContent();

            echo wp_json_encode(['reply' => $content]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
    } else {
        include __DIR__ . '/includes/builder.php';

        try {

            $message = wp_strip_all_tags($_POST['message'] ?? '');

            $builder = new AssistantClientPromptBuilder($message, ['assistant']);

            $result = $builder->generate_text_result();

            if (is_wp_error($result)) {
                error_log($result->get_error_message());
                echo wp_json_encode(['reply' => $result->get_error_message()]);
            }

            $fr = $builder->get_function_resolver();

            $reply = '';

            while ($fr->has_ability_calls($result->toMessage())) {
                error_log('Tool call');
                $reply .= "*Tool called*\n\n";
                $builder->add_message($result->toMessage());
                $response = $fr->execute_abilities($result->toMessage());
                error_log(print_r($response, true));
                if (is_wp_error($response)) {
                    error_log($response->get_error_message());
                    break;
                }
                $builder->add_message($response);
                $result = $builder->generate_text_result();

                //$result = $builder->with_message_parts($response)->generate_text_result();
                if (is_wp_error($result)) {
                    error_log($result->get_error_message());
                    break;
                }

                // The only way to enqueue the model message is to fake it as user message
                $m = $result->toMessage();
                $role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();
                $mm = new \WordPress\AiClient\Messages\DTO\Message($role, $m->getParts());

                // If that result message is enqueued and error is generated... I don't know right now
                $builder->add_mmessage($mm);
            }

            if (is_user_logged_in()) {
                wp_mkdir_p(ASSISTANT_CACHE_DIR);
                $secret = get_option('assistant_secret');
                file_put_contents((ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-' . $secret . '.txt'), serialize($builder->get_messages()));
            }

            echo wp_json_encode(['reply' => $reply . $result->toText()]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
    }
    die();
}

require_once __DIR__ . '/includes/abilities.php';

if (is_admin()) {
    require_once __DIR__ . '/admin/admin.php';
}

if (is_admin() || defined('DOING_CRON') && DOING_CRON) {
    require_once __DIR__ . '/includes/repo.php';
}
