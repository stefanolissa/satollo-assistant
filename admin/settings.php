<?php
defined('ABSPATH') || exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('bulk-abilities'); // by the wp list table...
    $settings = wp_unslash($_POST['data']);
    update_option('assistant_settings', $settings ?? []);

    // When the provider or the model is changed, the chat history must be deleted
    // (I should create a chat history for each provider+model but I'm lazy and probably
    // is not worth the time)
//    require_once __DIR__ . '/../vendor/autoload.php';
//    require_once __DIR__ . '/agent.php';
//
//    AssistantAgent::make()->resolveChatHistory()->flushAll();
}

$settings = get_option('assistant_settings', []);
$enabled_abilities = $settings['abilities'] ?? [];
$provider = $settings['provider'] ?? 'mistral';
$framework = $settings['framework'] ?? 'neuron';

class Abilities_List_Table extends WP_List_Table {

    var $enabled_abilities;

    public function __construct($enabled_abilities) {
        parent::__construct([
            'singular' => 'ability', // Singular name of the listed records.
            'plural' => 'abilities', // Plural name of the listed records.
            'ajax' => false, // Does this table support ajax?
        ]);

        $this->enabled_abilities = $enabled_abilities;
    }

    /**
     * Defines the columns for our list table.
     *
     * @return array An associative array of column headers.
     */
    public function get_columns() {
        $columns = [
            'cb' => '<input type="checkbox">',
            'name' => 'Name',
            'label' => 'Label',
            'description' => 'Description',
        ];
        return $columns;
    }

    /**
     * Prepares the data for the list table.
     * This is where you would fetch data from a database, file, or API.
     */
    public function prepare_items() {

        // TODO: Move outside and pass abilities with the constructor
        if (!function_exists('wp_get_abilities')) {
            $this->items = [];
            return;
        }

        $abilities = wp_get_abilities();

        // Define columns and sortable columns (if needed).
        $columns = $this->get_columns();
        $hidden = []; // You can specify columns to hide here.
        $sortable = []; // You can specify sortable columns here.
        $this->_column_headers = [$columns, $hidden, $sortable];

        // This is where you would implement pagination logic.
        $per_page = 20; // Number of items to display per page.
        $current_page = $this->get_pagenum();
        $total_items = count($abilities);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);

        // Slice the data for the current page.
        $this->items = array_slice($abilities, (($current_page - 1) * $per_page), $per_page);
    }

    /**
     * @param \WP_Ability $item
     */
    public function column_cb($item) {
        return '<input type="checkbox" name="data[abilities][]" value="' . esc_attr($item->get_name()) . '"'
                . (in_array($item->get_name(), $this->enabled_abilities) ? 'checked' : '')
                . '>';
    }

    /**
     * Handles the display of a single column's data.
     * This is the default handler for all columns without a dedicated method.
     *
     * @param \WP_Ability $item        A single item from the data array.
     * @param string $column_name The name of the current column.
     * @return string The content to display for the column.
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                return esc_html($item->get_name());
            case 'description':
                return esc_html($item->get_description());
            case 'label':
                return esc_html($item->get_label());
            default:
                return '?';
        }
    }
}

$table = new Abilities_List_Table($enabled_abilities);
$table->prepare_items();
?>
<style>
    .key {
        width: 400px;
        font-family: monospace;
    }
</style>
<div class="wrap">
    <h2>Settings</h2>
<!--    <p>
        <a href="?page=monitor-abilities">List</a> | <a href="?page=monitor-abilities&subpage=logs">Logs</a>
    </p>-->

    <form method="post">

        <h3>AI Providers</h3>
        <p>
            The Assistant needs an AI provider. Create an account and get an API key from a provider
            of your choice, please.
        </p>

        <h3>AI framework</h3>

        <p>
            <label>
                <input type="radio" name="data[framework]" value="neuron" <?php echo $framework === 'neuron' ? 'checked' : ''; ?>>
                Neuron AI
            </label>
        </p>

        <p>
            <label>
                <input type="radio" name="data[framework]" value="wp" <?php echo $framework === 'wp' ? 'checked' : ''; ?>>
                WP AI Client
            </label>
        </p>

        <h3>AI provider</h3>
        <p>
            Only for Neuron AI.
        </p>
        <table class="widefat">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Model</th>
                    <th>API Key</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="radio" name="data[provider]" value="mistral" <?php echo $provider === 'mistral' ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        Mistral AI
                    </td>
                    <td>
                        <input type="text" name="data[mistral_model]" class="model" value="<?php echo esc_attr($settings['mistral_model'] ?? ''); ?>" placeholder="mistral-medium-2508">
                    </td>
                    <td>
                        <input type="text" name="data[mistral_key]" class="key" value="<?php echo esc_attr($settings['mistral_key'] ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="radio" name="data[provider]" value="openai" <?php echo $provider === 'openai' ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        Open AI
                    </td>
                    <td>
                        <input type="text" name="data[openai_model]" class="model" value="<?php echo esc_attr($settings['openai_model'] ?? ''); ?>" placeholder="gpt-5-nano">
                    </td>
                    <td>
                        <input type="text" name="data[openai_key]" class="key" value="<?php echo esc_attr($settings['openai_key'] ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="radio" name="data[provider]" value="anthropic" <?php echo $provider === 'anthropic' ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        Anthropic - Claude
                    </td>
                    <td>
                        <input type="text" name="data[anthropic_model]" class="model" value="<?php echo esc_attr($settings['anthropic_model'] ?? ''); ?>" placeholder="claude-3-haiku-20240307">
                    </td>
                    <td>
                        <input type="text" name="data[anthropic_key]" class="key" value="<?php echo esc_attr($settings['anthropic_key'] ?? ''); ?>">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="radio" name="data[provider]" value="gemini" <?php echo $provider === 'gemini' ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        Google - Gemini
                    </td>
                    <td>
                        <input type="text" name="data[gemini_model]" class="model" value="<?php echo esc_attr($settings['gemini_model'] ?? ''); ?>" placeholder="gemini-2.5-flash">
                        <p class="description"><a href="https://ai.google.dev/gemini-api/docs/models" target="_blank">Model list</a></p>
                    </td>
                    <td>
                        <input type="text" name="data[gemini_key]" class="key" value="<?php echo esc_attr($settings['gemini_key'] ?? ''); ?>">
                    </td>
                </tr>
            </tbody>
        </table>

        <p><button name="save" class="button button-primary">Save</button></p>

        <h3>Abilities</h3>
        <p>
            Listed below the "abilities" your site makes available to the Assistant to get information or
            execute tasks. This is something new, expect themes and plugins to add more and more abilities.
        </p>
        <p>
            Select the abilities you want make usable by the Assistant.
        </p>

        <p>This configuration does not work, by now.</p>

        <?php $table->display(); ?>
        <p><button name="save" class="button button-primary">Save</button></p>

    </form>

    <h3>Debug</h3>
    <p>
        That helps me when supporting you...
    </p>
    <pre><?php echo esc_html(print_r(get_option('assistant_settings'), true)); ?></pre>
</div>