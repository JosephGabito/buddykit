<?php
// We need to register REST API End Point
add_action( 'rest_api_init', function () {
    register_rest_route( 'buddykit/v1', '/activity-new', array(
        'methods' => 'POST',
        'callback' => 'buddykit_activity_route_endpoint',
    ) );
} );

// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );

/**
 * Handle multiple file uploads
 * @return object instance of WP_REST_Response
 */
function buddykit_activity_route_endpoint() {

    // Bail out if files is empty
    if (empty($_FILES) || $_FILES["file"]["error"]) {
        $response = new WP_REST_Response(
            array('message' => 'Error: File is empty')
        );
    }
    // Include WordPress' file that declares 'wp_handle_upload'
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    $uploadedfile = $_FILES['file'];
    
    $upload_overrides = array( 'test_form' => false );

    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

    if ( $movefile && ! isset( $movefile['error'] ) ) {
        
        $args = array(
            'action' => 'Test uploaded a new media',
            'content' => '<img src="'.$movefile['url'].'" />',
            'component' => 'activity',
            'type' => 'buddykit-media',
            'primary_link' => false,
            'user_id' => 1,
        );

        //Create new activity
        $activity_id = bp_activity_add( $args );

        $response = new WP_REST_Response(
            array('message' => 'File successfully uploaded')
        );

    } else {
        $response = new WP_REST_Response(
            array(
                'message' => $movefile['error'] 
                )
        );
    }

    return $response;

}

function buddykit_register_scripts() {
    
    wp_enqueue_style( 'buddykit-style', BUDDYKIT_PUBLIC_URI . 'css/buddykit.css', false );

    wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', array('plupload-html5'), false );

    return;
}