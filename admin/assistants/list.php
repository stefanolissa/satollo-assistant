<?php
defined('ABSPATH') || exit;

/** @var wpdb $wpdb */
global $wpdb;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('assistant-action');

    if (isset($_POST['add'])) {
        $wpdb->insert($wpdb->prefix, ['name' => 'New assistant']);
        echo '<script>location.href="?page=assistant&subpage=edit&id=' . ((int)$wpdb->insert_id) . '";</script>';
        return;
    }
}

class Assistants_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'assistant',
            'plural' => 'assistants',
            'ajax' => false,
        ]);
    }

    public function get_columns() {
        $columns = [
            //'cb' => '<input type="checkbox">',
            'name' => 'Name',
            'description' => 'Description',
            'shortcode' => 'Shortcode',
        ];
        return $columns;
    }

    public function prepare_items() {

        // TODO: Move outside and pass abilities with the constructor
        if (!function_exists('wp_get_abilities')) {
            $this->items = [];
            return;
        }

        $assistants = [];

        $a = new stdClass();
        $a->id = 12;
        $a->name = 'the bot';
        $a->description = 'do coffeee';
        $assistants[] = $a;

        $columns = $this->get_columns();
        $hidden = []; // You can specify columns to hide here.
        $sortable = []; // You can specify sortable columns here.
        $this->_column_headers = [$columns, $hidden, $sortable];

        // This is where you would implement pagination logic.
        $per_page = 20; // Number of items to display per page.
        $current_page = $this->get_pagenum();
        $total_items = count($assistants);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);

        $this->items = array_slice($assistants, (($current_page - 1) * $per_page), $per_page);
    }

    public function column_cb($item) {
        return '<input type="checkbox" name="data[abilities][]" value="' . esc_attr($item->get_name()) . '"'
                . (in_array($item->get_name(), $this->enabled_abilities) ? 'checked' : '')
                . '>';
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                return esc_html($item->name);
            case 'description':
                return esc_html($item->description);
            case 'shortcode':
                return esc_html('[assistant id="' . $item->id . '"]');
            default:
                return '?';
        }
    }
}

$table = new Assistants_List_Table($enabled_abilities);
$table->prepare_items();
?>
<style>

</style>
<div class="wrap">
    <h2>Assistants</h2>

    <form method="post">
        <?php wp_nonce_field('assistant-action'); ?>
        <p><button name="add" class="button button-primary">Add new</button></p>
    </form>

    <form method="post">
        <?php $table->display(); ?>
    </form>


</div>