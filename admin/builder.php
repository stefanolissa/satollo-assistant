<?php

class AssistantClientPromptBuilder extends WP_AI_Client_Prompt_Builder {

    #[\Override]
    public function __construct($prompt = null) {
        parent::__construct(WordPress\AiClient\AiClient::defaultRegistry(), $prompt);
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
