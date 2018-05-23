<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action('admin_init', 'buddykit_settings_init');

add_action('admin_menu', 'buddykit_settings_menu' );

function buddykit_settings_page_view() {
	$active_tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
    if ( empty ( $active_tab ) ) {
    	$active_tab = 'activity-media'; //default
    }
	?>
	<div class="wrap">
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
	</div>
	<?php
	
}
function buddykit_section_media_view($args) {
	echo 'All settings related in activity media. Invalid inputs will be reverted to safe default values.';
}
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

	$config_default = buddykit_config_settings_default();

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

	/**
	 * Maximum Image Size
	 */
	add_settings_field(
	    'buddykit_field_max_image_size', // The field ID
	    'Max Image Size', // The label
	    'buddykit_field_size_view', // Callback view
	    'buddykit-settings.php', //Under what settings?
	    'buddykit_section_media', //Section,
	   	[
 			'label_for' => 'buddykit_field_max_image_size',
 			'class' => 'buddykit_field_max_image_size_row',
 			'default' => $config_default['buddykit_field_max_image_size'],
 			'description' => esc_html__('The allowed maximum size of the image in MB. 
 				Value must be an integer.', 'buddykit')
 		]
	);

	/**
	 * Maximum Video Size
	 */
	add_settings_field(
	    'buddykit_field_max_image_number', // The field ID
	    'Max Images Number', // The label
	    'buddykit_field_size_view', // Callback view
	    'buddykit-settings.php', //Under what settings?
	    'buddykit_section_media', //Section,
	   	[
 			'label_for' => 'buddykit_field_max_image_number',
 			'class' => 'buddykit_field_max_video_size_row',
 			'default' => $config_default['buddykit_field_max_image_number'],
 			'description' => esc_html__('Limits the maximum number of images per activity post.', 'buddykit')
 		]
	);

	/**
	 * Upload Button Label
	 */
	add_settings_field(
	    'buddykit_field_upload_button_label', // The field ID
	    'Upload Button Label', // The label
	    'buddykit_field_upload_button_label_view', // Callback view
	    'buddykit-settings.php', // Under what settings?
	    'buddykit_section_media', //Section,
		[
 			'label_for' => 'buddykit_field_upload_button_label',
 			'class' => 'buddykit_field_upload_button_label_row',
 			'default' => $config_default['buddykit_field_upload_button_label'],
 			'description' => __('The label of the button inside the activity stream', 'buddykit')
 		]
	);
}

function buddykit_settings_sanitize_callback($params) {
	
	$config_default = buddykit_config_settings_default();

	$prev_options = get_option( 'buddykit_settings' );
	// Maximum image size settings.
	$max_image_size = filter_var( $params['buddykit_field_max_image_size'], FILTER_VALIDATE_INT, array(
			'options' => array(
					'default' => $config_default['buddykit_field_max_image_size'],
					'min_range' => 1,
					'max_range' => intval( ini_get('post_max_size') )
				)
		));
	// Maximum number of images.
	$max_image_number = filter_var( $params['buddykit_field_max_image_number'], FILTER_VALIDATE_INT, array(
			'options' => array(
					'default' => $config_default['buddykit_field_max_image_number'],
					'min_range' => 1,
					'max_range' => intval( ini_get('post_max_size') )
				)
		));

	if ( ! empty( $errors ) ) {
		foreach( $errors as $error ) {
			add_settings_error( $error['id'], $error['code'], $error['message'], $error['type'] );
		}
	}

	$options = array(
		'buddykit_field_max_image_size' => sanitize_text_field( $max_image_size ),
		'buddykit_field_max_image_number' => sanitize_text_field( $max_image_number ),
		'buddykit_field_upload_button_label' => sanitize_text_field( $params['buddykit_field_upload_button_label'] ),
	);
	
	return $options;

}


function buddykit_field_size_view($args) {
	$option = get_option('buddykit_settings'); 
	?>
		<input id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		type="text" maxlength="<?php echo strlen(ini_get('post_max_size')) - 1; ?>" name="buddykit_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
		value="<?php echo !empty($option[$args['label_for']]) ? $option[$args['label_for']]: $args['default']; ?>" 
		/>
		<p class="description">
			<?php echo $args['description']; ?><br/>
			<?php esc_html_e('Maximum value can be set up to ', 'buddykit'); ?>
			<?php echo ini_get('post_max_size'); ?> (Post Max Size)
		</p>
	<?php
}

function buddykit_field_upload_button_label_view($args) {
	$option = get_option('buddykit_settings'); 
	$value = $args['default'];
	if ( ! empty( $option[$args['label_for']] ) ) {
		$value = $option[$args['label_for']];
	}
	?>
		<input id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		type="text" name="buddykit_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
		value="<?php echo $value; ?>" 
		/>
		<p class="description">
			<?php echo $args['description']; ?>
		</p>
	<?php
}