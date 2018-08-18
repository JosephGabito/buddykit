<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

function optionkit() 
{
	require_once plugin_dir_path(__FILE__) . 'optionkit.php';
	return OptionKit\MenuFields::getInstance();
}