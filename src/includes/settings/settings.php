<?php
class OptionType {

	public function menu( $args = array() ) 
	{

		$defaults = array(
			'page_title' => 'OptionType Default Page Title',
			'menu_title' => 'OptionType Default Menu Title',
			'capability' => 'manage_options',
			'menu_slug' => 'option-type-menu',
			'callback' => array( $this, 'content'),
			'icon_url' => 'http://thrive.dsc/wp-content/uploads/2018/08/cat.png',
			'position' => 20
		);

		$args = wp_parse_args( $args, $defaults );
		
		add_menu_page( 
			$args['page_title'],$args['menu_title'], $args['capability'], 
			$args['menu_slug'],$args['callback'],$args['icon_url'], $args['position']
		);
	}

	public function submenu( $args = array() ) 
	{return;
		$defaults = array(
				'parent_slug' => 'options-general.php',
				'page_title' => 'My Options',
				'menu_title' => 'My Options',
				'capability' => 'manage_options',
				'menu_slug' => 'my-options',
				'function' => $this->content(),
			);

		$args = wp_parse_args( $args, $defaults );

		add_submenu_page(
			$args['parent_slug'], $args['page_title'], $args['menu_title'], 
			$args['capability'], $args['menu_slug'], $args['function']
		);
			
	}

	public function content() 
	{
		// must check that the user has the required capability.
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

	    ?>
	    <div class="wrap">
	    	<h1 class="wp-heading-inline">Cat Options</h1>
	    </div>
	    <?php

	}

	public function section() 
	{

	}

	public function field() {

	}

}

//----//----//----//----//----//----//----//----//----

add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {

	$settings = new OptionType();
	
	$settings->menu( array(
			'page_title' => 'Page Title',
			'menu_title' => 'Cat Options',
			'capability' => 'manage_options',
			'menu_slug' => 'page-title',
		));

	$settings->submenu(array(
			'parent_slug' => 'page-title',
			'page_title' => 'Submenu Page Title',
			'menu_title' => 'Submenu Page Title',
			'capability' => 'manage_options',
			'menu_slug' => 'menu_slug',
		));
}

/**
 * Usage:
 *
 * add_action('admin_menu', 'my_plugin_menu');
 *
 * function my_plugin_menu() {
 * 
 * 	$x = new BuddyKitSettings();
 *
 * 	$x->createOption(array(
 * 			'type' => 'main',
 * 			'label' => esc_html__('BuddyKit', 'buddykit')
 * 	));
 * 
 * 	$x->addSection(array(
 * 			'id' => 'users',
 * 			'class' => '',
 * 			'label' => esc_html__('Users', 'buddykit')
 * 	  ));
 *
 * 	$x->addField(array(
 * 	    'id' => 'field-id',
 * 	    'class' => 'field-class',
 * 	    'type' => 'text',
 * 	    'label' => esc_html__('Default Avatar', 'buddykit')
 * 	    'description' => esc_html__('Choose the default avatar', 'buddykit')
 * 	    'default' => 3,
 * 	  ));
 * }
 * 
 */