<?php

defined('ABSPATH') || exit;

add_action('wp_abilities_api_categories_init', function () {
    wp_register_ability_category(
            'assistant',
            [
                'label' => 'Assistant abilities',
                'description' => 'Set of abilities to interact with the users',
            ]
    );
});

// Dummy abilities
add_action('wp_abilities_api_init', function () {

    $r = wp_register_ability('assistant/change-email',
            [
                'label' => 'Change my email',
                'description' => 'Change the current user email sending a confirmation',
                'category' => 'assistant',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'email' => [
                            'type' => 'string',
                            'description' => 'The new email',
                            'format' => 'email'
                        ]
                    ],
                    'required' => ['email'],
                    'additionalProperties' => false,
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'string',
                            'description' => 'The result of the email change',
                        ],
                    ],
                ],
                'execute_callback' => function ($input) {
                    $user_id = get_current_user_id();

                    // No... see wp-includes/user.php - why there is not a dedicated function
                    // not coupled with $_POST??? OMG...
                    global $errors;
                    $errors = null;
                    $_POST['user_id'] = $user_id;
                    $_POST['email'] = sanitize_email($input['email']);

                    send_confirmation_on_profile_email();

                    if ($errors) {
                        return ['result' => 'The email cannot be change due to ' . $errors->get_error_message()];
                    } else {
                        return['result' => 'Email change started, check your mailbox to confirm'];
                    }

                    /*
                      $email = sanitize_email($input['email']);

                      if (!$email) {
                      return['result' => 'The provided email is not valid.'];
                      }

                      $result = wp_update_user([
                      'ID' => $user_id,
                      'user_email' => $input['email'],
                      ]);

                      if (is_wp_error($result)) {
                      return ['result' => 'The email cannot be change due to ' . $result->get_error_message()];
                      } else {
                      return['result' => 'Email change started, check your mailbox to confirm'];
                      }
                     */
                },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'meta' => [
                ],
            ]
    );

    if ($r === null) {
        error_log('Ability not registsred');
    }

    $r = wp_register_ability('assistant/change-name',
            [
                'label' => 'Change my name',
                'description' => 'Change the current user name',
                'category' => 'assistant',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'first_name' => [
                            'type' => 'string',
                            'description' => 'The new first name',
                        ]
                    ],
                    'required' => ['first_name'],
                    'additionalProperties' => false,
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'string',
                            'description' => 'The result of the name change',
                        ],
                    ],
                ],
                'execute_callback' => function ($input) {
                    $user_id = get_current_user_id();
                    $input['first_name'] = ucwords($input['first_name']);

                    $result = wp_update_user([
                        'ID' => $user_id,
                        'first_name' => $input['first_name'],
                    ]);

                    if (is_wp_error($result)) {
                        return ['result' => 'The name cannot be changed due to ' . $result->get_error_message()];
                    } else {
                        return['result' => 'First name changed, welcome ' . $input['first_name']];
                    }
                },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'meta' => [
                ],
            ]
    );

    if ($r === null) {
        error_log('Ability not registsred');
    }

    $r = wp_register_ability('assistant/change-role',
            [
                'label' => 'Change my role',
                'description' => 'Change the current user role',
                'category' => 'assistant',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'role' => [
                            'type' => 'string',
                            'description' => 'The new role',
                            'enum' => ['subscriber', 'editor', 'administrator']
                        ]
                    ],
                    'required' => ['role'],
                    'additionalProperties' => false,
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'string',
                            'description' => 'The result of the role change',
                        ],
                    ],
                ],
                'execute_callback' => function ($input) {

                    //return ['result' => 'Admit it, for a moment, you believed it would have worked.'];
                    return ['result' => 'Was joking the role the role cannot be changed!'];
                },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'meta' => [
                ],
            ]
    );

    if ($r === null) {
        error_log('Ability not registsred');
    }

    $r = wp_register_ability('assistant/list-languages',
            [
                'label' => 'List the available languages',
                'description' => 'List the available languages',
                'category' => 'assistant',
                'input_schema' => [],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'array',
                            'description' => 'The list of the languages as JSON',
                        ],
                    ],
                ],
                'execute_callback' => function ($input = null) {
                    $locales = get_available_languages(); // Only the code xx_YY
                    if (!$locales) {
                        $locales[] = 'en_US';
                    }

                    require_once ABSPATH . 'wp-admin/includes/translation-install.php';
                    $translations = wp_get_available_translations();
                    $translations['en_US'] = [
                        'language' => 'en_US',
                        'english_name' => 'English (United States)',
                        'native_name' => 'English (United States)',
                        'iso' => ['en']
                    ];

                    error_log(print_r($locales, true));
                    //error_log(print_r($translations, true));

                    $languages = array();
                    foreach ($locales as $locale) {
                        if (isset($translations[$locale])) {
                            $translation = $translations[$locale];
                            $languages[] = array(
                                //'language' => $translation['language'],
                                'code' => $translation['language'],
                                'native_name' => $translation['native_name'],
                                //'lang' => current($translation['iso']),
                            );

                        } else {
                            continue;
//                            $languages[] = array(
//                                'language' => $locale,
//                                'native_name' => $locale,
//                                'lang' => '',
//                            );
                        }
                    }
                    error_log(print_r($languages, true));
                    return $languages;
                },
                'permission_callback' => function () {
                    return true;
                    //return is_user_logged_in();
                },
                'meta' => [
                ],
            ]
    );

                    $r = wp_register_ability('assistant/change-language',
            [
                'label' => 'Change my language',
                'description' => 'Change the current user role',
                'category' => 'assistant',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'language_code' => [
                            'type' => 'string',
                            'description' => 'The new language code, use the languages list to find the codes',
                        ]
                    ],
                    'required' => ['language_code'],
                    'additionalProperties' => false,
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'result' => [
                            'type' => 'string',
                            'description' => 'The result of the operation',
                        ],
                    ],
                ],
                'execute_callback' => function ($input) {
                        error_log($input['language_code']);
                    //return ['result' => 'Admit it, for a moment, you believed it would have worked.'];
                    return ['result' => 'The language was changed'];
                },
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'meta' => [
                ],
            ]
    );
