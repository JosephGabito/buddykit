<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue the script needed
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );
// Html Templates
add_action( 'wp_footer', 'buddykit_html_templates' );
// Append media to activity
add_filter('bp_activity_new_update_content', '__buddykit_activity_append_media_content');

add_filter('groups_activity_new_update_content', '__buddykit_activity_append_media_content');
// We need to register REST API End Point
add_filter('bp_activity_allowed_tags', '__buddykit_update_activity_kses_filter', 10);

add_action( 'rest_api_init', function () {

    // New upload
    register_rest_route( 'buddykit/v1', '/upload', array(
        'methods' => 'POST',
        'callback' => 'buddykit_activity_route_endpoint',
    ) );

    // Temporary media list.
    register_rest_route( 'buddykit/v1', '/user-temporary-media', array(
        'methods' => 'GET',
        'callback' => 'buddykit_user_temporary_media_endpoint',
    ) );

    // Delete media
    register_rest_route( 'buddykit/v1', '/user-temporary-media-delete/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'buddykit_user_temporary_media_delete_endpoint',
        'args' => array(
                'id' => array(
                    'validate_callback' => '__is_verified_owner_validate'
                ),
            ),
    ) );

    // Flush temporary media
     register_rest_route( 'buddykit/v1', '/user-temporary-flush/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'buddykit_user_temporary_media_flush_all_endpoint',
        'args' => array(
                'id' => array(
                    'validate_callback' => '__does_user_owns_the_file_validate'
                ),
            ),
    ) );
});



function __is_verified_owner_validate($file_id) {
    
    global $wpdb;
    
    $user_id = get_current_user_id();
    
    $stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files 
        WHERE id = %d AND user_id = %d", $file_id, $user_id);

    $result = $wpdb->get_row( $stmt, OBJECT );
    
    if ( !empty($result) and is_object($result)) 
    {
        return true;
    }
    
    return false;
}

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


function __buddykit_activity_append_media_content( $activity_content )
{
    $activity_content .= str_replace('http://','//',buddykit_activity_append_media_content());
    return $activity_content;
}

function buddykit_activity_append_media_content () {

    global $wpdb;
    
    $media_html = '';

    $user_id = get_current_user_id();

    $stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files
            WHERE user_id = %d AND is_tmp = %d",
            $user_id, 1
        );

    $results = $wpdb->get_results($stmt, OBJECT);

    // Check if there are temporary files
    if ( ! empty ( $results ) ) {
        
        // Flush the content.
        $flushed = buddykit_flush_user_tmp_files($user_id);
        if ( $flushed ) {
            if ( ! class_exists('BuddyKitFileAttachment') )  {
                require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
            }

            // Start constructing activity template if files were successfully flushed.
            $gallery_id = 'buddykit-activity-gallery-'.uniqid();
            // Start template
            $media_html .= '<ul class="buddykit-activity-media-gallery items-'.absint(count($results)).'">';

                foreach( $results as $result ) {
                    $url = BuddyKitFileAttachment::get_user_uploads_url($result->user_id) . $result->name;
                    $media_html .= '<li class="buddykit-activity-media-gallery-item">';
                        $media_html .= '<a data-fancybox="'.esc_attr($gallery_id).'" title="'.esc_attr($result->name).'" href="'.esc_url($url).'">';
                            $media_html .= '<img src="'.esc_url($url).'" alt="'.esc_attr($result->name).'" />';
                        $media_html .= '</a>';
                    $media_html .= '</li>';
                }
            
            $media_html .= '</ul>';
            // End template
        }
    }

    if ( ! empty ( $media_html) ) {
        // Update the record.
        $updated = $wpdb->update(
            $wpdb->prefix . 'buddykit_user_files',
            array('is_tmp' => '0'), 
            array('user_id' => $user_id),
            array('%d'),
            array('%d')
        );
    }

    return $media_html;

}

function __does_user_owns_the_file_validate($id)
{
    return get_current_user_id() === (int) $id;
}

function buddykit_user_temporary_media_flush_all_endpoint(WP_REST_Request $request) {

    $request = $request->get_params();
    $user_id = get_current_user_id();

    if ( $user_id !== get_current_user_id() ) {
        return false;
    }

    $destroyed = buddykit_destroy_temporary_files($user_id);

    if ( $destroyed ) {
        return new WP_REST_Response(
           array(
                'status' => 200,
                'message' => 'delete_okay'
           )
       );
    }

    return false;
}

function buddykit_destroy_temporary_files($user_id){

    global $wpdb;

    if ( empty($user_id ) ) {return false;}
    
    $dir = wp_upload_dir();

    $tmpdir = $dir['basedir'] . '/buddykit/' . $user_id . '/tmp/';

    $record_deleted = $wpdb->delete( $wpdb->prefix.'buddykit_user_files', 
        array(
            'user_id' => $user_id,
            'is_tmp' => 1
        ),
        array(
            '%d',
            '%d'
        )
    );

    if ( $record_deleted ) {

        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
            require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
        }
        $fs = new WP_Filesystem_Direct(array());
        if ( $fs->delete($tmpdir, true) ) {
            return true;
        }
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

    if ( ! is_user_logged_in() ) {
        return false;
    }

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
        return false;
    }
    return new WP_REST_Response( array( 'file' => $file_id ) );
}

function buddykit_user_temporary_media_endpoint() {

    $final = array();

    if ( ! is_user_logged_in() ) {
         return new WP_REST_Response( 
            array( 
                'status' => 401,
                'message' => 'unauthorized'
            )
        );
    }

    if ( ! class_exists('BuddyKitFileAttachment') ) 
    {
        require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';;
    }

    $user_temporary_files = buddykit_get_user_uploaded_files();
   
    if ( $user_temporary_files && ! empty( $user_temporary_files ) ) {
        foreach( $user_temporary_files as $file ) {
            $final[] = array(
                    'name' => $file->name,
                    'public_url' => BuddyKitFileAttachment::get_user_uploads_url($file->user_id, $is_tmp = true) . $file->name,
                    'user_id' => $file->user_id,
                    'ID' => $file->id,
                    'type' => $file->type
                );
        }
    }

    return $final;
}

function buddykit_get_user_uploaded_files($tmp=false) {
    
    if ( ! is_user_logged_in() ) {
        return false;
    }

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
    
    //Make sure there is a logged-in user.
    if ( ! is_user_logged_in() ) {
        return new WP_REST_Response(array(
                'status' => 400,
                'file_id' => 0
            ));
    }
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
            array('%d', '%s', '%s','%d')
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

function buddykit_html_templates() {
    ?>
    <?php if ( is_user_logged_in() ) {?>
        <script type="text/template" id="buddykit-file-uploader">
            <div id="container">
                 <a id="browse" href="#" class="button button-primary" style="position: relative; z-index: 1;">
                    <?php esc_html_e('Photo/Video', 'buddykit'); ?>
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
        <div id="buddykit-hidden-error-message" style="display: none;">
            <h2><?php esc_html_e('Oops!', 'buddykit'); ?></h2>
            <p id="buddykit-hidden-error-message-text">
                <?php esc_html_e('There was an error performing that action. Please try again later. Thanks!', 'buddykit'); ?>
            </p>
        </div>
    <?php
    }
    return;
}


