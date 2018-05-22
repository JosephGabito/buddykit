<?php
add_action('admin_init', 'buddykit_settings_init');

add_action('admin_menu', 'buddykit_settings_menu' );

// Add 'BuddyKit' settings under 'Settings'
function buddykit_settings_menu() {

	add_options_page( 
		'Buddykit Settings',
		'Buddykit',
		'manage_options',
		'buddykit-settings.php',
		'buddykit_settings_page_view'
	);
}

function buddykit_settings_init() {

	register_setting(
		'buddykit-settings.php',
		'buddykit_settings',
		array(
            'type' => 'string', 
            'sanitize_callback' => 'buddykit_settings_sanitize_callback',
        )
	);

	add_settings_section(
		'buddykit_section_media',
		'Activity Media',
		'buddykit_section_media_view',
		'buddykit-settings.php'
	);

	add_settings_field(
	    'buddykit_field_max_image_size', // The field ID
	    'Max Image Size', // The label
	    'buddykit_field_max_image_size_view', // Callback view
	    'buddykit-settings.php', //Under what settings?
	    'buddykit_section_media', //Section,
	   	[
 			'label_for' => 'buddykit_field_max_image_size',
 			'class' => 'buddykit_field_max_image_size_row'
 		]
	);
}

function buddykit_settings_sanitize_callback($params) {
	if ( empty( $params['buddykit_field_max_image_size'] ) ) {
		add_settings_error('error-max-image-size', 'settings_updated', $message='shit', $type='error');
		exit;
	}
	$options = array(
		'buddykit_field_max_image_size' => sanitize_text_field($params['buddykit_field_max_image_size']),
	);
	return $options;
}

function buddykit_settings_page_view() {
	$active_tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
    if ( empty ( $active_tab ) ) {
    	$active_tab = 'activity-media'; //default
    }
	?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e('Buddykit Settings', 'buddykit'); ?>
	</h1>
	<h2 class="nav-tab-wrapper">
                <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
                <a href="?page=buddykit-settings.php&tab=activity-media" class="nav-tab <?php if($active_tab == 'activity-media'){echo 'nav-tab-active';} ?> ">
                	<?php _e('Activity Media', 'buddykit'); ?>
                </a>
                <a href="?page=buddykit-settings.php&tab=ads-options" class="nav-tab <?php if($active_tab == 'ads-options'){echo 'nav-tab-active';} ?>">
                	<?php _e('Live Notifications (Coming Soon)', 'buddykit'); ?>
                </a>
            </h2>

	<?php
	?>
	<form method="POST" action="options.php">
	<?php 
		settings_fields( 'buddykit-settings.php' );
		do_settings_sections( 'buddykit-settings.php' );
		submit_button();
	?>
	</form>
	<?php
	echo '</div>';
}
function buddykit_section_media_view($args) {
	echo 'All settings related activity media';
}

function buddykit_field_max_image_size_view($args) {
	$option = get_option('buddykit_settings'); 
	?>
		<input id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		type="text" name="buddykit_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
		value="<?php echo $option[$args['label_for']]; ?>" 
		/>
	<?php
}