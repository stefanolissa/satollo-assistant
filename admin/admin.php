<?php

defined('ABSPATH') || exit;

// TODO: Move on activate
wp_mkdir_p(WP_CONTENT_DIR . '/cache/assistant');

add_action('admin_menu', function () {

    add_menu_page(
            'Assistant', 'Assistant', 'administrator', 'assistant',
            function () {
                $subpage = $_GET['subpage'] ?? '';
                switch ($subpage) {
                    case 'chat':
                        include __DIR__ . '/chat.php';
                        break;
                    default:
                        include __DIR__ . '/index.php';
                }
            },
            'dashicons-smiley', 6
    );

});

//require_once __DIR__ . '/../vendor/autoload.php';
//require_once __DIR__ . '/agent.php';
//
//add_action('wp_ajax_assistant_message', function () {
//
//    try {
//        $agent = AssistantAgent::make($_POST['category']);
//        $agent->observe(new NeuronAI\Observability\LogObserver(new AssistantLogger()));
//
//        $response = $agent->chat(
//                new \NeuronAI\Chat\Messages\UserMessage($_POST['message'])
//        );
//
//        $content = $response->getContent();
//
//        echo wp_json_encode(['reply' => $content]);
//    } catch (Exception $e) {
//        echo wp_json_encode(['reply' => $e->getMessage()]);
//    }
//    die();
//});

add_action('wp_ajax_assistant_message', function () {

    try {
        $category = sanitize_key($_POST['category'] ?? '');
        $abilities = wp_get_abilities();
        $selected = [];

        // Maybe using array_filter would be a good idea...
        foreach ($abilities as $ability) {
            if ($ability->get_category() !== $category) {
                continue;
            }
            $selected[] = $ability;
        }

        //error_log(print_r($selected, true));

        $message = $_POST['message'];

        $builder = wp_ai_client_prompt($message)
                ->using_system_instruction(file_get_contents(__DIR__ . '/system.md'))
                ->using_abilities(...$selected);

        $result = $builder->generate_text_result();

        if (is_wp_error($result)) {
            error_log($result->get_error_message());
            echo wp_json_encode(['reply' => $result->get_error_message()]);
        }

        //error_log(print_r($result, true));

        $fr = new WP_AI_Client_Ability_Function_Resolver(...$selected);

        while ($fr->has_ability_calls($result->toMessage())) {
            $response = $fr->execute_abilities($result->toMessage());
            if (is_wp_error($response)) {
                error_log($response->get_error_message);
                break;
            }
            $result = $builder->with_history($result->toMessage(), $response)->generate_text_result();
            if (is_wp_error($result)) {
                error_log($response->get_error_message);
                // the message sequence does not validate, missing a first user message, probably I need to add
                // it to the history...
                break;
            }
        }

        error_log(print_r($result, true));

        echo wp_json_encode(['reply' => $result->toText()]);
    } catch (Exception $e) {
        echo wp_json_encode(['reply' => $e->getMessage()]);
    }
    die();
});

