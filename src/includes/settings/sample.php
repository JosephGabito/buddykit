<?php
add_action('init', function(){

	if ( is_admin() ) {
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
		'id' => 'text-field',
		'title' => 'Text Field',
		'page' => 'my-options',
		'section' => 'pusher_settings',
		'default' => '1 2 3 four five',
		'description' => 'Test',
		'attributes' => array(
				'size' => 50,
				'required' => true
			)
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

	$settings->addField(array(
		'id' => 'as3dasd',
		'type' => 'select',
		'title' => 'Select',
		'page' => 'my-options',
		'section' => 'pusher_settings',
		'default' => 'option-2',
		'description' => 'Select Field',
		'options' => array(
				'option-1' => 'Options 1',
				'option-2' => 'Options 2',
				'option-3' => 'Options 3',
			)
		
	));

	$settings->addField(array(
		'id' => 'as3dasd2',
		'type' => 'select',
		'title' => 'Select 2',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 'option-2',
		'description' => 'Select the default size of the tshirt',
		'options' => array(
				'option-1' => 'Options 1',
				'option-2' => 'Options 2',
				'option-3' => 'Options 3',
			)
		
	));

	$settings->addField(array(
		'id' => 'as3dassdasd2',
		'type' => 'multiselect',
		'title' => 'Multiselect',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 'option-2',
		'description' => 'Select the default size of the tshirt',
		'options' => array(
				'option-1' => 'Options 1',
				'option-2' => 'Options 2',
				'option-3' => 'Options 3',
			)
	));

	$settings->addField(array(
		'id' => 'as3d1sasd2',
		'type' => 'colorpicker',
		'title' => 'Color Picker',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '#992211',
		'description' => 'Choose color',
	));

	$settings->addField(array(
		'id' => 'slider-field',
		'type' => 'range',
		'title' => 'Range Field',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 500,
		'description' => 'Select a range',
		'attributes' => array(
				'min' => 250,
				'max' => 2000,
			)
	));

	$settings->addField(array(
		'id' => 'email_address',
		'type' => 'email',
		'title' => 'Email Address',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '',
		'description' => 'Enter Email Address',
	));

	$settings->addField(array(
		'id' => 'password',
		'type' => 'password',
		'title' => 'Password',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '',
		'description' => 'Password should be 256 characters long and should end with emoji.',
	));

	$settings->addField(array(
		'id' => 'image-upload',
		'type' => 'image-upload',
		'title' => 'Image Upload',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '',
		'description' => 'Image Upload.',
	));

	$settings->addField(array(
		'id' => 'asd231sd22s',
		'type' => 'radio',
		'title' => 'Radio Selection',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 'option-2',
		'description' => 'Radio Field',
		'options' => array(
				'option-1' => 'Options 1',
				'option-2' => 'Options 2',
				'option-3' => 'Options 3',
			),
		
	));


	$settings->addField(array(
		'id' => 'asdasd23',
		'type' => 'checkbox',
		'title' => 'Checkbox',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 'option-2',
		'description' => 'Radio Field',
		'options' => array(
				'option-1' => 'Anyone on the same group as me',
				'option-2' => 'New task created',
				'option-3' => 'For Fun',
				'option-4' => 'Science',
				'option-5' => 'Technology',
				'option-6' => 'Education',
			)
		
	));

	$settings->addField(array(
		'id' => 'url_type',
		'type' => 'url',
		'title' => 'Field URL',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => 'http://google.com',
		'description' => 'URL Field',
		
	));

	$settings->addField(array(
		'id' => 'number_type',
		'type' => 'number',
		'title' => 'Field Number',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '1',
		'description' => 'Number Field',
		'attributes' => array(
				'step' => 0.1
			)
		
	));

	$settings->addField(array(
		'id' => 'wysiwyg_type',
		'type' => 'wysiwyg',
		'title' => 'WYSIWYG',
		'page' => 'my-options',
		'section' => 'pusher_credits',
		'default' => '1',
		'description' => 'WYSIWYG Field (WP Editor) Fied',
		'wysiwyg_attributes' => array(
				'editor_height' => 123
			)
	));

	$settings->initialize();

	}
}, 10);


 