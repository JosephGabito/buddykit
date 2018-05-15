<?php
// We need to register REST API End Point
add_action( 'rest_api_init', function () {
    register_rest_route( 'buddykit/v1', '/author/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'my_awesome_func',
    ) );
} );

// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );

function my_awesome_func() {

    if (empty($_FILES) || $_FILES["file"]["error"]) {
        die('{"OK": 0}');
    }

    $fileName = $_FILES["file"]["name"];
    
    move_uploaded_file($_FILES["file"]["tmp_name"], "uploads/$fileName");

    die('{"OK": 1}');

    return new WP_REST_Response(array('message' => 'this is a test'));

}

function buddykit_register_scripts() {
    
    wp_enqueue_style( 'buddykit-style', BUDDYKIT_PUBLIC_URI . 'css/buddykit.css', false );

    wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', array('plupload-html5'), false );

    return;
}