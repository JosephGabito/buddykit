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
        	<?php _e('Live Notifications', 'buddykit'); ?>
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
	$active_tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
	if ( empty( $active_tab) || 'activity-media' == $active_tab ) {
		echo 'All settings related in activity media. Invalid inputs will be reverted to safe default values.';
	}
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
	
	$active_tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

	register_setting(
		'buddykit-settings.php',
		'buddykit_settings',
		array(
            'type' => 'string', 
            'sanitize_callback' => 'buddykit_settings_sanitize_callback',
        )
	);

	$tab_title = apply_filters('buddykit_admin_options_tab_title', __('Activity Media', 'buddykit'));


	switch( $active_tab ){
		case '':
		case 'activity-media':

			add_settings_section(
				'buddykit_section_media',
				$tab_title,
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
		 				Value must be an integer.', 'buddykit') . '<br/>' . esc_html('Maximum value can be set up to ', 'buddykit')
						. '<strong>' . ini_get( 'post_max_size') . '</strong>' .  
						' (Post max size)'
		 		]
			);

			/**
			 * Max Video Size
			 */
			add_settings_field(
				'buddykit_field_max_video_size', // The field ID
			    'Max Video Size', // The label
			    'buddykit_field_size_view', // Callback view
			    'buddykit-settings.php', //Under what settings?
			    'buddykit_section_media', //Section,
			   	[
		 			'label_for' => 'buddykit_field_max_video_size',
		 			'class' => 'buddykit_field_max_video_size_row',
		 			'default' => $config_default['buddykit_field_max_video_size'],
		 			'description' => esc_html__('The allowed maximum size of the video in MB. 
		 				Value must be an integer.', 'buddykit') . '<br/>' . esc_html('Maximum value can be set up to ', 'buddykit')
						. '<strong>' . ini_get( 'post_max_size') . '</strong>' .  
						' (Post max size)'
		 		]
			);

			/**
			 * Maximum Number of Media 
			 */
			add_settings_field(
			    'buddykit_field_max_image_number', // The field ID
			    'Max Media Number', // The label
			    'buddykit_field_size_view', // Callback view
			    'buddykit-settings.php', //Under what settings?
			    'buddykit_section_media', //Section,
			   	[
		 			'label_for' => 'buddykit_field_max_image_number',
		 			'class' => 'buddykit_field_max_video_size_row',
		 			'default' => $config_default['buddykit_field_max_image_number'],
		 			'description' => esc_html__('Limits the maximum number of medias per activity post.', 'buddykit')
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
		break;	

		default:
				do_action('buddykit_settings_tab_fields');
		break;
	}


	
}

function buddykit_settings_sanitize_callback($params) {
	
	$config_default = buddykit_config_settings_default();

	$prev_options = get_option( 'buddykit_settings' );

	$options = array();

	// Maximum image size settings.
	if ( isset( $params['buddykit_field_max_image_size'] ) ) {
		$max_image_size = filter_var( $params['buddykit_field_max_image_size'], FILTER_VALIDATE_INT, array(
			'options' => array(
					'min_range' => 1,
					'max_range' => intval( ini_get('post_max_size') )
				),
		));
		if ( ! $max_image_size ) {
			$errors[] = array(
					'id' => 'max_image_size_range_error',
					'code' => 'max_image_size_range_error',
					'message' => esc_html__('There was an error with your input for "Max Image Size". Reverting to default value.', 'buddykit'),
					'type' => 'error'
				);
			$max_image_size = $config_default['buddykit_field_max_image_size'];
		}
	
		$options['buddykit_field_max_image_size'] = sanitize_text_field( $max_image_size );
	}

	// Maximum size of videos.
	if ( isset( $params['buddykit_field_max_video_size'] ) ) {

		$max_video_size = filter_var( $params['buddykit_field_max_video_size'], FILTER_VALIDATE_INT, array(
			'options' => array(
					'min_range' => 1,
					'max_range' => intval( ini_get('post_max_size') )
				)
		));

		if ( ! $max_video_size ) {
			$errors[] = array(
					'id' => 'max_video_size_range_error',
					'code' => 'max_video_size_range_error',
					'message' => esc_html__('There was an error with your input for "Max Video Size". Reverting to default value.', 'buddykit'),
					'type' => 'error'
				);
			$max_video_size = $config_default['buddykit_field_max_video_size'];
		}

		$options['buddykit_field_max_video_size'] = sanitize_text_field( $max_video_size );
	}

	// Maximum number of images.
	if ( isset( $params['buddykit_field_max_image_number'] ) ) {

		$max_image_number = filter_var( $params['buddykit_field_max_image_number'], FILTER_VALIDATE_INT, array(
			'options' => array(
					'default' => $config_default['buddykit_field_max_image_number'],
					'min_range' => 1,
					'max_range' => apply_filters('buddykit_field_max_image_number_max_range', 125)
				)
		));
		$options['buddykit_field_max_image_number'] = sanitize_text_field( $max_image_number );
	}

	// Upload Label
	if ( isset( $params['buddykit_field_upload_button_label'] ) ) {
		$options['buddykit_field_upload_button_label'] = sanitize_text_field( $params['buddykit_field_upload_button_label'] );
	}

	// Enable/disable Live Notifications
	
	if ( isset( $params['buddykit_rtn_is_enabled']) ) {
		$options['buddykit_rtn_is_enabled'] = sanitize_text_field( $params['buddykit_rtn_is_enabled'] );
	} else {
		$options['buddykit_rtn_is_enabled'] = 0;
	}

	// Pusher Key.
	if ( isset( $params['buddykit_rtn_pusher_key']) ) {
		$options['buddykit_rtn_pusher_key'] = sanitize_text_field( $params['buddykit_rtn_pusher_key'] );
	}
	// Pusher App ID.
	if ( isset( $params['buddykit_rtn_pusher_app_id']) ) {
		$options['buddykit_rtn_pusher_app_id'] = sanitize_text_field( $params['buddykit_rtn_pusher_app_id'] );
	}
	// Pusher Secret.
	if ( isset( $params['buddykit_rtn_pusher_secret']) ) {
		$options['buddykit_rtn_pusher_secret'] = sanitize_text_field( $params['buddykit_rtn_pusher_secret'] );
	}
	// Pusher Cluster.
	if ( isset( $params['buddykit_rtn_pusher_cluster']) ) {
		$options['buddykit_rtn_pusher_cluster'] = sanitize_text_field( $params['buddykit_rtn_pusher_cluster'] );
	}

	if ( ! empty( $errors ) ) {
		foreach( $errors as $error ) {
			add_settings_error( $error['id'], $error['code'], $error['message'], $error['type'] );
		}
	}

	$__options = wp_parse_args($options, $prev_options);

	return $__options;

}


function buddykit_field_size_view($args) {
	$option = get_option('buddykit_settings'); 
	?>
		<input id="<?php echo esc_attr( $args['label_for'] ); ?>" 
		type="text" size="3" maxlength="<?php echo strlen(ini_get('post_max_size')) - 1; ?>" name="buddykit_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
		value="<?php echo !empty($option[$args['label_for']]) ? $option[$args['label_for']]: $args['default']; ?>" 
		/>
		<p class="description">
			<?php echo $args['description']; ?>
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