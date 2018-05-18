<?php
// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );
// Html Templates
add_action( 'wp_footer', 'buddykit_html_templates' );

// We need to register REST API End Point
add_action( 'rest_api_init', function () {

    // New upload
    register_rest_route( 'buddykit/v1', '/upload', array(
        'methods' => 'POST',
        'callback' => 'buddykit_activity_route_endpoint',
    ) );

    register_rest_route( 'buddykit/v1', '/activity-new', array(
        'methods' => 'GET',
        'callback' => 'buddykit_activity_new_endpoint',
    ) );

    // Temporary media list.
    register_rest_route( 'buddykit/v1', '/user-temporary-media', array(
        'methods' => 'GET',
        'callback' => 'buddykit_user_temporary_media_endpoint',
    ) );

    // Delete media
    register_rest_route( 'buddykit/v1', '/user-temporary-media-delete/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'buddykit_user_temporary_media_delete_endpoint'
    ) );

    // Flush temporary media
     register_rest_route( 'buddykit/v1', '/user-temporary-flush/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'buddykit_user_temporary_media_flush_all_endpoint',
        'args' => array(
                'id' => array(
                    'validate_callback' => '__buddykit_user_temporary_media_flush_all_endpoint_validate_id'
                ),
            ),
    ) );
});

add_filter('bp_activity_allowed_tags', '__buddykit_update_activity_kses_filter', 10);

function __buddykit_update_activity_kses_filter()
{
    $bp_allowed_tags = bp_get_allowedtags();
   
    $bp_allowed_tags['a']['data-fancybox'] = array();

    $bp_allowed_tags['ul'] = array(
                        'class' => array()
                     );
    $bp_allowed_tags['li'] = array(
                        'class' => array()
                     );
    return $bp_allowed_tags;
}

function buddykit_activity_new_endpoint() {

    global $wpdb;

    $user_id = 1;

    if ( function_exists('bp_activity_add'))
    {

        $flushed = buddykit_flush_user_tmp_files($user_id);

        if ( $flushed ) {

            $stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files
                WHERE user_id = %d AND is_tmp = %d",
                $user_id, 1
            );

            $results = $wpdb->get_results($stmt, OBJECT);

            $media_html = '';
          
            if ( ! empty($results)) {
                
                $media_html .= '<ul class="buddykit-activity-media-gallery items-'.absint(count($results)).'">';
                    foreach( $results as $result ) {
                        $url = BuddyKitFileAttachment::get_user_uploads_url($result->user_id) . $result->name;
                        $media_html .= '<li class="buddykit-activity-media-gallery-item">';
                            $media_html .= '<a data-fancybox="gallery" title="'.esc_attr($result->name).'" href="'.esc_url($url).'">';
                                $media_html .= '<img src="'.esc_url($url).'" alt="'.esc_attr($result->name).'" />';
                            $media_html .= '</a>';
                        $media_html .= '</li>';
                    }
                $media_html .= '</ul>';
            }
            $user_link = bp_core_get_userlink($user_id);
            $action = sprintf(__('%s uploaded %d new media', 'buddykit'), $user_link, absint( count( $results ) ));
            $args = array(
                'action' => $action,
                'content' => apply_filters('buddykit_media_activity_html', $media_html, $results),
                'component' => 'members',
                'type' => 'activity_update',
                'user_id' =>  $user_id,
            );

            $activity_id = bp_activity_add( $args );

            if ( $activity_id >= 1) {
                // Update the record
                $updated = $wpdb->update(
                    $wpdb->prefix . 'buddykit_user_files',
                    array('is_tmp' => '0'), 
                    array('user_id' => $user_id),
                    array('%d'),
                    array('%d')
                );
            }
        } else {
            return false;
        }
    }

    return false;
}

function __buddykit_user_temporary_media_flush_all_endpoint_validate_id($id)
{
    return get_current_user_id() === (int) $id;
}

function buddykit_user_temporary_media_flush_all_endpoint(WP_REST_Request $request) {

    $request = $request->get_params();
    $user_id = (int)$request['id'];

    if ( $user_id !== get_current_user_id() ) {
        return false;
    }

    $flushed = buddykit_flush_user_tmp_files($user_id);

    if ( $flushed ) {
        return new WP_REST_Response(
           array(
               'message' => 'DELETE_OK'
           )
       );
    }

    return false;
}

function buddykit_flush_user_tmp_files($user_id) {

    global $wpdb;

    if ( empty( $user_id ) ) { return false; }

    if ( ! class_exists('BuddyKitFileAttachment') )  {
        require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
    }

    $fs = new BuddyKitFileAttachment();

    $flushed = $fs->flush_dir($user_id);

    if ( $flushed ) { 
        return true;  
    }
    
    return false;
}

