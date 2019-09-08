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
add_filter( 'bp_activity_after_save', 'buddykit_activity_meta_attach_media' );

// Update the files activity id after saving.
add_action( 'bp_activity_after_save', 'buddykit_update_files_activity_id' );

// Add content to activity.
add_action( 'bp_activity_entry_content', 'buddykit_add_media_to_activity_content' );

/**
 * Updates the files activity id after saving.
 *
 * @param  array $bp_activity The returned argument from bp activity savings.
 * @return void
 */
function buddykit_update_files_activity_id( $bp_activity ) {

	global $wpdb;
	if ( WP_DEBUG ) {
		$wpdb->show_errors();
	}
	$find_buddykit_attachments = preg_match_all( '/< *li[^>]*data-file-id *= *["\']?([^"\']*)/i', $bp_activity->content, $matches );
	if ( ! empty( $matches[1] ) ) {
		$media_list = implode( ',', $matches[1] );
		if ( ! empty( $media_list ) ) {
			// Update buddykit user file
			$stmt = "UPDATE {$wpdb->prefix}buddykit_user_files SET activity_id = %d WHERE id IN (" . esc_sql( $media_list ) . ')';
			$query = $wpdb->prepare( $stmt, $bp_activity->id );
			if ( ! $wpdb->query( $query ) ) {
				if ( WP_DEBUG ) {
					$wpdb->print_error();
				}
			}
		}
	}

}
// Register our rest api endpoint.
add_action( 'rest_api_init', function () {

	// New upload.
	register_rest_route( 'buddykit/v1', '/upload', array(
		'methods' => 'POST',
		'callback' => 'buddykit_activity_route_endpoint',
	) );

	// Delete Uploaded Media.
	register_rest_route( 'buddykit/v1', '/delete/(?P<id>\d+)', array(
		'methods' => 'DELETE',
		'callback' => 'buddykit_activity_route_endpoint_delete',
		'args' => array(
				'id' => array(
					'validate_callback' => 'buddykit_is_verified_owner_and_is_admin_validate',
				),
			),
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
 * Callback function to delete uploaded media file.
 *
 * @param  mixed $http_request The callback argument used in WP REST Api.
 * @return mixed Boolean (false) on fail. Other wise WP_Rest_Response object.
 */
function buddykit_activity_route_endpoint_delete( $http_request ) {

	global $wpdb;

	$params = $http_request->get_params();
	$id = $params['id'];

	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user_id = get_current_user_id();

	$file_deleted = false;

	if ( current_user_can( 'manage_options' ) ) {
		// Get the file record of the file with out user id. Administrator can delete anything.
		$file_record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files 
			WHERE id = %d", $id), OBJECT );
		// Actually delete the file. . Administrator can delete anything.
		$file_deleted = $wpdb->delete( $wpdb->prefix . 'buddykit_user_files',
		array( 'id' => $id ), array( '%d' ) );
	} else {
		// Get the file record
		$file_record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files 
			WHERE id = %d AND user_id = %d", $id, $user_id), OBJECT );
		// Actually delete the file.
		$file_deleted = $wpdb->delete( $wpdb->prefix . 'buddykit_user_files',
		array( 'id' => $id, 'user_id' => $user_id ), array( '%d', '%d' ) );
	}
	// Delete the record in database.
	if ( $file_deleted ) {

		$actual_file_path = buddykit_get_user_upload_dir( false, $file_record->user_id ) . $file_record->name;
		$__exp_filename = explode( '.', $file_record->name );
		$actual_file_path_thumb = buddykit_get_user_upload_dir( false, $file_record->user_id ) . $__exp_filename[0] . '-thumbnail.' . $__exp_filename[1];

		// Delete fill size image.
		if ( file_exists( $actual_file_path ) ) {
			wp_delete_file( $actual_file_path );
		} else {
			echo 'File: ' . $actual_file_path . ' does not exists.';
		}
		// Delete thumbnail.
		if ( file_exists( $actual_file_path_thumb ) ) {
			wp_delete_file( $actual_file_path_thumb );
		}
	}

	// Update bp activity meta
	$activity_id = $file_record->activity_id;
	$stmt = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}buddykit_user_files WHERE activity_id = %d", absint( $activity_id ) );

	$new_activity_meta_files = $wpdb->get_results( $stmt, ARRAY_A );

	bp_activity_update_meta( $activity_id, 'buddykit_media_files', $new_activity_meta_files );
	return new WP_REST_Response(
		array(
			'id' => absint( $id ),
			'status' => 200,
			'message' => 'delete_okay',
		)
	);
}

/**
 * Check if the current user is the owner of the file.
 *
 * @param  integer $file_id The file id.
 * @return boolean True on success. Otherwise, false.
 */
function buddykit_is_verified_owner_validate( $file_id ) {

	global $wpdb;

	$user_id = get_current_user_id();

	$stmt = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}buddykit_user_files 
        WHERE id = %d AND user_id = %d", $file_id, $user_id);

	$result = $wpdb->get_row( $stmt, OBJECT ); // db call ok.

	// Return
	if ( ! empty( $result ) and is_object( $result ) ) {
		return true;
	}

	return false;
}

function buddykit_is_verified_owner_and_is_admin_validate( $file_id ) {

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return buddykit_is_verified_owner_validate( $file_id );
}

/**
 * Appends the media to the content of the activity.
 *
 * @return string The activity html.
 */
function buddykit_activity_meta_attach_media( $activity ) {

	global $wpdb;

	$user_id = get_current_user_id();
	$activity_meta_updated = false;
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
			}

			foreach ( $results as $result ) {

				$url = BuddyKitFileAttachment::get_user_uploads_url( $result->user_id ) . $result->name;
				$extension = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
				$filename  = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME );

				$activity_media_meta[] = array(
					'id' => $result->id,
					'name' => $result->name,
					'thumbnail' => $filename . '-thumbnail.' . $extension,
					'site_id' => get_current_blog_id(),// multisite support
				);

			}

			$activity_meta_updated = bp_activity_update_meta( $activity->id, 'buddykit_media_files', $activity_media_meta );
		} // End if ( $flushed ).

	} // End if ( ! empty( $results ) ).

	if ( $activity_meta_updated ) {
		// Update the record.
		$updated = $wpdb->update(
			$wpdb->prefix . 'buddykit_user_files',
			array(
					'is_tmp' => '0',
					'activity_id' => absint( $activity->id ),
				),
			array(
					'user_id' => $user_id,
					'is_tmp' => '1',
				),
			array( '%d' ), // Where Datatype.
			array( '%d' ) // Activity_id.
		);
	}

	return $activity_meta_updated;

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
 *
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
 *
 * @param  int $user_id The user id.
 * @return boolean True on success. Otherwise, false.
 */
