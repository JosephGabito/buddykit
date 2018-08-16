<?php
//----//----//----//----//----//----//----//----//----
$settings = new OptionKit\MenuFields( 
	'Cat Settings', 
	'your_unique_identifier' 
);

// Creates a top level menu in WordPress.
$settings->menu( array(
	'menu_title' => 'Community',
	'menu_slug' => 'cat-options',
	'icon_url' => 'dashicons-groups',
));

// Creates a submenu inside our newly created top level menu.
$settings->submenu( array(
	'parent_slug' => $settings->menu['menu_slug'],
	'menu_title' => __('Live Notifications', 'my-textdomain'),
	'menu_slug' => 'my-options'
));

// Creates a submenu inside our newly created top level menu.
$settings->submenu( array(
	'parent_slug' => $settings->menu['menu_slug'],
	'menu_title' => 'Cat Temper',
	'menu_slug' => 'my-optionss'
));

// ---// ---// ---// ---// ---// ---

$settings->addSection(array(
	'id' => 'eg_setting_section',
	'label' => 'Example settings section in reading',
	'desc' => '<p>A simple <strong>description</strong></p>',
	'page' => 'cat-options'
));

$settings->addSection(array(
	'id' => 'pusher_credits',
	'label' => 'Pusher Credits',
	'page' => 'my-options'
));

$settings->addSection(array(
	'id' => 'pusher_settings',
	'label' => 'Pusher Settings',
	'page' => 'my-options'
));

$settings->addField(array(
	'id' => 'as3dd',
	'title' => 'Text Field',
	'page' => 'my-options',
	'section' => 'pusher_settings',
	'default' => '1 2 3 four five',
	'description' => 'Test'
));

$settings->addField(array(
	'id' => 'as3d',
	'type' => 'textarea',
	'title' => 'Text Field 2',
	'page' => 'my-options',
	'section' => 'pusher_settings',
	'default' => '1 2 3 four five six',
	'description' => 'Test2',
	'attributes' => array(
			'rows' => 6,
			'cols' => 100
		)
));

$unique_option_group = 'asdadsd';

$settings->initialize( $unique_option_group );

//----------//----------//----------//----------//----------//----------//----------//----------//----------
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 