function buddykit_user_temporary_media_delete_endpoint(WP_REST_Request $request) {

    global $wpdb;

    $params = $request->get_params();
    $file_id = 0;
    if ( !empty ($params['id'])) {
        $file_id = $params['id'];
    }
    $stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files WHERE id = %d;", absint($file_id));
    $file = $wpdb->get_row( $stmt, OBJECT );

    //Delete from record
    $deleted = $wpdb->delete(
        $wpdb->prefix.'buddykit_user_files',
        array( 'id' => absint($file_id) ),
        array( '%d' )
    );

    if ( $deleted ) {
        // Delete the file in the tmp
        if ( ! class_exists('BuddyKitFileAttachment') ) {
            require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
        }
        $fs = new BuddyKitFileAttachment();

        if ( ! $fs->delete_file($file->name) ) {
            return false;
        }

    } else {
        echo 'There was an error';
    }
    return new WP_REST_Response(
        array(
            'file' => $file_id
        )
    );
}

function buddykit_user_temporary_media_endpoint() {

    $final = array();

    $user_temporary_files = buddykit_get_user_uploaded_files();

    if ( ! empty( $user_temporary_files ) ) {
        foreach( $user_temporary_files as $file ) {
            $final[] = array(
                    'name' => $file->name,
                    'public_url' => $file->url,
                    'user_id' => $file->user_id,
                    'ID' => $file->id,
                    'type' => $file->type
                );
        }
    }
    return $final;
}

function buddykit_get_user_uploaded_files($tmp=false) {

    global $wpdb;

    $stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files
        WHERE user_id = %d AND is_tmp = 1;", get_current_user_id());

    $result =  $wpdb->get_results( $stmt );

    return $result;
}

/**
 * Handle multiple file uploads
 * @return object instance of WP_REST_Response
 */
function buddykit_activity_route_endpoint() {

    global $wpdb;

    $http_response = array();

    if ( ! class_exists('BuddyKitFileAttachment') ) {
        require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
    }

    $fs = new BuddyKitFileAttachment();

    $result = $fs->process_http_file();

    $http_response['image'] = $result;
    $http_response['status'] = 201;
    $http_response['file_id'] = 0;

    $inserted = $wpdb->insert(
            $wpdb->prefix . 'buddykit_user_files',
            array(
                'user_id' => get_current_user_id(),
                'name' => basename( $result['file'] ),
                'type' => $result['type'],
                'is_tmp' => 1,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%d',
            )
        );

    if ( $inserted ) {
        $http_response['status'] = 200;
        $http_response['file_id'] = absint($wpdb->insert_id);
    }

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
    wp_enqueue_style( 'fancy-box-style', BUDDYKIT_PUBLIC_URI . 'css/vendor/fancybox/jquery.fancybox.min.css', false );
    wp_enqueue_script( 'fancy-box-js', BUDDYKIT_PUBLIC_URI . 'js/vendor/fancybox/fancybox.js',  array('jquery'), false );

    if ( is_user_logged_in() ) {
        wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', 
            array('plupload-html5', 'backbone', 'underscore'), false );
        wp_localize_script( 'buddykit-src', '__buddyKit', buddykit_config() );
    }

    return;
}


/**
 * Testing purposes
 */

function buddykit_html_templates() {
    ?>
    <?php if ( is_user_logged_in() ) {?>
        <script type="text/template" id="buddykit-file-uploader">
            <div id="container">
                 <a id="browse" href="#" class="button button-primary" style="position: relative; z-index: 1;">
                    <?php esc_html_e('Attach Photo/Video', 'buddykit'); ?>
                </a>
            </div>
            <div id="buddykit-filelist-wrap">
                <ul id="buddykit-filelist"></ul>
                <div id="buddykit-flush-tmp-files-wrap">
                    <a href="#" style="display: none;" id="buddykit-flush-temporary-files-btn" title="<?php esc_attr_e('Clear All Files','buddykit'); ?>" class="button button-danger">
                        <?php esc_html_e('Clear Files','buddykit'); ?>
                    </a>
                </div>
            </div>
        </script>

        <script type="text/template" id="buddykit-file-list-template">
            <li class="buddykit-filelist-item">
                <img width="150" src="<%= public_url %>" alt="<%= name %>">
                <a data-file-id="<%= ID %>" data-model-id="<%= this.id %>" title="<%= name %>" class="buddykit-filelist-item-delete" href="#"> × </a>
            </li>
        </script>
    <?php
    }
    return;
}
