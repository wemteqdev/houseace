<?php

add_filter('woocommerce_before_main_content', 'fifu_cat_show_image', 30);

function fifu_cat_show_image() {
    $url = fifu_cat_get_url();
    $alt = fifu_cat_get_alt();

    if ($url)
        echo fifu_get_html($url, $alt);
}

add_filter('wp_head', 'fifu_cat_add_social_tags');

function fifu_cat_add_social_tags() {
    $url = fifu_cat_get_url();
    $title = single_cat_title('', false);

    $term_id = fifu_cat_get_term_id();
    if ($term_id)
        $description = wp_strip_all_tags(category_description($term_id));

    if ($url && get_option('fifu_social') == 'toggleon')
        include 'html/social.html';
}

function fifu_cat_get_url() {
    $term_id = fifu_cat_get_term_id();
    return get_term_meta($term_id, 'fifu_image_url', true);
}

function fifu_cat_get_alt() {
    $term_id = fifu_cat_get_term_id();
    return get_term_meta($term_id, 'fifu_image_alt', true);
}

function fifu_cat_get_term_id() {
    global $wp_query;
    return $wp_query->get_queried_object_id();
}

