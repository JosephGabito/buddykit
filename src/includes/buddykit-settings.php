<?php

add_action('init', 'buddykit_load_settings');

function buddykit_load_settings()
{
	// Bail out if not in wp-admin.
	if ( ! is_admin() ) {
		return;
	}
	
	$settings = new OptionKit\MenuFields( 
		esc_html__('BuddyKit Settings', 'optionkit'), 
		'buddykit_settings'
	);

	// Creates a top level menu in WordPress.
	$settings->menu( array(
		'menu_title' => __('BuddyKit', 'optionkit'),
		'menu_slug' => 'buddykit-main-option',
		'icon_url' => 'dashicons-groups',
	));


	// Start the script.
	$settings->register();

}