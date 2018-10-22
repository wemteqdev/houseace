<?php
	require_once( dirname( __FILE__ ) . '/wp-load.php' );

    // admin password is v0aRojXe*Q%)e)7KV5p%2xqX
    // install adminstrator passwords plugin
    // install advanced custom fields plugin
    // install custom post type ui - set rest api true, rest api slug to 'listings'

    $headers = array (
        'Authorization' => 'Basic ' . base64_encode( 'admin' . ':' . '1vdR 4lVM ccLX 00zn suaK kVNr'  ),
    );

    $domain_id = 111;

    $filters = array(
        'filter[meta_key]'=>'id',
        'filter[meta_value]'=> $domain_id
    );

    $filter_string = http_build_query($filters);
    $url = rest_url( 'wp/v2/listings?' . $filter_string  );
    $response = wp_remote_get( $url, array (
        'headers' => $headers,
    ));


    $listing = json_decode($response['body']);

    print_r(count($listing));

    exit;


    $id = 42;
    $url = rest_url( 'wp/v2/listings/' . $id  );

    print($url . '\n');

    $data = array(
        'title' => 'test test',
        'fields[channel]' => 'xxxx'
    );

    $response = wp_remote_post( $url, array (
        'method'  => 'POST',
        'headers' => $headers,
        'body'    =>  $data
    ));


    print_r($response);

