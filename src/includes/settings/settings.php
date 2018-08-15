<?php
class OptionKit {

	var $frameworkName = "OptionKit";
	
	var $version = 0.1;

	var $menu = array();

	var $submenu = array();

	var $sections = array();

	var $fields = array();

	var $identifer = "";

	public function __construct( $identifer ) {
		$this->identifer = $identifer;
	}

	public function initialize() 
	{
		add_action('admin_menu', array($this, 'createOptionPage'));
		add_action('admin_init', array($this, 'createOptionFields') );
	}

	function createOptionPage() {

		// Add main menu
		if ( ! empty( $this->menu ) ): 
			add_menu_page( 
				$this->menu['page_title'],
				$this->menu['menu_title'], 
				$this->menu['capability'], 
				$this->menu['menu_slug'],
				$this->menu['callback'],
				$this->menu['icon_url'], 
				$this->menu['position']
			);
		endif;

		//Submenu.
		if ( ! empty( $this->submenu ) ):
			add_submenu_page(
				$this->submenu['parent_slug'], 
				$this->submenu['page_title'], 
				$this->submenu['menu_title'], 
				$this->submenu['capability'], 
				$this->submenu['menu_slug'], 
				$this->submenu['function']
			);
		endif;

		

		return;
	}

	public function createOptionFields(){

		register_setting( $this->identifer, 'eg_setting_name' );
		
		foreach ( $this->sections as $section ) {
			add_settings_section(
				$section['id'],
				$section['label'],
				$section['callback'],
				$section['page']
			);
		}
		
	}

	public function menu( $args = array() ) 
	{

		$defaults = array(
			'page_title' => 'OptionKit Default Page Title',
			'menu_title' => 'OptionKit Default Menu Title',
			'capability' => 'manage_options',
			'menu_slug' => '',
			'callback' => array( $this, 'content'),
			'icon_url' => '',
			'position' => 20
		);

		$this->menu = wp_parse_args( $args, $defaults );

		if ( empty( $args['menu_slug'] ) ) {
			wp_die( $this->frameworkName . ' Error: \'menu_slug\' is empty or not defined.');
		}
		
		return $this->menu;
	}

	public function submenu( $args = array() ) 
	{
		$defaults = array(
				'parent_slug' => 'options-general.php',
				'page_title' => 'My Options',
				'menu_title' => 'My Options',
				'capability' => 'manage_options',
				'menu_slug' => 'my-options',
				'function' => array( $this, 'content' ),
			);

		$this->submenu = wp_parse_args( $args, $defaults );

		return $this->submenu;
			
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
	    	<?php settings_fields( 'cat-options' ); ?>
	    	<?php  
	    		do_settings_sections( 'cat-options' );
 				// output save settings button
 				submit_button( 'Save Settings' ) 
 			?>
	    </div>
	    <?php

	}

	public function sectionCallback( $section )
	{
		foreach( $this->sections as $registered_section ) {
			if ( $registered_section['id'] === $section['id']) {
				if ( isset( $registered_section['desc'] ) ) {
					echo wp_kses_post( $registered_section['desc'] );
				}
				break;
			}
		}
	}

	public function addSection( $args ) 
	{

		$defaults = array(
			'description' => '',
			'callback' => array( $this, 'sectionCallback' )
		);

		$this->sections[] = wp_parse_args( $args, $defaults );

		return $this->sections;
		
	}

	public function addField() {
		add_settings_field(
			'eg_setting_name',
			'Example setting Name',
			'eg_setting_callback_function',
			'asdasd',
			'eg_setting_section'
		);
	}

}
