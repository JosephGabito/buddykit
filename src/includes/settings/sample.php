<?php
//----//----//----//----//----//----//----//----//----

$settings = new OptionKit( 'your_unique_identifier' );

// Creates a top level menu in WordPress.
$settings->menu( array(
	'page_title' => 'Cat Options',
	'menu_title' => 'Cat Options',
	'menu_slug' => 'cat-options',
	'icon_url' => 'http://localhost/thrive/wp-content/uploads/2018/08/if_cat_285654.png'
));

// Creates a submenu inside our newly created top level menu.
$settings->submenu( array(
	'parent_slug' => $settings->menu['menu_slug'],
	'page_title' => 'Submenu Page Title',
	'menu_title' => 'Submenu Page Title',
));

$settings->addSection(array(
	'id' => 'eg_setting_section',
	'label' => 'Example settings section in reading',
	'desc' => '<p>A simple <strong>description</strong></p>',
	'page' => 'cat-options'
));

$settings->addSection(array(
	'id' => 'eg_setting_se2asdction',
	'label' => 'reading',
	'page' => 'cat-options'
));

//$settings->addField();
$unique_option_group = 'asdadsd';
$settings->initialize( $unique_option_group );

//----------//----------//----------//----------//----------//----------//----------//----------//----------
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 