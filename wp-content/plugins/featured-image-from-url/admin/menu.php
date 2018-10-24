<?php

add_action('admin_menu', 'fifu_insert_menu');

function fifu_insert_menu() {
    add_menu_page(
            'Featured Image From URL', 'Featured Image From URL', 'administrator', 'featured-image-from-url', 'fifu_get_menu_html', plugins_url() . '/featured-image-from-url/admin/images/favicon.png'
    );

    add_action('admin_init', 'fifu_get_menu_settings');
}

function fifu_get_menu_html() {
    $image_button = plugins_url() . '/featured-image-from-url/admin/images/onoff.jpg';

    $enable_social = get_option('fifu_social');
    $enable_lazy = get_option('fifu_lazy');
    $enable_content = get_option('fifu_content');
    $enable_fake = get_option('fifu_fake');
    $css_style = get_option('fifu_css');
    $default_url = get_option('fifu_default_url');
    $enable_wc_lbox = get_option('fifu_wc_lbox');
    $enable_wc_zoom = get_option('fifu_wc_zoom');
    $enable_hide_page = get_option('fifu_hide_page');
    $enable_hide_post = get_option('fifu_hide_post');
    $enable_get_first = get_option('fifu_get_first');
    $enable_pop_first = get_option('fifu_pop_first');
    $enable_ovw_first = get_option('fifu_ovw_first');
    $column_height = get_option('fifu_column_height');
    $enable_priority = get_option('fifu_priority');

    $array_cpt = array();
    for ($x = 0; $x <= 4; $x++)
        $array_cpt[$x] = get_option('fifu_cpt' . $x);

    include 'html/menu.html';

    fifu_update_menu_options();

    if (get_option('fifu_fake') == 'toggleon')
        fifu_enable_fake();
    else
        fifu_disable_fake();
}

function fifu_get_menu_settings() {
    fifu_get_setting('fifu_social');
    fifu_get_setting('fifu_lazy');
    fifu_get_setting('fifu_content');
    fifu_get_setting('fifu_fake');
    fifu_get_setting('fifu_css');
    fifu_get_setting('fifu_default_url');
    fifu_get_setting('fifu_wc_lbox');
    fifu_get_setting('fifu_wc_zoom');
    fifu_get_setting('fifu_hide_page');
    fifu_get_setting('fifu_hide_post');
    fifu_get_setting('fifu_get_first');
    fifu_get_setting('fifu_pop_first');
    fifu_get_setting('fifu_ovw_first');
    fifu_get_setting('fifu_column_height');
    fifu_get_setting('fifu_priority');

    for ($x = 0; $x <= 4; $x++)
        fifu_get_setting('fifu_cpt' . $x);
}

function fifu_get_setting($type) {
    register_setting('settings-group', $type);

    if (!get_option($type)) {
        if (strpos($type, "cpt") !== false || strpos($type, "default") !== false || strpos($type, "css") !== false)
            update_option($type, '');
        else if (strpos($type, "fifu_column_height") !== false)
            update_option($type, "64");
        else if (strpos($type, "wc") !== false)
            update_option($type, 'toggleon');
        else
            update_option($type, 'toggleoff');
    }
}

function fifu_update_menu_options() {
    fifu_update_option('fifu_input_social', 'fifu_social');
    fifu_update_option('fifu_input_lazy', 'fifu_lazy');
    fifu_update_option('fifu_input_content', 'fifu_content');
    fifu_update_option('fifu_input_fake', 'fifu_fake');
    fifu_update_option('fifu_input_css', 'fifu_css');
    fifu_update_option('fifu_input_default_url', 'fifu_default_url');
    fifu_update_option('fifu_input_wc_lbox', 'fifu_wc_lbox');
    fifu_update_option('fifu_input_wc_zoom', 'fifu_wc_zoom');
    fifu_update_option('fifu_input_hide_page', 'fifu_hide_page');
    fifu_update_option('fifu_input_hide_post', 'fifu_hide_post');
    fifu_update_option('fifu_input_get_first', 'fifu_get_first');
    fifu_update_option('fifu_input_pop_first', 'fifu_pop_first');
    fifu_update_option('fifu_input_ovw_first', 'fifu_ovw_first');
    fifu_update_option('fifu_input_column_height', 'fifu_column_height');
    fifu_update_option('fifu_input_priority', 'fifu_priority');

    for ($x = 0; $x <= 4; $x++)
        fifu_update_option('fifu_input_cpt' . $x, 'fifu_cpt' . $x);
}