function buddykit_destroy_temporary_files( $user_id ) {

	global $wpdb;

	if ( empty( $user_id ) ) {return false;}

	$dir = wp_upload_dir();

	$tmpdir = $dir['basedir'] . '/buddykit/' . $user_id . '/tmp/';

	$record_deleted = $wpdb->delete( $wpdb->prefix . 'buddykit_user_files',
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
 *
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
		$wpdb->prefix . 'buddykit_user_files',
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
 *
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

	$max_image_size = absint( $config['config']['options']['buddykit_field_max_image_size'] );
	$max_video_size = absint( $config['config']['options']['buddykit_field_max_video_size'] );

	// Set the maxsize for image.
	$max_file_size = $max_image_size;
	// Set the max file size to video settings if the uplpoaded fie is video.
	if ( false !== strpos( 'video/mp4', $_FILES['file']['type'] ) ) {
		$max_file_size = $max_video_size;
	}

	$fv->set_maxsize( $max_file_size );

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
 *
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
function buddykit_get_user_upload_dir( $is_temporary = false, $user_id = 0 ) {

	$dir = wp_upload_dir();

	if ( $user_id <= 0 ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$url = trailingslashit( $dir['basedir'] ) . sprintf( 'buddykit/%d/', $user_id );

	if ( $is_temporary ) {
		return trailingslashit( $url . 'tmp' );
	}

	return trailingslashit( $url . 'uploads' );
}


/**
 * Register all needed scripts.
 *
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
 *
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


/**
 * Returns all user photos uploading in activity
 *
 * @param  int $user_id The user id
 * @return array The photos of user
 */
function buddykit_get_user_activity_photos( $user_id ) {

	global $wpdb;

	$photos = array();

	$stmt = "SELECT id, user_id, name, type FROM {$wpdb->prefix}buddykit_user_files 
			WHERE user_id = %d AND type IN('image/png', 'image/jpeg', 'image/jpg', 'image/gif')
			ORDER BY id DESC
			";

	$query = $wpdb->prepare( $stmt, $user_id );

	$results = $wpdb->get_results( $query, OBJECT );

	if ( ! empty( $results ) ) {
		foreach ( $results as $photo ) {
			$photos[] = array(
				'image_id' => $photo->id,
				'image_src_full' => buddykit_get_user_uploads_uri( $user_id, $photo->name ),
				'image_src' => buddykit_get_user_uploads_thumbnail_uri( $user_id, $photo->name ),
				'image_alt' => $photo->name,
			);
		}
	}

	return $photos;
}

/**
 * Get the specific user video.
 *
 * @param  integer $user_id The id of the user.
 * @return array The collection of videos made by user.
 */
function buddykit_get_user_activity_videos( $user_id ) {
	global $wpdb;
	$videos = array();
	$stmt = "SELECT id, user_id, name, type FROM {$wpdb->prefix}buddykit_user_files 
		WHERE user_id = %d AND type IN('video/mp4') ORDER BY id DESC
		";
	$query = $wpdb->prepare( $stmt, $user_id );
	$results = $wpdb->get_results( $query, OBJECT );

	if ( ! empty( $results ) ) {
		foreach ( $results as $video ) {
			$videos[] = array(
				'video_id' => $video->id,
				'video_src' => buddykit_get_user_uploads_uri( $user_id, $video->name ),
				'video_alt' => $video->name,
			);
		}
	}
	return $videos;
}

/**
 * Adds the media files to activity content
 *
 * @return void
 */
function buddykit_add_media_to_activity_content() {

	$media_files = bp_activity_get_meta( bp_get_activity_id(), 'buddykit_media_files' );

	if ( ! empty( $media_files ) ) {

		if ( ! class_exists( 'BuddyKitFileAttachment' ) ) {
				require_once BUDDYKIT_PATH . 'src/includes/media/class-file-attachment.php';
		}
			$gallery_id = 'buddykit-activity-gallery-' . uniqid();?>
			<ul class="buddykit-activity-media-gallery items-<?php echo absint( count( $media_files ) ); ?>">
				
				<?php foreach ( $media_files as $file ) { ?>
					
					<?php $file_name = $file['name']; ?>
					<?php $file_id = $file['id']; ?>
					<?php $file_site_id = 0; ?>

					<?php if ( isset( $file['site_id'] ) ) { ?>
						<?php $file_site_id = $file['site_id']; ?>
					<?php } ?>

					<?php $url = BuddyKitFileAttachment::get_user_uploads_url( bp_get_activity_user_id(), false, $file_site_id ) . $file_name; ?>
					
					<?php $extension = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ); ?>
					<?php $filename_from_path  = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME ); ?>

					<?php
						$thumbnail_url = BuddyKitFileAttachment::get_user_uploads_url(
							bp_get_activity_user_id(),
							false,
							$file_site_id
						);

						$thumbnail_url .= $filename_from_path . '-thumbnail.' . $extension;
					?>

					<?php $allowed_video_extensions = array( 'mp4' ); ?>

					<?php if ( in_array( $extension, $allowed_video_extensions ) ) { ?>
							<li data-file-id="<?php echo absint( $file_id );?>" class="buddykit-activity-media-gallery-item type-video">
								<div class="buddykit-media-wrap">
									<div class="buddykit-media-button-play-wrap">
										<div class="buddykit-media-button-play"></div>
									</div>
									<div class="buddykit-video-inner-wrap">
										<video id="buddykit-media-video-<?php echo esc_attr( $file_id ); ?>" 
											src="<?php echo esc_url( $url ); ?>" width="100%" height="100%" class="buddykit-media-video">
										</video>
									</div>
								</div>
							</li>
					<?php } else { ?>
						<li data-file-id="<?php echo absint( $file_id );?>" class="buddykit-activity-media-gallery-item type-image">
							<a data-fancybox="<?php echo esc_attr( $gallery_id ); ?>" title="<?php echo esc_attr( $file_name ); ?>" 
								href="<?php echo esc_url( $url ); ?>">
								<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $file_name ); ?>" />
							</a>
						</li>
					<?php } ?>
				
				<?php } ?>

			</ul>
		<?php
	}
	return;
}

function buddykit_rebuild_activity_media( $activity_id ) {

	global $wpdb;

	return;

}

/**
 * Returns the 'thumbnail' upload url of the image file. Pass the name.
 *
 * @param  string $user_id  The user id.
 * @param  string $filename The filename.
 * @return string The url of the uploaded file.
 */
function buddykit_get_user_uploads_thumbnail_uri( $user_id, $filename = '' ) {

	$wp_upload = wp_upload_dir();
	$parts = explode( '.', sanitize_file_name( $filename ) );
	return $base = $wp_upload['baseurl'] . '/buddykit/' . absint( $user_id ) . '/uploads/' . $parts[0] . '-thumbnail.' . $parts[1];

}


/**
 * Returns the upload url of the image file. Pass the name.
 *
 * @param  string $user_id  The user id.
 * @param  string $filename The filename.
 * @return string The url of the uploaded file.
 */
function buddykit_get_user_uploads_uri( $user_id, $filename = '' ) {

	$wp_upload = wp_upload_dir();

	return $base = $wp_upload['baseurl'] . '/buddykit/' . absint( $user_id ) . '/uploads/' . sanitize_file_name( $filename );

}
