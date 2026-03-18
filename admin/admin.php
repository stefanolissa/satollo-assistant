<?php

defined('ABSPATH') || exit;

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
                    default:
                        include __DIR__ . '/index.php';
                }
            },
            'dashicons-smiley', 6
    );
});

$assistant_settings = get_option('assistant_settings', []);

if (($assistant_settings['framework'] ?? 'neuron') === 'neuron') {

    add_action('wp_ajax_assistant_message', function () {
        if (!current_user_can('administrator')) {
            die();
        }
        check_ajax_referer('prompt');

        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/agent.php';
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
} else {

    add_action('wp_ajax_assistant_message', function () {
        if (!current_user_can('administrator')) {
            die();
        }
        check_ajax_referer('prompt');
        include __DIR__ . '/builder.php';

        try {
            $secret = get_option('assistant_secret');
            $messages = unserialize(@file_get_contents(ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-admin-' . $secret . '.txt'));

            $category = sanitize_key($_POST['category'] ?? '');
            $abilities = wp_get_abilities();

            $abilities = array_filter($abilities, function ($ability) use ($category) {
                /** @var WP_Ability $ability */
                return $category === $ability->get_category();
            });

            $message = wp_strip_all_tags($_POST['message'] ?? '');

            $builder = new AssistantClientPromptBuilder($message);
            $builder->using_system_instruction(file_get_contents(__DIR__ . '/system.md')); // Add a settings
            $builder->using_abilities(...$abilities);

            if ($messages) {
                $builder->with_history(...$messages);
            }

            $result = $builder->generate_text_result();

            log_result($result);

            if (is_wp_error($result)) {
                error_log($result->get_error_message());
                echo wp_json_encode(['reply' => $result->get_error_message()]);
            }

            $fr = new WP_AI_Client_Ability_Function_Resolver(...$abilities);

            $reply = '';

            while ($fr->has_ability_calls($result->toMessage())) {
                $reply .= "*Tool called*\n\n";
                $builder->add_message($result->toMessage());
                $response = $fr->execute_abilities($result->toMessage());
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
                log_result($result);
                error_log(print_r($result->toMessage(), true));

                // The only way to enqueue the model message is to fake it as user message
                $m = $result->toMessage();
                $role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();
                $mm = new \WordPress\AiClient\Messages\DTO\Message($role, $m->getParts());

                // If that result message is enqueued and error is generated... I don't know right now
                $builder->add_mmessage($mm);
            }

            file_put_contents(ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-admin-' . $secret . '.txt', serialize($builder->get_messages()));

            echo wp_json_encode(['reply' => $reply . $result->toText()]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
        die();
    });
}

function assistant_admin_prompt() {

    current_user_can('administrator') || die();
    check_ajax_referer('prompt');

    $category = sanitize_key($_POST['category'] ?? '');
    $message = wp_strip_all_tags($_POST['message'] ?? '');

    $assistant_settings = get_option('assistant_settings', []);
    $secret = get_option('assistant_secret');
    if (($assistant_settings['framework'] ?? 'neuron') === 'neuron') {

        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/agent.php';
        try {
            $agent = AssistantAgent::make($category);
            $agent->observe(new NeuronAI\Observability\LogObserver(new AssistantLogger()));

            $response = $agent->chat(
                    new \NeuronAI\Chat\Messages\UserMessage($message)
            );

            $content = $response->getContent();

            echo wp_json_encode(['reply' => $content]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
        die();
    } else {

        include __DIR__ . '/builder.php';

        try {
            $builder = new AssistantClientPromptBuilder($message);
            $builder->using_system_instruction(file_get_contents(__DIR__ . '/system.md')); // Add a settings

            $abilities = array_filter(wp_get_abilities(), function ($ability) use ($category) {
                /** @var WP_Ability $ability */
                return $category === $ability->get_category();
            });
            $builder->using_abilities(...$abilities);

            $messages = unserialize(@file_get_contents(ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-admin-' . $secret . '.txt'));
            if ($messages) {
                $builder->with_history(...$messages);
            }

            $result = $builder->generate_text_result();

            //log_result($result);

            if (is_wp_error($result)) {
                error_log($result->get_error_message());
                echo wp_json_encode(['reply' => $result->get_error_message()]);
            }

            $fr = new WP_AI_Client_Ability_Function_Resolver(...$abilities);

            $reply = '';

            while ($fr->has_ability_calls($result->toMessage())) {
                $reply .= "*Tool called*\n\n";
                $builder->add_message($result->toMessage());
                $response = $fr->execute_abilities($result->toMessage());
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
                //log_result($result);
                //error_log(print_r($result->toMessage(), true));
                // The only way to enqueue the model message is to fake it as user message
                $m = $result->toMessage();
                $role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();
                $mm = new \WordPress\AiClient\Messages\DTO\Message($role, $m->getParts());

                // If that result message is enqueued and error is generated... I don't know right now
                $builder->add_mmessage($mm);
            }

            file_put_contents(ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-admin-' . $secret . '.txt', $builder->get_messages());

            echo wp_json_encode(['reply' => $reply . $result->toText()]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
        die();
    }
}

function log_result($result) {
    if (is_wp_error($result)) {
        /** @var WP_Error $result */
        error_log($result->get_error_message());
        return;
    }
    $data = $result->jsonSerialize();
    $data['modelMetadata'] = [];
    error_log(print_r(json_encode($data, JSON_PRETTY_PRINT), true));
}
