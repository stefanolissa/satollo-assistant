<?php
defined('ABSPATH') || exit;

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

            <a href="?page=assistant&subpage=chat&category=<?= rawurlencode($category->get_slug()); ?>" class="category">
                <h3><?= esc_html($category->get_label()) ?></h3>
                <small><?= $count ?> abilities</small>
                <p><?= esc_html($category->get_description()) ?></p>
            </a>

        <?php } ?>

    </div>


</div>