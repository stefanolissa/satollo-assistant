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

        try {
            $messages = unserialize(file_get_contents(__DIR__ . '/messages.txt'));

            $b = wp_ai_client_prompt();

            $category = sanitize_key($_POST['category'] ?? '');
            $abilities = wp_get_abilities();

            $abilities = array_filter($abilities, function ($ability) use ($category) {
                /** @var WP_Ability $ability */
                return $category === $ability->get_category();
            });

            $message = wp_strip_all_tags($_POST['message'] ?? '');

            $builder = wp_ai_client_prompt($message)
                    ->using_system_instruction(file_get_contents(__DIR__ . '/system.md')) // Add a settings
                    ->using_abilities(...$abilities);

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
                $reply .= 'tool called ';
                $builder->builder->messages[] = $result->toMessage();
                $response = $fr->execute_abilities($result->toMessage());
                if (is_wp_error($response)) {
                    error_log($response->get_error_message());
                    break;
                }
                $builder->builder->messages[] = $response;
                $result = $builder->generate_text_result();

                //$result = $builder->with_message_parts($response)->generate_text_result();
                if (is_wp_error($result)) {
                    error_log($result->get_error_message());
                    break;
                }
                log_result($result);
                error_log(print_r($result->toMessage(), true));
                // If that result message is enqueued and error is generated... I don't know right now
                $builder->builder->messages[] = $result->toMessage();
            }

            file_put_contents(__DIR__ . '/messages.txt', serialize($builder->builder->messages));

            echo wp_json_encode(['reply' => $reply . $result->toText()]);
        } catch (Exception $e) {
            echo wp_json_encode(['reply' => $e->getMessage()]);
        }
        die();
    });
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
