<?php
/**
 * This file is part of the BuddyKit WordPress Plugin package.
 *
 * (c) Joseph G. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package BuddyKit\Media
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Enqueue the script needed.
add_action( 'wp_enqueue_scripts', 'buddykit_register_scripts' );

// Html Templates.
add_action( 'wp_footer', 'buddykit_html_templates' );

// Append media to activity.
add_filter( 'bp_activity_new_update_content', '__buddykit_activity_append_media_content' );

// Append media to groups activity.
add_filter( 'groups_activity_new_update_content', '__buddykit_activity_append_media_content' );

// We need to register REST API End Point.
add_filter( 'bp_activity_allowed_tags', '__buddykit_update_activity_kses_filter', 10 );

// Register our rest api endpoint.
add_action( 'rest_api_init', function () {

	// New upload.
	register_rest_route( 'buddykit/v1', '/upload', array(
		'methods' => 'POST',
		'callback' => 'buddykit_activity_route_endpoint',
	) );

	// Temporary media list.
	register_rest_route( 'buddykit/v1', '/user-temporary-media', array(
		'methods' => 'GET',
		'callback' => 'buddykit_user_temporary_media_endpoint',
	) );

	// Delete media.
	register_rest_route( 'buddykit/v1', '/user-temporary-media-delete/(?P<id>\d+)', array(
		'methods' => 'DELETE',
		'callback' => 'buddykit_user_temporary_media_delete_endpoint',
		'args' => array(
				'id' => array(
					'validate_callback' => 'buddykit_is_verified_owner_validate',
				),
			),
	) );

	// Flush temporary media.
	 register_rest_route( 'buddykit/v1', '/user-temporary-flush/(?P<id>\d+)', array(
		 'methods' => 'DELETE',
		 'callback' => 'buddykit_user_temporary_media_flush_all_endpoint',
		 'args' => array(
				'id' => array(
					'validate_callback' => 'buddykit_does_user_owns_the_file_validate',
				),
			),
	 ) );
});


/**
 * Check if the current user is the owner of the file.
 * @param  integer $file_id The file id.
 * @return boolean True on success. Otherwise, false.
 */
function buddykit_is_verified_owner_validate( $file_id ) {

	global $wpdb;

	$user_id = get_current_user_id();

	$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files 
        WHERE id = %d AND user_id = %d", $file_id, $user_id);

	$result = $wpdb->get_row( $stmt, OBJECT ); // db call ok.

	if ( ! empty( $result ) and is_object( $result ) ) {
		return true;
	}

	return false;
}

/**
 * Callback function to allow specific markup in activity stream.
 * @return array The collection of allowed markups.
 */
function __buddykit_update_activity_kses_filter() {

	$bp_allowed_tags = bp_get_allowedtags();

	$bp_allowed_tags['a']['data-fancybox'] = array();

	$bp_allowed_tags['ul'] = array(
						'class' => array(),
					 );
	$bp_allowed_tags['li'] = array(
						'class' => array(),
					 );

	$bp_allowed_tags['div'] = array(
						'class' => array(),
					 );

	$bp_allowed_tags['video'] = array(
						'id' => array(),
						'width' => array(),
						'height' => array(),
						'class' => array(),
						'src' => array(),
						'controls' => array(),
						'data-mejsoptions' => array(),
					);
	return $bp_allowed_tags;
}


/**
 * Replaces http:// with // to support SSL.
 * @param  string $activity_content The content of the activity.
 * @return string The replaced string.
 */
function __buddykit_activity_append_media_content( $activity_content ) {
	$activity_content .= str_replace( 'http://','//',buddykit_activity_append_media_content() );
	return $activity_content;
}

/**
 * Appends the media to the content of the activity.
 * @return string The activity html.
 */
