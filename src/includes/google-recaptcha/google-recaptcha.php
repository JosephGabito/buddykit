<?php
function buddykit_honeypot_get_decoy_field_id () {
	
	global $wpdb;

	$decoy_id = 0;
	$stmt = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}bp_xprofile_fields ORDER BY id DESC LIMIT 0, %d", 1);
	$result = $wpdb->get_row($stmt, OBJECT);
	
	if ( ! empty( $result->id ) ) {
		$decoy_id = (int)$result->id;
		$decoy_id ++;
	}
	
	return $decoy_id;

}

add_action('wp_enqueue_scripts', function(){
	wp_register_script('g-recaptcha', 'https://www.google.com/recaptcha/api.js');
});

add_action('bp_before_registration_submit_buttons', function(){
	global $bp;
	$option_captcha  = get_option('buddykit-security-recaptcha-is-enabled', array());
	if ( ! in_array( 'enabled', $option_captcha ) ) {
		return;
	}
	?>
	<?php wp_enqueue_script('g-recaptcha'); ?>
	<?php $recaptcha_errors = ''; ?>
	<?php if ( isset( $bp->signup->errors['bp_g-recaptcha_errors'] ) ) { ?>
		<?php $recaptcha_errors = $bp->signup->errors['bp_g-recaptcha_errors']; ?>
	<?php } ?>
	<?php if ( ! empty( $recaptcha_errors ) ): ?>
	<div class="bp-messages bp-feedback error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php echo esc_html( $recaptcha_errors ); ?></p>
	</div>
	<?php endif; ?>
	<div class="g-recaptcha" data-sitekey="<?php echo apply_filters( 'buddykit-security-recaptcha-key', get_option('buddykit-security-recaptcha-key', '') ); ?>"></div>
	<?php
});

add_action('bp_core_validate_user_signup', function( $result ){

	global $bp;

	$captcha  = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING);
	$honeypot = filter_input(INPUT_POST, 'field_' . buddykit_honeypot_get_decoy_field_id() , FILTER_SANITIZE_STRING);

	$option_captcha  = (array)get_option('buddykit-security-recaptcha-is-enabled', array());
	$option_honeypot = (array)get_option('buddykit-security-honey-pot-enabled', array());

	if ( in_array( 'enabled', $option_captcha ) ) {
		if ( empty( $captcha ) ) {
			$bp->signup->errors['bp_g-recaptcha_errors'] = __('Invalid Captcha Input', 'optionkit');
		}
	}

	if ( in_array( 'enabled', $option_honeypot ) ) {
		// Honeypot.
		if ( ! empty ( $honeypot ) ) {
			wp_die( __( 'Security Error: Spammer detected.', 'buddykit') );
		}
	}
	return $result;
});

add_action('xprofile_template_loop_start', function(){

	$option_honeypot = (array)get_option('buddykit-security-honey-pot-enabled', array());

	if ( ! in_array( 'enabled', $option_honeypot ) ) {
		return;
	}
	
	$decoy_id = buddykit_honeypot_get_decoy_field_id(); ?>
	<style>
		<?php echo '.editfield.field_' . absint( $decoy_id ); ?> { height: 0px;  overflow: hidden; z-index: -20; position: absolute; }
	</style>
	<div class="editfield field_<?php echo absint( $decoy_id ); ?> field_name visibility-public alt field_type_textbox">
	<fieldset>
		<legend id="field_1-<?php echo absint( $decoy_id ); ?>">
			<?php esc_html_e('Name', 'buddykit'); ?>
			<span class="bp-required-field-label">
				<?php esc_html_e('(required)', 'buddykit'); ?>
			</span>
		</legend>
		<input id="field_<?php echo absint( $decoy_id ); ?>" name="field_<?php echo absint( $decoy_id ); ?>" type="text" value="" aria-labelledby="field_<?php echo absint( $decoy_id ); ?>-<?php echo absint( $decoy_id ); ?>">
	</fieldset>
	</div>
<?php
});

add_action('init', function(){

	$settings = optionkit();

	// Creates a submenu inside our newly created top level menu.
	$settings->submenu( array(
		'parent_slug' => 'buddykit-main-option',
		'menu_title' => __('Security', 'buddykit'),
		'menu_slug' => 'buddykit-security',
	));

	// Create activity media settings.
	$settings->addSection(array(
		'id' => 'buddykit-security',
		'label' => __('Registration Captcha', 'buddykit'),
		'desc' => __('Safeguard and protect your BuddyPress site against spam bots.', 'buddykit'),
		'page' => 'buddykit-security'
	));
	// Enable/Disable Google ReCaptcha Key.
	$settings->addField(array(
		'id' => 'buddykit-security-recaptcha-is-enabled',
		'title' => __('Enable Google ReCaptcha Key','buddykit'),
		'page' => 'buddykit-security',
		'section' => 'buddykit-security',
		'default' => array(),
		'description' => __('Enable or disable the Google ReCaptcha functionality. Check to enable, uncheck to disable', 'buddykit'),
		'type' => 'checkbox',
		'options' => array(
			'enabled' => __('Enabled', 'buddykit')
		),
		'attributes' => array(
				'size' => 60,
				'required' => true,
				'placeholder' => __('e.g. 6Lf_8TMUACABAF6MZ2nlpmnS0eKDeb2nhKeqErYW', 'buddykit')
			)
	));
	// Google ReCaptcha Key.
	$settings->addField(array(
		'id' => 'buddykit-security-recaptcha-key',
		'title' => __('Google ReCaptcha Key','buddykit'),
		'page' => 'buddykit-security',
		'section' => 'buddykit-security',
		'default' => '',
		'description' => sprintf( __('Enter your Google ReCaptcha Key. %s to get your own ReCaptcha Key', 'buddykit'), '<a target="__blank" href="https://www.google.com/recaptcha">'.__('Click here', 'buddykit').'</a>' ),
		'attributes' => array(
				'size' => 60,
				'required' => true,
				'placeholder' => __('e.g. 6Lf_8TMUACABAF6MZ2nlpmnS0eKDeb2nhKeqErYW', 'buddykit')
			)
	));
	// Honeypot
	$settings->addField(array(
		'id' => 'buddykit-security-honey-pot-enabled',
		'title' => __('Enable Honeypot','buddykit'),
		'page' => 'buddykit-security',
		'section' => 'buddykit-security',
		'default' => array(),
		'description' => __('Honeypots are invisible fields that are automatically added to your registration form<br/> to prevent spam bots from creating accounts in your website', 'buddykit'),
		'type' => 'checkbox',
		'options' => array(
			'enabled' => __('Enabled', 'buddykit')
		),
		'attributes' => array(
				'size' => 60,
				'required' => true,
				'placeholder' => __('e.g. 6Lf_8TMUACABAF6MZ2nlpmnS0eKDeb2nhKeqErYW', 'buddykit')
			)
	));
});
