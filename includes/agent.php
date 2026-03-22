<?php

defined('ABSPATH') || exit;

function assistant_ajax_prompt() {
    check_ajax_referer('shortcode');
    $assistant_settings = get_option('assistant_settings', []);
    if (($assistant_settings['framework'] ?? 'neuron') === 'neuron') {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/agent-neuron.php';
        try {
            // TODO: From settings, get the allowed abilities or categories
            $agent = AssistantAgent::make('assistant');
            $agent->observe(new NeuronAI\Observability\LogObserver(new AssistantLogger()));

            $message = wp_strip_all_tags($_POST['message'] ?? '');

            $response = $agent->chat(
                    new \NeuronAI\Chat\Messages\UserMessage($message)
            );

            $content = $response->getMessage()->getContent();

            echo wp_json_encode(['reply' => $content]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
    } else {
        include __DIR__ . '/agent-wp.php';

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
                    wp_json_encode(['reply' => $result->get_error_message()]);
                    return;
                    break;
                }
                $builder->add_message($response);
                $result = $builder->generate_text_result();

                //$result = $builder->with_message_parts($response)->generate_text_result();
                if (is_wp_error($result)) {
                    error_log($result->get_error_message());
                    wp_json_encode(['reply' => $result->get_error_message()]);
                    return;
                    break;
                }

                // The only way to enqueue the model message is to fake it as user message
                $mm = $result->toMessage();
                // If it's a second function call, do not change the message role
                if (!$fr->has_ability_calls($result->toMessage())) {
                    $role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();
                    $mm = new \WordPress\AiClient\Messages\DTO\Message($role, $mm->getParts());
                }

                // If that result message is enqueued and error is generated... I don't know right now
                $builder->add_message($mm);
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
