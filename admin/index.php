<?php
defined('ABSPATH') || exit;
$subpage = $_GET['subpage'] ?? '';

//switch ($subpage) {
//    case 'logs':
//        include __DIR__ . '/logs.php';
//        return;
//    case 'view':
//        include __DIR__ . '/view.php';
//        return;
//}


$categories = wp_get_ability_categories();
$abilities = wp_get_abilities();
?>
<style>

    .categories {
        display: flex;
    }
    .category {
        xxxdisplay: inline-block;
        padding: 1rem;
        background-color: #fff;
        width: 15rem;
        margin-right: 1rem;
        margin-bottom: 1rem;
        text-decoration: none;
    }

    .category h3 {
        padding: 0;
        margin: 0;
        margin-bottom: 1rem;
    }

</style>
<div class="wrap">
    <h2>Welcome,</h2>
    <p>here there are different set of abilities I can use to help in your daily work.</p>

<!--    <p>
        <a href="?page=monitor-abilities">List</a> | <a href="?page=monitor-abilities&subpage=logs">Logs</a>
    </p>-->

    <div class="categories">
        <?php foreach ($categories as $category) { ?>

            <?php
            $count = 0;
            foreach ($abilities as $ability) {
                if ($ability->get_category() === $category->get_slug()) {
                    $count++;
                }
            }
            ?>

            <a href="?page=assistant-chat&category=<?php echo rawurlencode($category->get_slug()); ?>" class="category">
                <h3><?php echo $category->get_label(); ?></h3>
                <small><?= $count ?> abilities</small>
                <p><?php echo $category->get_description(); ?></p>

            </a>


        <?php } ?>

    </div>


</div>