function buddykit_activity_append_media_content() {

	global $wpdb;

	$media_html = '';

	$user_id = get_current_user_id();

	$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files
            WHERE user_id = %d AND is_tmp = %d",
		$user_id, 1
	);

	$results = $wpdb->get_results( $stmt, OBJECT );

	// Check if there are temporary files.
	if ( ! empty( $results ) ) {

		// Flush the content.
		$flushed = buddykit_flush_user_tmp_files( $user_id );

		if ( $flushed ) {
			if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
				require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
				;
			}

			// Start constructing activity template if files were successfully flushed.
			$gallery_id = 'buddykit-activity-gallery-'.uniqid();

			// Start template.
			$media_html .= '<ul class="buddykit-activity-media-gallery items-'.absint( count( $results ) ).'">';

			foreach ( $results as $result ) {

				$url = BuddyKitFileAttachment::get_user_uploads_url( $result->user_id ) . $result->name;

				$extension = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
				$filename  = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME );

				$thumbnail_url = BuddyKitFileAttachment::get_user_uploads_url( $result->user_id ) . $filename.'-thumbnail.'.$extension;
				$allowed_video_extensions= array('mp4');
				
					
					if ( in_array( $extension, $allowed_video_extensions ) ) {
						$media_html .= '<li class="buddykit-activity-media-gallery-item type-video">';
							$media_html .= '<div class="buddykit-media-wrap">';
								$media_html .= '<div class="buddykit-media-button-play-wrap">';
									$media_html .= '<div class="buddykit-media-button-play"></div>';
								$media_html .= '</div>';
								$media_html .= '<video id="buddykit-media-video'.esc_attr($result->id).'" src="'.esc_url($url).'" width="100%" height="100%" class="buddykit-media-video"></video>';
							$media_html .= '</div>';
						$media_html .= '</li>';
					} else {
						$media_html .= '<li class="buddykit-activity-media-gallery-item type-image">';
							$media_html .= '<a data-fancybox="'.esc_attr( $gallery_id ).'" title="'.esc_attr( $result->name ).'" href="'.esc_url( $url ).'">';
								$media_html .= '<img src="'.esc_url( $thumbnail_url ).'" alt="'.esc_attr( $result->name ).'" />';
							$media_html .= '</a>';
						$media_html .= '</li>';
					}
					
				

			}

			$media_html .= '</ul>';
			// End template.
		}
	}

	if ( ! empty( $media_html ) ) {
		// Update the record.
		$updated = $wpdb->update(
			$wpdb->prefix . 'buddykit_user_files',
			array( 'is_tmp' => '0' ),
			array( 'user_id' => $user_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	return $media_html;

}

/**
 * Checks if the current user owns the file.
 *
 * @param  int $id The file id.
 * @return boolean True on success. Otherwise, false.
 */
function buddykit_does_user_owns_the_file_validate( $id ) {
	return get_current_user_id() === (int) $id;
}

/**
 * Flushes all temporary media. This is actually a WPRest endpoint.
 * @param  array $request The http request vars.
 * @return mixed Returns WP_REST_Response object on success. Otherwise, false.
 */
function buddykit_user_temporary_media_flush_all_endpoint( $request ) {

	$request = $request->get_params();
	$user_id = get_current_user_id();

	if ( get_current_user_id() !== $user_id ) {
		return false;
	}

	$destroyed = buddykit_destroy_temporary_files( $user_id );

	if ( $destroyed ) {
		return new WP_REST_Response(
			array(
				'status' => 200,
				'message' => 'delete_okay',
			)
		);
	}

	return false;
}

/**
 * Destroys the temporary files.
 * @param  int $user_id The user id.
 * @return boolean True on success. Otherwise, false.
 */
function buddykit_destroy_temporary_files( $user_id ) {

	global $wpdb;

	if ( empty( $user_id ) ) {return false;}

	$dir = wp_upload_dir();

	$tmpdir = $dir['basedir'] . '/buddykit/' . $user_id . '/tmp/';

	$record_deleted = $wpdb->delete( $wpdb->prefix.'buddykit_user_files',
		array(
			'user_id' => $user_id,
			'is_tmp' => 1,
		),
		array(
			'%d',
			'%d',
		)
	);

	if ( $record_deleted ) {

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		}
		$fs = new WP_Filesystem_Direct( array() );
		if ( $fs->delete( $tmpdir, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Flushes the user temporary files.
 *
 * @param  int $user_id The user id.
 * @return boolean Returns true on success. Otherwise, false.
 */
function buddykit_flush_user_tmp_files( $user_id ) {

	global $wpdb;

	if ( empty( $user_id ) ) { return false; }

	if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
		require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
		;
	}

	$fs = new BuddyKitFileAttachment();

	$flushed = $fs->flush_dir( $user_id );

	if ( $flushed ) {
		return true;
	}

	return false;
}

/**
 * Callback function for our rest to handle temporary media deletion.
 * @param  mixed $request The wp rest api request.
 * @return mixed returns WP_REST_Response object on okay. Otherwise, false.
 */
function buddykit_user_temporary_media_delete_endpoint( $request ) {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	global $wpdb;

	$params = $request->get_params();
	$file_id = 0;

	if ( ! empty( $params['id'] ) ) {
		$file_id = $params['id'];
	}

	$stmt = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}buddykit_user_files WHERE id = %d;", absint( $file_id ) );
	$file = $wpdb->get_row( $stmt, OBJECT );

	// Delete from record
	$deleted = $wpdb->delete(
		$wpdb->prefix.'buddykit_user_files',
		array( 'id' => absint( $file_id ) ),
		array( '%d' )
	);

	if ( $deleted ) {
		// Delete the file in the tmp
		if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
			require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
			;
		}
		$fs = new BuddyKitFileAttachment();

		if ( ! $fs->delete_file( $file->name ) ) {
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
				'message' => 'unauthorized',
			 )
		 );
	}

	if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
		require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
		;
	}

	$user_temporary_files = buddykit_get_user_uploaded_files();

	if ( $user_temporary_files && ! empty( $user_temporary_files ) ) {
		foreach ( $user_temporary_files as $file ) {
			$final[] = array(
					'name' => $file->name,
					'public_url' => BuddyKitFileAttachment::get_user_uploads_url( $file->user_id, $is_tmp = true ) . $file->name,
					'user_id' => $file->user_id,
					'ID' => $file->id,
					'type' => $file->type,
				);
		}
	}

	return $final;
}

