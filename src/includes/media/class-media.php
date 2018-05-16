<?php
// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );
// Testing purposes
add_action( 'the_content', 'buddykit_append_form' );

// We need to register REST API End Point
add_action( 'rest_api_init', function () {
    register_rest_route( 'buddykit/v1', '/activity-new', array(
        'methods' => 'POST',
        'callback' => 'buddykit_activity_route_endpoint',
    ) );

    register_rest_route( 'buddykit/v1', '/user-temporary-media', array(
        'methods' => 'GET',
        'callback' => 'buddykit_user_temporary_media_endpoint',
    ) );

});

function buddykit_user_temporary_media_endpoint() {
    
    $final = array();
    $user_temporary_files = buddykit_get_user_uploaded_files();

    if ( ! empty( $user_temporary_files ) ) {
        foreach( $user_temporary_files as $file ) {
            $final[] = array(
                    'name' => $file,
                    'public_url' => buddykit_get_user_temporary_files_url($file),
                    'user_id' => get_current_user_id(),
                    'ID' => uniqid(),
                    'type' => 'image'
                );
        }
    }
    return $final;
}

function buddykit_get_user_uploaded_files() {

    $dir   = wp_upload_dir();
    $files = array();

    $tmp_dir = $dir['basedir'] . '/buddykit/' . get_current_user_id() . '/tmp/';
    
    if ( is_dir($tmp_dir) ) {
        $files = preg_grep('/^([^.])/',  scandir( $tmp_dir, 0 ) );
    }
    
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
    $result = $fs->process_http_file();
    
    $http_response['image'] = $result;
    return new WP_REST_Response($http_response);

}

/**
 * Returns the temporary url for the given file
 * @return string The temporary url of file.
 */
function buddykit_get_user_temporary_files_url( $file = '' ) {
    
    $url = buddykit_get_user_upload_dir( $tmp = true );
    
    if ( empty( $file ) ) {
        return false;        
    }

    return $url . $file;
}

/**
 * Fetches the current user's upload directory
 * @param boolean $is_temporary Whether you want to getch the temporary file or not
 * 
 * @return string The current user upload directory.
 */
function buddykit_get_user_upload_dir( $is_temporary = false )
{
    $dir = wp_upload_dir();

    $url = trailingslashit( $dir['baseurl'] ) . sprintf('buddykit/%d/', get_current_user_id());

    if ( $is_temporary ) {
        return trailingslashit( $url . 'tmp' );
    }

    return trailingslashit( $url . 'uploaded' );
}

/**
 * Register all needed scripts.
 * @return void
 */
function buddykit_register_scripts() {
    
    wp_enqueue_style( 'buddykit-style', BUDDYKIT_PUBLIC_URI . 'css/buddykit.css', false );

    wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', array('plupload-html5', 'backbone', 'underscore'), false );

    wp_localize_script( 'buddykit-src', '__buddyKit', array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'rest_upload_uri' => get_rest_url( null, 'buddykit/v1/', 'rest'),
        'file_list_container_id' => 'buddykit-filelist',
        'current_user_id' => get_current_user_id()
    ));

    wp_enqueue_script('buddykit-wp-api');

    return;
}


/**
 * Testing purposes
 */
function buddykit_append_form( $content ) {
    ob_start();
    ?>
    <ul id="buddykit-filelist"></ul>
    
    <hr/>
    <div id="container">
        <a id="browse" href="javascript:;">[Browse...]</a>
        <a id="start-upload" href="javascript:;">[Post]</a>
    </div>
    <hr/>
    
    <script type="text/template" id="buddykit-file-list-template">

        <li class="buddykit-filelist-item">
            <img width="150" src="<%= public_url %>" alt="<%= name %>">
            <a data-model-id="<%= this.id %>" title="<%= name %>" class="buddykit-filelist-item-delete" href="#"> × </a>
        </li>

    </script>

    <?php
    return $content . ob_get_clean();
}

