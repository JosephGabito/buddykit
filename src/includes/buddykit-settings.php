<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action('init', 'buddykit_load_settings');

function buddykit_load_settings()
{
	// Bail out if not in wp-admin.
	if ( ! is_admin() ) {
		return;
	}

	$settings = optionkit();

	// Creates a top level menu in WordPress.
	$settings->menu( array(
		'menu_title' => __('BuddyKit', 'optionkit'),
		'menu_slug' => 'buddykit-main-option',
		'icon_url' => 'dashicons-hammer',
	));

	$settings->addSection(array(
		'id' => 'buddykit-components',
		'label' => __('Components', 'optionkit'),
		'desc' => __('Enable or disable Buddykit Components.', 'optionkit'),
		'page' => 'buddykit-main-option'
	));

	$settings->addField(array(
		'id' => 'buddykit-components',
		'title' => __('Active Components','optionkit'),
		'page' => 'buddykit-main-option',
		'type' => 'checkbox',
		'section' => 'buddykit-components',
		'default' => array('activity-media', 'realtime-notifications'),
		'description' => __('Select which component ', 'optionkit'),
		'options' => array(
				'activity-media' => __('Activity Media', 'optionkit'),
				'realtime-notifications' => __('Realtime Notifications', 'optionkit'),
			)
	));

	// Register the settings.
	$settings->register();

}