//    $r = wp_register_ability('test/site-health',
//            [
//                'label' => 'Checks the site health',
//                'description' => 'Checks the site health and returns a report with the main issues about the system, plugins, scheduler, optimizations',
//                'input_schema' => [
//                    'type' => 'object',
//                    'properties' => [],
//                    'additionalProperties' => false,
//                ],
//                'output_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'result' => [
//                            'type' => 'string',
//                            'description' => 'Some kind of data',
//                            'minLength' => 0
//                        ],
//                    ],
//                ],
//                'execute_callback' => function () {
//                    return ['result' => 'The site is ok, there is nothing to do.'];
//                },
//                'permission_callback' => function () {
//                    return true;
//                },
//                'meta' => [
//                    'category' => 'test',
//                ],
//            ]
//    );
//    $r = wp_register_ability('test/site-health',
//            [
//                'label' => 'Checks the site health',
//                'description' => 'Checks the site health and returns a report with the main issues about the system, plugins, scheduler, optimizations',
//                'input_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'number_of_posts' => [
//                            'type' => 'integer',
//                            'description' => 'Number of posts to include into the newsletter',
//                            'minLength' => 0
//                        ],
//                    ],
//                    'additionalProperties' => false,
//                ],
//                'output_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'field1' => [
//                            'type' => 'string',
//                            'description' => 'Some kind of data',
//                            'minLength' => 0
//                        ],
//                    ],
//                ],
//                'execute_callback' => function () {
//                    return 'The newsletter has been created, no other actions are needed. The newsletter can be modified or sent from the editing page '
//        . ' at url https://localhost/edit-newsletter';
//                },
//                'permission_callback' => function () {
//                    return true;
//                },
//                'meta' => [
//                    'category' => 'dummy',
//                ],
//            ]
//    );
//
//    $r = wp_register_ability('newsletter/get-subscribers-statistics',
//            [
//                'label' => 'Generates statistics about the subscribers',
//                'description' => 'Generates statistics about the subscribers returning the number of confirmed, unconfirmed, bounced.',
//                'input_schema' => [
//                    'type' => 'object',
//                    'properties' => [],
//                    'additionalProperties' => false,
//                ],
//                'output_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'confirmed' => [
//                            'type' => 'integer',
//                            'description' => 'Number of confirmed subscribers',
//                        ],
//                        'not_confirmed' => [
//                            'type' => 'integer',
//                            'description' => 'Number of not confirmed subscribers',
//                        ],
//                        'bounced' => [
//                            'type' => 'integer',
//                            'description' => 'Number of bounced subscribers',
//                        ],
//                    ],
//                ],
//                'execute_callback' => function ($input) {
//                    error_log(print_r($input, true));
//                    return ['confirmed' => 12, 'not_confirmed' => 3, 'bounced' => 4];
//                },
//                'permission_callback' => function () {
//                    return true;
//                },
//                'meta' => [
//                    'category' => 'dummy',
//                ],
//            ]
//    );
//
//                $r = wp_register_ability('newsletter/update-subscriber',
//            [
//                'label' => 'Update a subscriber',
//                'description' => 'Update a subscriber chaging one or more details between status (confirmed, unconfirmed), the first name and the last name',
//                'input_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'email' => [
//                            'type' => 'string',
//                            'description' => 'The subscriber email to identify and update it',
//                            'required' => true,
//                        ],
//                        'status' => [
//                            'type' => 'string',
//                            'description' => 'The subscriber status with value confirmed or unconfirmed',
//                            'required' => false,
//                            'enum' => ['confirmed', 'unconfirmed']
//                        ]
//                    ],
//                    'additionalProperties' => false,
//                ],
//                'output_schema' => [
//                    'type' => 'object',
//                    'properties' => [
//                        'result' => [
//                            'type' => 'string',
//                            'description' => 'If the operation has been successful or not',
//                        ]
//                    ],
//                ],
//                'execute_callback' => function () {
//                    return ['result' => 'Subscriber not found, provide another email address.'];
//
//                },
//                'permission_callback' => function () {
//                    return true;
//                },
//                'meta' => [
//                    'category' => 'dummy',
//                ],
//            ]
//    );
});

