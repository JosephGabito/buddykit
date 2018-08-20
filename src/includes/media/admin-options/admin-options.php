<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action('init', function(){

	if ( ! is_admin() ) {
		return;
	}
	
	//Check if activity media is enabled or disabled.
	$components = (array) get_option( 'buddykit-components', array('activity-media', 'realtime-notifications') );

	if ( ! in_array('activity-media', $components ) ) {
		return;
	}
	
	$settings = optionkit();

	// Creates a submenu inside our newly created top level menu.
	$settings->submenu( array(
		'parent_slug' => 'buddykit-main-option',
		'menu_title' => __('Activity', 'optionkit'),
		'menu_slug' => 'buddykit-options'
	));


	// Create activity media settings.
	$settings->addSection(array(
		'id' => 'activity-media',
		'label' => __('Activity Media', 'optionkit'),
		'desc' => __('All settings related in activity media. Invalid inputs will be reverted to safe default values.', 'optionkit'),
		'page' => 'buddykit-options'
	));

	// Max Image Size.
	$settings->addField(array(
		'id' => 'buddykit-media-max-image-size',
		'title' => __('Max Image Size','optionkit'),
		'page' => 'buddykit-options',
		'section' => 'activity-media',
		'default' => '5',
		'description' => __('The allowed maximum size of the image in MB. Value must be an integer. <br/>Maximum value can be set up to 500M (Post max size)', 'optionkit'),
		'attributes' => array(
				'size' => 3,
				'required' => true
			)
	));
	// Max Video Size.
	$settings->addField(array(
		'id' => 'buddykit-media-max-video-size',
		'title' => __('Max Video Size','optionkit'),
		'page' => 'buddykit-options',
		'section' => 'activity-media',
		'default' => '5',
		'description' => __('The allowed maximum size of the video in MB. Value must be an integer. <br/>Maximum value can be set up to 500M (Post max size)','optionkit'),
		'attributes' => array(
				'size' => 3,
				'required' => true
			)
	));
	// Max Media Number.
	$settings->addField(array(
		'id' => 'buddykit-media-max-number',
		'title' => __('Max Media Number', 'optionkit'),
		'page' => 'buddykit-options',
		'section' => 'activity-media',
		'default' => '5',
		'description' => __('Limits the maximum number of medias per activity post', 'optionkit'),
		'attributes' => array(
				'size' => 3,
				'required' => true
			)
	));
	// Upload button label.
	$settings->addField(array(
		'id' => 'buddykit-media-upload-button-label',
		'title' => __('Upload Button Label', 'optionkit'),
		'page' => 'buddykit-options',
		'section' => 'activity-media',
		'default' => __('Upload Photos/Videos', 'optionkit'),
		'description' => __('The label of the button inside the activity stream', 'optionkit'),
		'attributes' => array(
				'size' => 25,
				'required' => true
			)
	));

	$settings->register();

});