<?php
// We need to register REST API End Point
add_action( 'rest_api_init', function () {
    register_rest_route( 'buddykit/v1', '/author/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
    ) );
} );

// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );

function my_awesome_func() {
    return new WP_REST_Response(array(
            'message' => 'this is a test'
        ));
}

function buddykit_register_scripts() {
    
    wp_enqueue_style( 'buddykit-style', BUDDYKIT_ASSET_URI . 'buddykit.css', array('jquery'), false );

    wp_enqueue_script( 'buddykit-filepond', BUDDYKIT_ASSET_URI . 'filepond.min.js', array('jquery'), false );
   
    wp_enqueue_script( 'buddykit-filepond-jquery-adapter', 
        BUDDYKIT_ASSET_URI . 'filepond.jquery.js', array('buddykit-filepond'), false );

    wp_enqueue_script( 'buddykit-src', BUDDYKIT_ASSET_URI . 'buddykit.js', 
        array(
            'buddykit-filepond',
            'buddykit-filepond-jquery-adapter'
        ), false 
    );

    return;
}