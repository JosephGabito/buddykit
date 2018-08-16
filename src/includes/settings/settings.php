<?php
namespace OptionKit;
use OptionKit;

class MenuFields {

	var $frameworkName = "OptionKit";
	
	var $version = 0.1;

	var $menu = array();

	var $submenus = array();

	var $sections = array();

	var $fields = array();

	var $title = '';

	var $identifer = "";

	public function __construct( $title, $identifer ) {
		$this->identifer = $identifer;
		$this->title = $title;
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
		if ( ! empty( $this->submenus ) ):
			foreach ( $this->submenus as $submenu ):
				add_submenu_page(
					$submenu['parent_slug'], 
					$submenu['page_title'], 
					$submenu['menu_title'], 
					$submenu['capability'], 
					$submenu['menu_slug'], 
					$submenu['function']
				);
			endforeach;
		endif;

		return;
	}

	public function createOptionFields(){

		// All of settings sections.
		foreach ( $this->sections as $section ) {

			add_settings_section(
				$section['id'],
				$section['label'],
				$section['callback'],
				$section['page']
			);

		}
		// Add all section fields.
		foreach ( $this->fields as $field ) {
			add_settings_field(
				$field['id'],
				$field['title'],
				$field['callback'],
				$field['page'],
				$field['section'],
				$field['args']
			);

			register_setting( $this->identifer, $field['id'] );

		}
	}

	public function menu( $args = array() ) 
	{

		$defaults = array(
			'page_title' => '',
			'menu_title' => 'OptionKit Default Menu Title',
			'capability' => 'manage_options',
			'menu_slug' => '',
			'callback' => array( $this, 'wrap'),
			'icon_url' => '',
			'position' => 80
		);

		$menu = wp_parse_args( $args, $defaults );

		if ( empty( $menu['page_title'] ) ) {
			$menu['page_title'] = $menu['menu_title'];
		}

		$this->menu = $menu;

		if ( empty( $args['menu_slug'] ) ) {
			wp_die( $this->frameworkName . ' Error: \'menu_slug\' is empty or not defined.');
		}
		
		return $this->menu;
	}

	/**
	 * Registers a submenu.
	 * @param  array  $args The arguments.
	 * @return array  The submenu properties.
	 */
	public function submenu( $args = array() ) 
	{
		$defaults = array(
			'parent_slug' => 'options-general.php',
			'page_title' => '',
			'menu_title' => 'My Options',
			'capability' => 'manage_options',
			'menu_slug' => '',
			'function' => array( $this, 'wrap' ),
		);

		$submenu = wp_parse_args( $args, $defaults );

		if ( empty( $submenu['page_title'] ) ) {
			$submenu['page_title'] = $submenu['menu_title'];
		}

		$this->submenus[] = $submenu;

		return $this->submenus;
			
	}

	public function wrap() {
		$this->content();
	}

	protected function content() 
	{
		// must check that the user has the required capability.
	    if (!current_user_can('manage_options'))
	    {
	      wp_die( __('You do not have sufficient permissions to access this page.') );
	    }

	    ?>
	    <div class="wrap">
	    	<?php
	    	// check if the user have submitted the settings
			// wordpress will add the "settings-updated" $_GET parameter to the url
			if ( isset( $_GET['settings-updated'] ) ) {
				// add settings saved message with the class of "updated"
				add_settings_error( 'settings_message', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
			}
			 
			// show error/update messages
			settings_errors( 'settings_message' );
	    	?>
	    	<h1 class="wp-heading-inline">
	    		<?php echo wp_kses_post( $this->getPageTitle() ); ?>
	    	</h1>

	    	<form action="options.php" method="post">
	    		<?php settings_fields( $this->identifer ); ?>
	    		<?php do_settings_sections( $_GET['page'] ); ?>
				<?php submit_button( 'Save Settings' ); ?>
	    	</form>
	    	
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

	public function fieldCallback( $args ) {
		switch ( $args ):
			default:
				require_once BUDDYKIT_PATH . 'src/includes/settings/field-types/text.php';
				$field = new OptionKit\FieldTypes\Text($args);
				$field->display();
			break;
		endswitch;
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

	public function addField( $args ) {

		$defaults = array(
			'callback' => array( $this, 'fieldCallback' ),
		);

		$args_defaults = array(
			'id' => $args['id'],
			'name' => $args['id'],
			'default' => $args['default'],
			'class' => 'optionkit-field'
		);

		$this->fields[] = wp_parse_args( array(
				'id' => $args['id'],
				'title' => $args['title'],
				'page' => $args['page'],
				'section' => $args['section'],
				'args' => wp_parse_args( $args['args'], $args_defaults ),
			), $defaults);

		return $this->fields;
	}

	protected function getPageTitle() {
		
		return get_admin_page_title();
		
	}

}
