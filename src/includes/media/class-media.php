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

// Testing purposes
add_action( 'the_content', 'buddykit_append_form' );

/**
 * Testing purposes
 */
function buddykit_append_form( $content ) {
    ob_start();
    ?>
    <?php $files = buddykit_get_user_uploaded_files(); ?>
    
        <ul id="filelist">
            <?php foreach($files as $file) { ?>
                <li>
                    <img width="150" src="http://localhost/thrive/wp-content/uploads/buddykit/1/tmp/<?php echo $file; ?>" />
                    <a href="#"> (&times; Delete) </a>
                </li>
            <?php } ?>
        </ul>
    
    <hr/>
    <div id="container">
        <a id="browse" href="javascript:;">[Browse...]</a>
        <a id="start-upload" href="javascript:;">[Post]</a>
    </div>
    <hr/>
    <pre id="console"></pre>
    <?php
    return $content . ob_get_clean();
}

function buddykit_get_user_uploaded_files() {

    $dir = wp_upload_dir();

    $tmp_dir = $dir['basedir'] . '/buddykit/' . get_current_user_id() . '/tmp/';

    $files = preg_grep('/^([^.])/',  scandir( $tmp_dir, 1 ) );
    
    return array_diff($files, array('.','..'));

}

/**
 * Handle multiple file uploads
 * @return object instance of WP_REST_Response
 */
function buddykit_activity_route_endpoint() {

    
    $http_response = array();

    if ( ! class_exists('BuddyKitFileAttachment') ) {
        require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
    }

    $fs = new BuddyKitFileAttachment();

    // Reads the global $_FILE request.
    // See includes/media/class-file-attachment.php
    print_r($_REQUEST);
    $result = $fs->process_http_file();
    
    $http_response['message'] = 'test';
    $http_response['__debug'] = $result;


    return new WP_REST_Response($http_response);

}

/**
 * Register all needed scripts.
 * @return void
 */
function buddykit_register_scripts() {
    
    wp_enqueue_style( 'buddykit-style', BUDDYKIT_PUBLIC_URI . 'css/buddykit.css', false );

    wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', array('plupload-html5'), false );
    
    wp_localize_script( 'buddykit-src', '__buddyKit', array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' )
    ) );    

    wp_enqueue_script('buddykit-wp-api');

    return;
}

