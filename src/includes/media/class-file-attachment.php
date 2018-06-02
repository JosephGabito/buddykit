<?php
/**
 * This file is part of the BuddyKit WordPress Plugin package.
 *
 * (c) Joseph G. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package BuddyKit\Media\FileAttachments
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * TaskBrekerFileAttachment contains several useful methods for attaching,
 * deleting, and updating task file attachments.
 */
class BuddyKitFileAttachment {

	/**
	 * Do we alter the upload dir path?
	 *
	 * @var boolean
	 */
	public $set_upload_dir = true;

	/**
	 * Our class constructor;
	 *
	 * @param  boolean $set_upload_dir Set true to alter 'upload_dir' hook.
	 * @return void.
	 */
	public function __construct( $set_upload_dir = true ) {

		$this->set_upload_dir = $set_upload_dir;

		if ( $this->set_upload_dir ) {
			add_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
		}

		add_filter( 'wp_handle_upload_prefilter', array( $this, 'buddykit_on_upload_change_file_name' ) );
	}

	/**
	 * Sets the upload directory to our custom file path.
	 *
	 * @param mixed $dirs The collection of directory properties.
	 * @return  array The directory.
	 */
	public function set_upload_dir( $dirs ) {

		$user_id = get_current_user_id();

		$upload_dir = apply_filters( 'buddykit_task_file_attachment_upload_dir',
		sprintf( 'buddykit/%d/tmp', $user_id ) );

	    $dirs['subdir'] = $upload_dir;
	    $dirs['path'] = trailingslashit( $dirs['basedir'] ) . $upload_dir;
	    $dirs['url'] = trailingslashit( $dirs['baseurl'] ) . $upload_dir;

	    return $dirs;

	}

	/**
	 * Catch any file attachments and let wp_handle_upload do the uploading.
	 *
	 * @return array The http message.
	 */
	public function process_http_file() {

		if ( ! is_user_logged_in() ) {
			return array( 'error' => __( 'Authentication issues. Terminating...', 'buddykit' ) );
		}

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		}

		$fs = new WP_Filesystem_Direct( array() );

		$file = '';

		if ( isset( $_FILES['file'] ) && ! empty( $_FILES['file']['name'] ) ) {
			$file = wp_unslash( $_FILES['file'] );
		}

		if ( empty( $file ) ) {
			return array( 'error' => __( 'Did not received any http file.', 'buddykit' ) );
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return array( 'error' => __( 'An error occured. Please check maximum size and maximum header size.', 'buddykit' ) );
		}

		$upload_overwrites = array(
			'test_form' => false,
		);

		// First delete everything in tmp directory.
		$path = wp_upload_dir();

		$tmp_dir = $path['basedir'] . sprintf( '/buddykit/%d/tmp', get_current_user_id() );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Check for existing directory.
		if ( $fs->is_dir( $tmp_dir ) ) {
			return wp_handle_upload( $file, $upload_overwrites );
		} else {
			// Re-create the directory.
			if ( ! $fs->mkdir( $tmp_dir ) ) {
				return array( 'error' => __( 'Unable to create temporary directory. Permission error.', 'buddykit' ) );
			}
		}

