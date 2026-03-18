<?php

class AssistantClientPromptBuilder extends WP_AI_Client_Prompt_Builder {

    var $abilities = [];

    /**
     * See the WP_AI_Client_Prompt_Builder constructor, copied here to control the PromptBuilder class...
     * not the best idea, but the only way right now.
     *
     * @param string $prompt
     * @param string $category Abilities category to use
     */
    #[\Override]
    public function __construct($prompt = null, $categories = []) {
        parent::__construct(WordPress\AiClient\AiClient::defaultRegistry(), $prompt);

        $this->abilities = array_filter(wp_get_abilities(), function ($ability) use ($categories) {
            /** @var WP_Ability $ability */
            return in_array($ability->get_category(), $categories);
        });

        $this->using_abilities(...$this->abilities);

        $this->using_system_instruction(file_get_contents(__DIR__ . '/system.md')); // Add a settings

        if (is_user_logged_in()) {
            $secret = get_option('assistant_secret');
            $messages = unserialize(@file_get_contents(ASSISTANT_CACHE_DIR . '/' . get_current_user_id() . '-' . $secret . '.txt'));
            if ($messages) {
                $this->with_history(...$messages);
            }
        }
    }

    /**
     * Bad but necessary things.
     *
     * @param \WordPress\AiClient\Messages\DTO\Message $message
     */
    function add_message($message) {
        $reflection = new ReflectionClass(parent::class);
        $property = $reflection->getProperty('builder');
        $property->setAccessible(true);
        $builder = $property->getValue($this);

        $reflection = new ReflectionClass($builder);
        $property = $reflection->getProperty('messages');
        $property->setAccessible(true);

        $messages = $property->getValue($builder);
        $messages[] = $message;
        $property->setValue($builder, $messages);
    }

    /**
     * Bad but necessary things.
     *
     * @param \WordPress\AiClient\Messages\DTO\Message $message
     */
    function get_messages() {
        $reflection = new ReflectionClass(parent::class);
        $property = $reflection->getProperty('builder');
        $property->setAccessible(true);
        $builder = $property->getValue($this);

        $reflection = new ReflectionClass($builder);
        $property = $reflection->getProperty('messages');
        $property->setAccessible(true);

        $messages = $property->getValue($builder);

        return $messages;
    }

    function get_function_resolver() {
        return new WP_AI_Client_Ability_Function_Resolver(...$this->abilities);
    }
}