function buddykit_get_user_uploaded_files( $tmp = false ) {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	global $wpdb;

	$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files
        WHERE user_id = %d AND is_tmp = 1;", get_current_user_id());

	$result = $wpdb->get_results( $stmt );

	return $result;
}

/**
 * Handle multiple file uploads
 * @return object instance of WP_REST_Response
 */
function buddykit_activity_route_endpoint() {

	// Make sure there is a logged-in user.
	if ( ! is_user_logged_in() ) {
		return new WP_REST_Response(array(
				'status' => 400,
				'file_id' => 0,
			));
	}

	global $wpdb;

	$http_response = array();

	if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
		require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
	}

	if ( ! class_exists( 'BuddyKitFileValidator' ) ) {
		require_once BUDDYKIT_PATH . 'src/includes/class-file-validator.php';
	}

	$file = '';

	$fs = new BuddyKitFileAttachment();

	$fv = new BuddyKitFileValidator( wp_unslash( $_FILES['file'] ) );

	$allowed_extensions = array(
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',

			'mp4'  => 'video/mp4',
		);

	$config = buddykit_config();
	$max_file_size = absint( $config['config']['max_upload_size'] ) * 1000000;

	$fv->set_maxsize( $max_file_size ); // 5MB
	$fv->set_allowed_extension( $allowed_extensions );

	$validated = $fv->validate( $file );

	if ( ! is_bool( $validated ) && true !== $validated ) {
		return new WP_REST_Response(array(
				'status' => 406,
				'message' => 'file_not_acceptable',
				'error_message' => $validated,
			));
	}

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
		array( '%d', '%s', '%s','%d' )
	);

	if ( $inserted ) {
		$http_response['status'] = 200;
		$http_response['file_id'] = absint( $wpdb->insert_id );
	}

	return new WP_REST_Response( $http_response );

}

