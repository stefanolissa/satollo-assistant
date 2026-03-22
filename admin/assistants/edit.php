<?php
defined('ABSPATH') || exit;

/** @var wpdb $wpdb */
global $wpdb;

$assistant = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}assistants where id=%d limit 1", (int)$_GET['id']), ARRAY_A);
if (!$assistant) {
    die('Invalid ID');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('assistant-action');

    if (isset($_POST['save'])) {
        $data = wp_unslash($_POST['data']);

        $row['name'] = $data['name'];
        $row['description'] = $data['description'];
        $row['categories'] = implode(',', $data['categories'] ?? []);

        $wpdb->update($wpdb->prefix, $row, ['id' => $assistant['id']]);
    }
} else {
    $data = $assistant;
    $data['categories'] = wp_parse_list($assistant['categories'] ?? []);
}

$categories = wp_get_ability_categories();
?>

<table class="form-table">

    <tbody>

        <tr>
            <th>
                Name
            </th>
            <td>
                <input type="text" name="data[name]" size="40" value="<?= esc_attr($data['name'] ?? ''); ?>" placeholder="">
                <p class="description"></p>
            </td>

        </tr>
        <tr>
            <th>
                Description
            </th>
            <td>
                <textarea name="data[description]" cols="40" placeholder=""><?php echo esc_html($data['description']); ?></textarea>
                <p class="description">
                </p>
            </td>
        </tr>
    </tbody>
</table>

<h3>Expose those abilitiy categories</h3>
<p>Warning</p>

<?php foreach ($categories as $category) { ?>
    <label>
        <input type="checkbox" name="data[categories]" value="<?php echo esc_attr($category->get_slug()) ?>" <?php echo in_array($category->get_slug(), $data['categories']) ? 'checked' : ''; ?>>
        <?php echo esc_html($category->get_label()) ?>
    </label>
<?php } ?>

<p><button name="save" class="button button-primary">Save</button></p>
