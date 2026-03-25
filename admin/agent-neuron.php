<?php

defined('ABSPATH') || exit;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\ArrayProperty;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;

class AssistantAgent extends Agent {

    var $category;

    function __construct($category = 0) {
        parent::__construct();
        $this->category = sanitize_key($category);
    }

    protected function provider(): AIProviderInterface {
        $settings = get_option('assistant_settings', []);
        switch ($settings['provider']) {
            case 'mistral':
                return new NeuronAI\Providers\Mistral\Mistral(
                        key: $settings['mistral_key'],
                        model: $settings['mistral_model'] ?: 'mistral-medium-2508',
                );
            case 'openai':
                return new OpenAI(
                        key: $settings['openai_key'],
                        model: $settings['openai_model'] ?: 'gpt-5-nano',
                );
            case 'anthropic':
                return new \NeuronAI\Providers\Anthropic\Anthropic(
                        key: $settings['anthropic_key'],
                        model: $settings['anthropic_model'] ?: 'claude-3-haiku-20240307',
                        parameters: ['max_tokens' => 4096]
                );
            case 'gemini':
                return new \NeuronAI\Providers\Gemini\Gemini(
                        key: $settings['gemini_key'],
                        model: $settings['gemini_model'] ?: 'gemini-2.5-flash',
                        parameters: []
                );
        }
    }

    public function instructions(): string {
        $instructions = '';
        $category = wp_get_ability_category($this->category);
        if ($category) {
            $instructions = $category->get_meta()['instructions'] ?? '';
        }
        return file_get_contents(__DIR__ . '/system.md') . ' ' . $instructions . ' Use the language ' . wp_get_current_user()->locale;
    }

    protected function tools(): array {

        if (!function_exists('wp_get_abilities')) {
            return [];
        }

        $abilities = wp_get_abilities();
        $tools = [];

        foreach ($abilities as $ability) {

            if ($this->category && $ability->get_category() !== $this->category) {
                continue;
            }

            $tool_name = str_replace('/', '-', $ability->get_name());
            $tool_description = $ability->get_description() . ' ' . $ability->get_meta_item('instructions', '');

            $tool = Tool::make($tool_name, $tool_description);

            $properties = $ability->get_input_schema()['properties'] ?? [];
            $required = $ability->get_input_schema()['required'] ?? [];

            // Neuron tool does not accept a schema (argh!!!)
            // This is my dumb conversion code...
            foreach ($properties as $name => $data) {
                if ($data['type'] === 'array') {
                    $items = ToolProperty::make(
                            'items',
                            PropertyType::fromSchema($data['items']['type']),
                            $data['items']['description'] ?? '',
                            false,
                            $data['items']['enum'] ?? []
                    );
                    $tool->addProperty(ArrayProperty::make(
                                    $name,
                                    $data['description'] ?? '',
                                    in_array($name, $required),
                                    $items,
                                    $data['minItems'] ?? 0,
                                    $data['maxItems'] ?? 9999
                            ));
                } else {
                    $enum = $data['enum'] ?? []; // Ok, I know...
                    $tool->addProperty(new ToolProperty(
                                    $name,
                                    PropertyType::fromSchema($data['type']),
                                    $data['description'],
                                    in_array($name, $required),
                                    $enum
                    ));
                }
            }


            $tool->setCallable(function (...$args) use ($ability) {

                // Null must be passed to abilities without an input schema
                if (empty($args)) {
                    $r = $ability->execute(null);
                } else {
                    $r = $ability->execute($args);
                }

                if (is_wp_error($r)) {
                    return $r->get_error_message();
                }

                if (is_array($r)) {
                    return wp_json_encode($r);
                }
                return $r;
            });

            $tools[] = $tool;
        }

        return $tools;
    }

    protected function chatHistory(): ChatHistoryInterface {
        return new FileChatHistory(
                directory: __DIR__,
                key: 'neuron',
                contextWindow: 2000
        );
    }
}

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class AssistantLogger extends AbstractLogger {

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array  $context
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void {
        if (!WP_DEBUG) {
            return;
        }
        if ($message === 'message-saving' || $message === 'message-saved') {
            return;
        }
        error_log(
                '[' .
                strtoupper($level) .
                '] ' .
                $message . ' ' .
                wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
    }
}
