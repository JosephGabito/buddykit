<?php
/**
 * This file is part of the Buddykit WordPress Plugin package.
 *
 * (c) Dunhakdis SC. <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package buddykit/config
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action( 'wp_enqueue_scripts', 'buddykit_real_time_notifications' );

add_action( 'bp_notification_after_save', 'buddykit_pusher_push_notification' );


/**
 * Enqueue all the scripts needed for our notifications
 * @return void
 */
function buddykit_real_time_notifications() {

	// Include the scripts and styles for login users only.
	if ( is_user_logged_in() ) {

		wp_enqueue_script( 'buddykit-pusher'  , BUDDYKIT_PUBLIC_URI .  'js/vendor/pusher/pusher.js', false );

		wp_enqueue_style( 'buddykit-rtn-snackbar-style', BUDDYKIT_PUBLIC_URI . 'css/vendor/snackbar/snackbar.css', false );

		wp_enqueue_script( 'buddykit-rtn-snackbar'  , BUDDYKIT_PUBLIC_URI .  'js/vendor/snackbar/snackbar.js', array( 'buddykit-pusher' ), false );

		wp_enqueue_script( 'buddykit-rtn'  , BUDDYKIT_PUBLIC_URI .  'js/buddykit-real-time-notification.js', array( 'buddykit-pusher', 'buddykit-rtn-snackbar' ) );

	}

	return;
}

/**
 * Getting the notification format/templates.
 * @param  object $notification The notification object.
 * @return string The format/template of the notification.
 */
function buddykit_real_time_notifications_get_notification( $notification ) {

	// Check to see if there is a valid user.
	if ( ! is_user_logged_in() ) {
		return;
	}

	$bp = buddypress();
	if ( isset( $bp->{ $notification->component_name }->notification_callback )
		&& is_callable( $bp->{ $notification->component_name }->notification_callback ) ) {
			$description = call_user_func(
				$bp->{ $notification->component_name }->notification_callback,
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				1,
				'string',
				$notification->id
			);

			// @deprecated format_notification_function - 1.5
	} elseif ( isset( $bp->{ $notification->component_name }->format_notification_function )
			&& function_exists( $bp->{ $notification->component_name }->format_notification_function ) ) {
		$description = call_user_func(
			$bp->{ $notification->component_name }->format_notification_function,
			$notification->component_action,
			$notification->item_id,
			$notification->secondary_item_id,
			1
		);

		// Allow non BuddyPress components to hook in.
	} else {
		/** This filter is documented in bp-notifications/bp-notifications-functions.php */
		$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user',
			array( $notification->component_action, $notification->item_id,
				$notification->secondary_item_id, 1,
				'string', $notification->component_action,
				$notification->component_name,
				$notification->id,
			)
		);
	}
	 return $description;
}


/**
 * Actually pushes the notification to Pusher Api
 * @param  object $notification The notification object.
 * @return void
 */
function buddykit_pusher_push_notification( $notification ) {

	// Make sure the user is login before performing any actions.
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Parse old settings.
	$options = wp_parse_args( get_option( 'buddykit_settings' ), array(
			'buddykit_rtn_pusher_key' => '',
			'buddykit_rtn_pusher_app_id' => '',
			'buddykit_rtn_pusher_secret' => '',
			'buddykit_rtn_pusher_cluster' => '',
		));

	// Check if realtime notifications is enabled or disabled.
	$components = (array) get_option( 'buddykit-components', array('activity-media', 'realtime-notifications') );

	if ( ! in_array('realtime-notifications', $components ) ) {
		return;
	}

	$app_id     = get_option('buddykit-rtn-pusher-app-id', $options['buddykit_rtn_pusher_app_id']);
	$key        = get_option('buddykit-rtn-pusher-key', $options['buddykit_rtn_pusher_key']);
	$secret     = get_option('buddykit-rtn-pusher-secret', $options['buddykit_rtn_pusher_secret']);
	$cluster    = get_option('buddykit-rtn-pusher-cluster', $options['buddykit_rtn_pusher_cluster']);

	require_once __DIR__ . '/vendor/autoload.php';

	$pusher = new Pusher\Pusher(
		$key, $secret, $app_id,
		array( 'cluster' => $cluster )
	);

	$notifications = buddykit_real_time_notifications_get_notification( $notification );

	$pusher->trigger('buddykit-notification-channel', 'buddykit-notification-event',
		array(
			'notification' => $notifications,
			'user_id' => $notification->user_id,
			'format' => $format,
		)
	);

	return;
}