function fifu_update_option($input, $type) {
    if (isset($_POST[$input])) {
        if ($_POST[$input] == 'on')
            update_option($type, 'toggleon');
        else if ($_POST[$input] == 'off')
            update_option($type, 'toggleoff');
        else
            update_option($type, wp_strip_all_tags($_POST[$input]));
    }
}

function fifu_enable_fake() {
    if (get_option('fifu_fake_attach_id'))
        return;

    global $wpdb;
    $old_attach_id = get_option('fifu_fake_attach_id');

    // create attachment 
    $filename = 'Featured Image from URL';
    $parent_post_id = null;
    $filetype = wp_check_filetype('fifu.png', null);
    $attachment = array(
        'guid' => basename($filename),
        'post_mime_type' => $filetype['type'],
        'post_title' => '',
        'post_excerpt' => '',
        'post_content' => 'Please don\'t remove that. It\'s just an empty symbolic file that keeps the field filled ' .
        '(some themes/plugins depend on having an attached file to work). But you are free to use any image you want instead of this file.',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $filename, $parent_post_id);
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
    wp_update_attachment_metadata($attach_id, $attach_data);
    update_option('fifu_fake_attach_id', $attach_id);

    // insert _thumbnail_id
    $table = $wpdb->prefix . 'postmeta';
    $query = "
        SELECT DISTINCT post_id
        FROM " . $table . " a
        WHERE a.post_id in (
            SELECT post_id 
            FROM " . $table . " b 
            WHERE b.meta_key IN ('fifu_image_url', 'fifu_video_url', 'fifu_slider_image_url_0', 'fifu_shortcode')
            AND b.meta_value IS NOT NULL 
            AND b.meta_value <> ''
        )
        AND NOT EXISTS (
            SELECT 1 
            FROM " . $table . " c 
            WHERE a.post_id = c.post_id 
            AND c.meta_key = '_thumbnail_id'
        )";
    $result = $wpdb->get_results($query);
    foreach ($result as $i) {
        $data = array('post_id' => $i->post_id, 'meta_key' => '_thumbnail_id', 'meta_value' => $attach_id);
        $wpdb->insert($table, $data);
    }

    // update _thumbnail_id
    $data = array('meta_value' => $attach_id);
    $where = array('meta_key' => '_thumbnail_id', 'meta_value' => $old_attach_id);
    $wpdb->update($table, $data, $where, null, null);

    // update _thumbnail_id
    $query = "
        SELECT post_id 
        FROM " . $table . " a
        WHERE a.meta_key IN ('fifu_image_url', 'fifu_video_url', 'fifu_slider_image_url_0', 'fifu_shortcode')
        AND a.meta_value IS NOT NULL 
        AND a.meta_value <> ''";
    $result = $wpdb->get_results($query);
    foreach ($result as $i) {
        $data = array('meta_value' => $attach_id);
        $where = array('post_id' => $i->post_id, 'meta_key' => '_thumbnail_id', 'meta_value' => -1);
        $wpdb->update($table, $data, $where, null, null);
    }
}

function fifu_disable_fake() {
    global $wpdb;
    $table = $wpdb->prefix . 'postmeta';
    $where = array('meta_key' => '_thumbnail_id', 'meta_value' => get_option('fifu_fake_attach_id'));
    $wpdb->delete($table, $where);

    wp_delete_attachment(get_option('fifu_fake_attach_id'));
    delete_option('fifu_fake_attach_id');
}