		return array( 'error' => __( 'Unable to handle file upload.', 'buddykit' ) );

	}

	/**
	 * Changes the filename on update for integrity.
	 * @param  array $file The file.
	 * @return array The file.
	 */
	public function buddykit_on_upload_change_file_name( $file ) {
		$ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$file['name'] = md5( time().$file['name'] ).'.'.$ext;
		return $file;
	}
	/**
	 * Delete the file under a specific task.
	 *
	 * @param  string $file_name The file name of the attached file.
	 * @return boolean True on success. Otherwise, false.
	 */
	public function delete_file( $file_name = '' ) {

		if ( empty( $file_name ) ) {
			return false;
		}

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		}

		$fs = new WP_Filesystem_Direct( $args = array() );

		// Start deleting the file.
		$path = $this->get_current_user_file_path( $file_name, $is_tmp = true );

		return $fs->delete( $path );

	}

	/**
	 * Removes temporary media files and updates the status in the database.
	 * @param  integer $user_id The user id.
	 * @return boolean True on success. Otherwise, false.
	 */
	public function flush_dir( $user_id ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		}

		$path = wp_upload_dir();

		$upload_dir = $path['basedir'] . '/buddykit/';

		$source_path = $upload_dir . $user_id . '/tmp/';

		$destination_path = $upload_dir . $user_id . '/uploads/';

		$fs = new WP_Filesystem_Direct( array() );

		$files_to_be_moved = array();
		// Move the 'tmp' directory files to 'uploads'.
		// 1. read all the files at temporary directory.
		$tmp_files = $fs->dirlist( $source_path );

		if ( ! empty( $tmp_files ) ) {

			// 2. create the destination directory('uploads') if dir is not existing.
			$fs->mkdir( $destination_path );

			if ( $fs->is_dir( $destination_path ) ) {
				
				$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension

				foreach ( $tmp_files as $file ) {

					// Read the file source.
					$file_source = $source_path . $file['name'];

					$mimetype =  finfo_file($finfo, $file_source);

					// Video
					if ( strpos( $mimetype, 'video' ) !== false ) {
						
						$file_destination = $destination_path . $file['name'];
						$fs->copy( $file_source, $file_destination );

					} else {
						
					// Image
						// Edit the image.
						$image = wp_get_image_editor( $file_source );

						// Crop the image.
						if ( count( $tmp_files ) > 1 ) {
							$image->resize( 375, 375, true );
						} else {
							$image->resize( 500, 500, true );
						}

						// General thumbnail.
						$thumbnail_name = pathinfo( $file_source, PATHINFO_FILENAME );
						$thumbnail_extension = pathinfo( $file_source, PATHINFO_EXTENSION );

						$generated_thumbnail_name = $thumbnail_name . '-thumbnail.' . $thumbnail_extension;
						$thumbnail_source = $source_path . $generated_thumbnail_name;

						// Save to temporary dir.
						$image->save( $thumbnail_source );

						// Save the destination path of thumbnail.
						$thumbnail_final_path = $destination_path . $generated_thumbnail_name;

						$file_destination = $destination_path . $file['name'];

						// 3. move all files from temporary directory to uploads via 'copy'.
						$fs->copy( $file_source, $file_destination );
						$fs->copy( $thumbnail_source, $thumbnail_final_path );
					}
					

				}
				// Delete the temporary directory.
				$fs->rmdir( $source_path, $recursive = true );

				return true;

			} else {
				return false;
			}
		} else {
			return false;
		}

		return false;

	}

	/**
	 * Returns the current logged-in user file path.
	 * @param  string  $name The file name.
	 * @param  boolean $is_tmp If directory is temporary or not.
	 */
	public function get_current_user_file_path( $name = '', $is_tmp = false ) {

		if ( empty( $name ) ) {
			return false;
		}

		$path = wp_upload_dir();

		$upload_dir = $path['basedir'] . '/buddykit';

		$uploads = $is_tmp ? 'tmp': 'uploads';

		$file = $upload_dir .'/'. absint( get_current_user_id() ) .'/'. $uploads .'/'.$name;

		return $file;

	}

	/**
	 * Transport file method 'moves' the file located inside 'tmp' directory to the task directory.
	 *
	 * @param  string  $file_name The filename of the task.
	 * @param  integer $task_id   The task ID.
	 * @return boolean             True on success. Otherwise, false.
	 */
	protected function transport_file( $file_name = '', $task_id = 0 ) {

		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		}

		$args = array();

		$fs = new WP_Filesystem_Direct( $args );

		$path = wp_upload_dir();
		$tmp_directory = $path['basedir'] . '/buddykit/' . get_current_user_id() . '/tmp/' . $file_name;
		$destination_directory = $path['basedir'] . '/buddykit/' . get_current_user_id() . '/tasks/' . $task_id . '/';
		$final_destination = $destination_directory . $file_name;

		if ( wp_mkdir_p( $destination_directory ) ) {
			if ( ! $fs->move( $tmp_directory, $final_destination ) ) {
				return false;
			}
		} else {
			return false;
		}

	}

	/**
	 * Returns the user's upload directory
	 * @param  integer $user_id The id of the user.
	 * @param  boolean $is_tmp If directory is temporary or not.
	 * @return string The upload directory of the user.
	 */
	public static function get_user_uploads_url( $user_id = 0, $is_tmp = false ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		$uploads_dir = wp_upload_dir();
		$dir = '/uploads/';
		if ( $is_tmp ) {
			$dir = '/tmp/';
		}
		$user_uploads_dir = $uploads_dir['baseurl'] . '/buddykit/' . $user_id . $dir;

		return $user_uploads_dir;

	}

	/**
	 * Our class destruct mechanism. Since we set the directory path on object creation. We need to revert it back
	 * to default WordPress directory path to prevent any bugs.
	 *
	 * @return void.
	 */
	public function __destruct() {
		if ( $this->set_upload_dir ) {
			remove_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
		}
		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'buddykit_on_upload_change_file_name' ) );
	}
}
