<?php
/**
 * This file is part of the BuddyKit WordPress Plugin package.
 *
 * (c) Joseph G. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package BuddyKit\TaskBreakerCore\FileAttachments
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

		$upload_overwrites = array( 'test_form' => false );

		// First delete everything in tmp directory.
		$path = wp_upload_dir();

		$tmp_dir = $path['basedir'] . sprintf( '/buddykit/%d/tmp', get_current_user_id() );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Check for existing directory
		if ( $fs->is_dir( $tmp_dir ) ) {
			return wp_handle_upload( $file, $upload_overwrites );
		} else {
			// Re-create the directory.
			if ( $fs->mkdir( $tmp_dir ) ) {
			// Then, move the file.
			} else {
				return array( 'error' => __( 'Unable to create temporary directory. Permission error.', 'buddykit' ) );
			}
		}

		return array( 'error' => __( 'Unable to handle file upload.', 'buddykit' ) );

	}

	/**
	 * Delete the file under a specific task.
	 *
	 * @param  integer $task_id   The id of the task.
	 * @param  string  $file_name The file name of the attached file.
	 * @return boolean             True on success. Otherwise, false.
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

	public function flush_dir($user_id) {

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
		// Move the 'tmp' directory files to 'uploads'
		// 1. read all the files at temporary directory
		$tmp_files = $fs->dirlist($source_path);
		
		if ( !empty($tmp_files)) {

			// 2. create the destination directory('uploads') if dir is not existing
			$fs->mkdir($destination_path);

			if ( $fs->is_dir($destination_path)) {

				foreach( $tmp_files as $file ) {
					//The file from tmp dir
					$file_source = $source_path . $file['name'];
					// Get the extension
					$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
					// Generate new name which also serves as new destination path
					$destination_path_source = $destination_path . md5(time().$file['name']) .'.'.$ext;
					// 3. move all files from temporary directory to uploads via 'copy'
					$fs->copy($file_source, $destination_path_source);
				}
				// Delete the temporary directory.
				$fs->rmdir($source_path, $recursive = true);

			}else {
				return false;
			}
			
		} else {
			return false;
		}
		
		return false;

	}

	/**
	 * Returns the current logged-in user file path.
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
	 * Our class destruct mechanism. Since we set the directory path on object creation. We need to revert it back
	 * to default WordPress directory path to prevent any bugs.
	 *
	 * @return void.
	 */
	public function __destruct() {
		if ( $this->set_upload_dir ) {
			remove_filter( 'upload_dir', array( $this, 'set_upload_dir' ) );
		}
	}
}
