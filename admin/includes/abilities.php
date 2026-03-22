<?php

defined('ABSPATH') || exit;

add_action('wp_abilities_api_categories_init', function () {
    wp_register_ability_category(
            'assistant-admin',
            [
                'label' => 'Assistant Administration abilities',
                'description' => 'Set of abilities for the admin side',
            ]
    );
});

// Dummy abilities
add_action('wp_abilities_api_init', function () {

    $r = wp_register_ability('assistant/change-site-language',
            [
                'label' => 'Change the current site language',
                'description' => 'Change the current site language',
                'category' => 'assistant-admin',
                'input_schema' => [],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'string',
                            'description' => 'Result of the operation',
                        ],
                    ],
                ],
                'execute_callback' => function ($input = null) {
                    return ['result' => 'This function is not supported yet'];
                },
                'permission_callback' => function () {
                    return current_user_can('administrator');
                },
                'meta' => [
                    'instructions' => 'Use the language list tool to decode the language code into the language name'
                ],
            ]
    );
});

