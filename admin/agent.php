<?php

defined('ABSPATH') || exit;

use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
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
        $this->category = sanitize_key($category);
    }

    protected function provider(): AIProviderInterface {
        $settings = get_option('assistant', []);
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
        return (string) new SystemPrompt(
                        background:
                        [
                            "Use a friendly tone and be very short when answering.",
                            "User only the provided tools. If the correct tool cannot be found reply there is no tool to complete the request.",
                            $instructions
                        ],
                        steps:
                        [
                        ],
                        output:
                        [
                            "Format the JSON arrays as markdown tables",
                            "Reformulate the content returned by the tools, unless the tool specifies display the contente as-is.",
                            "Translate the answer into the language used in the request.",
                            "Use markdown to format the response.",
                            "Links must open on a new tab"
                        ]
                );
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

            // TODO: Use the ability label?
            $tool = Tool::make(
                    str_replace('/', '-', $ability->get_name()),
                    $ability->get_description() . ' ' . $ability->get_meta_item('instructions', ''));
            $properties = $ability->get_input_schema()['properties'] ?? [];
            $required = $ability->get_input_schema()['required'] ?? [];

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
                                    $data['description'],
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

                $r = $ability->execute($args);

                if (is_wp_error($r)) {
                    return $r->get_error_message();
                }

                if (is_array($r)) {
                    if (count($r) === 1) {
                        return array_shift($r);
                    }
                    return wp_json_encode($r);
                    $b = '';
                    foreach ($r as $k => $v) {
                        $b .= $k . ': ' . $v . "\n";
                    }

                    return $b;
                    //return wp_json_encode($r);
                }
                return $r;
            });

            $tools[] = $tool;
        }

        return $tools;
    }

    protected function chatHistory(): ChatHistoryInterface {
        return new FileChatHistory(
                directory: WP_CONTENT_DIR . '/cache/assistant',
                key: 'chat',
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

        error_log(
                '[' .
                strtoupper($level) .
                '] ' .
                $message . ' ' . 
                wp_json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );
    }
}
