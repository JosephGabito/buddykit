<?php
/**
 * Plugin Name: BuddyKit
 * Description: Additional features for your BuddyPress site when defaults are not enough.
 * Plugin URI: https://buddykit.io/
 * Author: Dunhakdis, Joseph G.
 * Author URI: https://dunhakdis.com/
 * Version: 0.1
 * Text Domain: buddykit
 * License: GPLv3
 * Domain Path: /src/languages
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BUDDYKIT_PATH', trailingslashit( plugin_dir_path(__FILE__) ));

define( 'BUDDYKIT_PUBLIC_URI', trailingslashit( plugin_dir_url( __FILE__ ) . '/src/public/'  ));

require_once BUDDYKIT_PATH . 'config/config.php';

require_once BUDDYKIT_PATH . 'src/install.php';

require_once BUDDYKIT_PATH . 'src/includes/media/admin-options/admin-options.php';

require_once BUDDYKIT_PATH . 'src/includes/media/class-media.php';
