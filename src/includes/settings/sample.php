<?php
//----//----//----//----//----//----//----//----//----

$settings = new OptionKit( 
	'Cat Settings', 
	'your_unique_identifier' 
);

// Creates a top level menu in WordPress.
$settings->menu( array(
	'menu_title' => 'BuddyKit',
	'menu_slug' => 'cat-options',
	'icon_url' => 'http://localhost/thrive/wp-content/uploads/2018/08/if_cat_285654.png'
));

// Creates a submenu inside our newly created top level menu.
$settings->submenu( array(
	'parent_slug' => $settings->menu['menu_slug'],
	'menu_title' => 'Live Notifications',
	'menu_slug' => 'my-options'
));

// Creates a submenu inside our newly created top level menu.
$settings->submenu( array(
	'parent_slug' => $settings->menu['menu_slug'],
	'page_title' => 'Cat Temper',
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



$unique_option_group = 'asdadsd';

$settings->initialize( $unique_option_group );

//----------//----------//----------//----------//----------//----------//----------//----------//----------
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 