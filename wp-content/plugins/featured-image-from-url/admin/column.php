<?php

add_action('admin_init', 'fifu_column');

function fifu_column() {
    add_filter('manage_posts_columns', 'fifu_column_head');
    add_filter('manage_pages_columns', 'fifu_column_head');
    add_filter('manage_edit-product_cat_columns', 'fifu_column_head');
    fifu_column_custom_post_type();
    add_action('manage_posts_custom_column', 'fifu_column_content', 10, 2);
    add_action('manage_pages_custom_column', 'fifu_column_content', 10, 2);
    add_action('manage_product_cat_custom_column', 'fifu_cat_column_content', 10, 3);
}

function fifu_column_head($default) {
    $default['featured_image'] = 'FIFU';
    return $default;
}

function fifu_cat_column_content($internal_image, $column, $term_id) {
    if ($column == 'featured_image') {
        $url = get_term_meta($term_id, 'fifu_image_url', true);
        if ($url != '')
            echo sprintf('<img src="%s" height="%s"/>', $url, get_option('fifu_column_height'));
    } else
        echo $internal_image;
}

function fifu_column_content($column, $post_id) {
    if ($column == 'featured_image') {
        $url = fifu_main_image_url($post_id);
        if ($url == '')
            $url = wp_get_attachment_url(get_post_thumbnail_id());
        echo sprintf('<img src="%s" height="%s"/>', $url, get_option('fifu_column_height'));
    }
}

function fifu_column_custom_post_type() {
    for ($i = 0; $i < 5; $i++)
        add_filter('manage_edit-' . get_option('fifu_cpt' . $i) . '_columns', 'fifu_column_head');
}

