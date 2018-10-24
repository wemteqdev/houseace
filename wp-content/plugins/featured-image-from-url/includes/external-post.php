<?php

add_filter('wp_insert_post_data', 'fifu_remove_first_image_ext', 10, 2);

function fifu_remove_first_image_ext($data, $postarr) {
    if (isset($_POST['fifu_input_url']))
        return $data;

    $content = $postarr['post_content'];
    if (!$content)
        return $data;

    $contentClean = fifu_show_all_images($content);
    $data = str_replace($content, $contentClean, $data);

    $img = fifu_first_img_in_content($contentClean);
    if (!$img)
        return $data;

    if (get_option('fifu_pop_first') == 'toggleoff')
        return str_replace($img, fifu_show_image($img), $data);

    return str_replace($img, fifu_hide_image($img), $data);
}

add_action('save_post', 'fifu_save_properties_ext');

function fifu_save_properties_ext($post_id) {
    if (isset($_POST['fifu_input_url']))
        return;

    $url = fifu_first_url_in_content($post_id);

    if ($url && get_option('fifu_get_first') == 'toggleon') {
        update_post_meta($post_id, 'fifu_image_url', fifu_convert($url));
        fifu_update_fake_attach_id($post_id);
    }
}

function fifu_first_img_in_content($content) {
    $matches = array();
    preg_match_all('/<img[^>]*>/', $content, $matches);
    return $matches && $matches[0] ? $matches[0][0] : null;
}

function fifu_show_all_images($content) {
    $matches = array();
    preg_match_all('/<img[^>]*display:[ ]*none[^>]*>/', $content, $matches);
    foreach ($matches[0] as $img) {
        $content = str_replace($img, fifu_show_image($img), $content);
    }
    return $content;
}

function fifu_hide_image($img) {
    if (strpos($img, 'style=\"') !== false)
        return preg_replace('/style=..[^"]*["]/', 'style=\"display:none\"', $img);
    return str_replace('/>', 'style=\"display:none\"/>', $img);
}

function fifu_show_image($img) {
    return preg_replace('/style=..display:[ ]*none../', '', $img);
}

function fifu_first_url_in_content($post_id) {
    $content = get_post_field('post_content', $post_id);
    if (!$content)
        return;
    $matches = array();
    preg_match_all('/<img[^>]*>/', $content, $matches);
    if (!$matches[0])
        return;

    //double quotes
    $aux1 = explode('src="', $matches[0][0]);
    if ($aux1) {
        $aux2 = explode('"', $aux1[1]);
    }

    //single quotes
    if (!$aux2[0]) {
        $aux1 = explode("src='", $matches[0][0]);
        if ($aux1)
            $aux2 = explode("'", $aux1[1]);
    }

    return $matches && $matches[0] ? $aux2[0] : null;
}

function fifu_update_fake_attach_id($post_id) {
    if (get_option('fifu_fake_attach_id') && !get_post_thumbnail_id($post_id))
        set_post_thumbnail($post_id, get_option('fifu_fake_attach_id'));
}

