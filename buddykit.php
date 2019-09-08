<?php
/**
 * Plugin Name: BuddyKit
 * Description: BuddyKit adds several features like Live Notifications and Media Activities to your BuddyPress powered websites.
 * Plugin URI: https://buddykit.io/
 * Author: Dunhakdis, Joseph G.
 * Author URI: https://dunhakdis.com/
 * Version: 0.0.4
 * Text Domain: buddykit
 * License: GPLv3
 * Domain Path: /src/languages
 *
 * ------------------------------------------------------------
 *
 * This file is part of the Buddykit WordPress Plugin package.
 *
 * (c) Dunhakdis SC. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package buddykit/bootstrap
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'BUDDYKIT_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );

define( 'BUDDYKIT_PUBLIC_URI', trailingslashit( plugin_dir_url( __FILE__ ) . 'src/public/' ) );

require_once BUDDYKIT_PATH . 'config/config.php';

require_once BUDDYKIT_PATH . 'src/install.php';

require_once BUDDYKIT_PATH . 'src/includes/media/admin-options/admin-options.php';

require_once BUDDYKIT_PATH . 'src/includes/media/class-media.php';

require_once BUDDYKIT_PATH . 'src/includes/media/profile-tabs/profile-tabs.php';

require_once BUDDYKIT_PATH . 'src/includes/real-time-notifications/real-time-notifications.php';
