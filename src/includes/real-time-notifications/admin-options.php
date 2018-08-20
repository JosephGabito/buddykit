<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action('init', function(){

	if ( ! is_admin() ) {
		return;
	}

	$settings = optionkit();

	$old_settings = get_option('buddykit_settings', array(
			'buddykit_rtn_is_enabled' => 1,
			'buddykit_rtn_pusher_key' => '',
			'buddykit_rtn_pusher_app_id' => '',
			'buddykit_rtn_pusher_secret' => '',
			'buddykit_rtn_pusher_cluster' => '',
		));

	// Submenu.
	$settings->subMenu(array(
		'menu_title' => __('Notifications', 'buddykit'),
		'menu_slug' => 'buddykit-notifications',
		'parent_slug' => 'buddykit-main-option',
		'position' => 100
	));

	// Section.
	$settings->addSection(
		array(
			'page' => 'buddykit-notifications',
			'id' => 'buddykit-live-notifications',
			'label' => __('Live Notifications', 'buddykit'),
			'desc' => sprintf( 
				__( 'BuddyKit uses Pusher to serve your real-time notifications. %s and take note of your "app_id", "key", "secret", and "cluster"', 'buddykit'), 
				'<a href="https://dashboard.pusher.com/accounts/sign_up">'.__('<br/>Create your Pusher account', 'buddykit').'</a>'
				),
		)
	);

	// Pusher APP ID.
	$settings->addField(
		array(
			'page' => 'buddykit-notifications',
			'id' => 'buddykit-rtn-pusher-app-id',
			'section' => 'buddykit-live-notifications',
			'title' => __('Pusher APP ID', 'buddykit'),
			'type' => 'text',
			'default' => $old_settings['buddykit_rtn_pusher_key'],
			'description' => __('Enter Your Pusher APP ID', 'buddykit'),
			'attributes' => array(
					'placeholder' => 'e.g. 147974'
				)
		)
	);

	// Pusher Key.
	$settings->addField(
		array(
			'page' => 'buddykit-notifications',
			'id' => 'buddykit-rtn-pusher-key',
			'section' => 'buddykit-live-notifications',
			'title' => __('Pusher Key', 'buddykit'),
			'type' => 'text',
			'default' => $old_settings['buddykit_rtn_pusher_key'],
			'description' => __('Enter Your Pusher APP Key', 'buddykit'),
			'attributes' => array(
					'placeholder' => 'e.g. 61d3abc0df34615dffe2'
				)
		)
	);

	// Pusher Secret.
	$settings->addField(
		array(
			'page' => 'buddykit-notifications',
			'id' => 'buddykit-rtn-pusher-secret',
			'section' => 'buddykit-live-notifications',
			'title' => __('Pusher Secret', 'buddykit'),
			'type' => 'text',
			'default' => $old_settings['buddykit_rtn_pusher_secret'],
			'description' => __('Enter Your Pusher APP Secret', 'buddykit'),
			'attributes' => array(
					'placeholder' => 'e.g. b16s4e8b259447dff2d4'
				)
		)
	);

	// Pusher Cluster.
	$settings->addField(
		array(
			'page' => 'buddykit-notifications',
			'id' => 'buddykit-rtn-pusher-cluster',
			'section' => 'buddykit-live-notifications',
			'title' => __('Pusher Cluster', 'buddykit'),
			'type' => 'text',
			'default' => $old_settings['buddykit_rtn_pusher_cluster'],
			'description' => __('Enter Your Pusher APP Cluster', 'buddykit'),
			'attributes' => array(
					'placeholder' => 'e.g. mt1'
				)
		)
	);

	$settings->register();

});


