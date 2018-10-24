<?php
	require_once( dirname( __FILE__ ) . '/wp-load.php' );

    // admin password is v0aRojXe*Q%)e)7KV5p%2xqX
    // install adminstrator passwords plugin
    // install advanced custom fields plugin
    // install custom post type ui - set rest api true, rest api slug to 'listings'

    $headers = array (
        'Authorization' => 'Basic ' . base64_encode( 'admin' . ':' . 'q929 Cs8K l49A boxt rTue Mc9t'  ),
    );

    // $domain_id = 1;

    // $filters = array(
    //     'filter[meta_key]'=>'id',
    //     'filter[meta_value]'=> $domain_id
    // );

    // $filter_string = http_build_query($filters);
    // $url = rest_url( 'wp/v2/listings?' . $filter_string  );
    // $response = wp_remote_get( $url, array (
    //     'headers' => $headers,
    // ));


    // $listing = json_decode($response['body']);

    // print_r(count($listing));

    // exit;


    $post_id = 72;

    $data = array(
        'title' => 'test',
        'field[bedrooms]' => 111
    );

    $url = 'http://houseace.loc:8888/wp-json/wq/v2/listings/' . $post_id;
    $url = 'http://houseace.loc:8888/wp-json/wq/v2/listings/';

    $url = 'http://houseace.loc:8888/wp-json/wq/v2/listings/' . $post_id;

    $response = wp_remote_post( $url, array (
        'method'  => 'POST',
        'timeout' => 45,
        'headers' => $headers,
        'body'    =>  $data
    ));


    echo($response['body']);