/**
 * Returns the temporary url for the given file.
 * @param array $file The file.
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
 * Fetches the current user's upload directory.
 *
 * @param boolean $is_temporary Whether you want to getch the temporary file or not.
 *
 * @return string The current user upload directory.
 */
function buddykit_get_user_upload_dir( $is_temporary = false ) {
	$dir = wp_upload_dir();

	$url = trailingslashit( $dir['baseurl'] ) . sprintf( 'buddykit/%d/', get_current_user_id() );

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

	// Buddykit stylesheet.
	wp_enqueue_style( 'buddykit-style', BUDDYKIT_PUBLIC_URI . 'css/buddykit.css', false );
	// Magnific popup stylesheet.
	wp_enqueue_style( 'magnific-popup', BUDDYKIT_PUBLIC_URI . 'css/vendor/magnific-popup/magnific-popup.css', false );
	// Plyr stylesheet.
	wp_enqueue_style( 'plyr-style', 'https://cdn.plyr.io/3.3.10/plyr.css', false );

	// Plyr
	wp_enqueue_script( 'plyr', 'https://cdn.plyr.io/3.3.10/plyr.polyfilled.js',  array(), false );

	// Magnific Popup
	wp_enqueue_script( 'magnific-popup', BUDDYKIT_PUBLIC_URI . 'js/vendor/magnific-popup/magnific-popup.js',  array( 'jquery', 'imagesloaded' ), false );

	// Buddykit
	wp_enqueue_script( 'buddykit-src', BUDDYKIT_PUBLIC_URI . 'js/buddykit.js', array( 'plupload-html5', 'backbone', 'underscore' ), false );
	
	wp_localize_script( 'buddykit-src', '__buddyKit', buddykit_config() );

	return;
}

/**
 * Underscore templates for our media.
 * @return void
 */
function buddykit_html_templates() {
	$options = buddykit_config_get_option();
	?>
    <?php if ( is_user_logged_in() ) {?>
        <script type="text/template" id="buddykit-file-uploader">
            <div id="buddykit-container">
                <a id="buddykit-browse" href="#" class="button button-primary" style="position: relative; z-index: 1;">
                    <?php echo esc_html( $options['buddykit_field_upload_button_label'] ); ?>
                </a>
            </div>
            <div id="buddykit-filelist-wrap" style="display: none;">
                <ul id="buddykit-filelist"></ul>
                <div id="buddykit-flush-tmp-files-wrap">
                    <a href="#" style="display: none;" id="buddykit-flush-temporary-files-btn" title="<?php esc_attr_e( 'Clear All Files','buddykit' ); ?>" class="button button-danger">
                        <?php esc_html_e( 'Clear Files','buddykit' ); ?>
                    </a>
                </div>
            </div>
        </script>

        <script type="text/template" id="buddykit-file-list-template">
            <li class="buddykit-filelist-item">
            	<% if ( type.indexOf('video') >= 0) { %>
            		<video src="<%= public_url %>" width="90" height="90" class="mejs__player" data-mejsoptions='"alwaysShowControls": "true"}'>
            		</video>
            	<% } else { %>
            		 <img width="150" src="<%= public_url %>" alt="<%= name %>">
            	<% } %>
                <a data-file-id="<%= ID %>" data-model-id="<%= this.id %>" title="<%= name %>" class="buddykit-filelist-item-delete" href="#"> &times; </a>
            </li>
        </script>

        <div id="buddykit-hidden-error-message" style="display: none;">
            <h2><?php esc_html_e( 'Oops!', 'buddykit' ); ?></h2>
            <p id="buddykit-hidden-error-message-text">
                <?php esc_html_e( 'There was an error performing that action. Please try again later. Thanks!', 'buddykit' ); ?>
            </p>
        </div>
    <?php
}
	return;
}
