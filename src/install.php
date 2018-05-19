<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $buddykit_db_version;
global $wpdb;

register_activation_hook( __FILE__, 'buddykit_install' );
register_activation_hook( __FILE__, 'buddykit_install_data' );
add_action( 'plugins_loaded', 'buddykit_update_db_check' );

function buddykit_update_db_check() {
    global $buddykit_db_version;
    if ( get_site_option( 'buddykit_db_version' ) != $buddykit_db_version ) {
        buddykit_install();
    }
}

$buddykit_db_version = '0.0.4';

function buddykit_install() {

	global $wpdb;
	
	global $buddykit_db_version;

	$installed_ver = get_option( "buddykit_db_version" );

	if ( $installed_ver != $buddykit_db_version ) {

		$table_name = $wpdb->prefix . 'buddykit_user_files';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id 	mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9),
			name tinytext NOT NULL,
			type varchar(55) NOT NULL,
			is_tmp smallint(1) NOT NULL,
			last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		add_option( 'buddykit_db_version', $buddykit_db_version );
	}

}
