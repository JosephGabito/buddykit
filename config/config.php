<?php
/**
 * This file is part of the Buddykit WordPress Plugin package.
 *
 * (c) Dunhakdis SC. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package buddykit/config
 */

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

//@see https://codex.wordpress.org/Editing_wp-config.php
if ( !defined('FS_CHMOD_DIR') ) {
    define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
}
/**
 * Main configuration.
 * @return array the main configuration
 */
function buddykit_config() {
    $max_file_size_default = 10000000; //10MB
	return array(
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'rest_upload_uri' => get_rest_url( null, 'buddykit/v1/', 'rest'),
        'file_list_container_id' => 'buddykit-filelist',
        'current_user_id' => get_current_user_id(),
        'i18' =>array(
                'temporary_file_delete_item_message' => __('Are you sure you want to delete this?', 'buddykit'),
            ),
        'config' => array(
            'upload_form_container' => apply_filters('buddykit_config_upload_form_container', 'whats-new-form'),
            'max_upload_size' => apply_filters('buddykit_config_max_upload_size', $max_file_size_default),
        ),
    );
}
