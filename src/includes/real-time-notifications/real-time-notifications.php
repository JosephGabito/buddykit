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

add_action('bp_notification_after_save', 'buddykit_pusher_push_notification');


/**
 * Enqueue all the scripts needed for our notifications
 * @return void
 */
function buddykit_real_time_notifications() {

    // Include the scripts and styles for login users only.
    if ( is_user_logged_in() ) 
    {

    wp_enqueue_script( 'buddykit-pusher'  , BUDDYKIT_PUBLIC_URI .  'js/vendor/pusher/pusher.js', false );

    wp_enqueue_style( 'buddykit-rtn-snackbar-style', BUDDYKIT_PUBLIC_URI . 'css/vendor/snackbar/snackbar.css', false );
    
    wp_enqueue_script( 'buddykit-rtn-snackbar'  , BUDDYKIT_PUBLIC_URI .  'js/vendor/snackbar/snackbar.js', array('buddykit-pusher'), false );
    
    wp_enqueue_script( 'buddykit-rtn'  , BUDDYKIT_PUBLIC_URI .  'js/buddykit-real-time-notification.js', array('buddykit-pusher', 'buddykit-rtn-snackbar') );

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
            	array( $notification->component_action, 
            		$notification->item_id, $notification->secondary_item_id, 1,
            		 'string', $notification->component_action, $notification->component_name, $notification->id ) 
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

    require_once __DIR__ . '/vendor/autoload.php';

    $key = '61d3abc0df34615bffe8';
    $secret = 'a16e4e8b559447fff2d4';
    $app_id = '147971';
    $cluster = 'mt1';

    $pusher = new Pusher\Pusher(
        $key, $secret, $app_id, 
        array('cluster' => $cluster)
    );

    $notifications = buddykit_real_time_notifications_get_notification($notification);

    $pusher->trigger('buddykit-notification-channel', 'buddykit-notification-event', 
        array(
            'notification' => $notifications,
            'user_id' => $notification->user_id,
            'format' => $format
        )
    );

    return;
}


add_action('buddykit_settings_tab_fields', function(){
    /**
     * Upload Button Label
     */
   add_settings_section(
        'buddykit_section_media',
        'Pusher',
        'buddykit_section_rtn_view',
        'buddykit-settings.php'
    );
    
    add_settings_field(
        'buddykit_rtn_pusher_key', // The field ID
        'Pusher Key', // The label
        'buddykit_rtn_pusher_key_view', // Callback view
        'buddykit-settings.php', // Under what settings?
        'buddykit_section_media', //Section,
            [
                'label_for' => 'buddykit_rtn_pusher_key',
                'class' => 'buddykit_rtn_pusher_key_row',
                'default' => '',
                'description' => __('Pusher API key', 'buddykit')
            ]
        );
});
function buddykit_section_rtn_view() {
    echo '<p>';
    esc_html_e('We use Pusher to serve your real-time notifications. Why Pusher? Base on our experiece, unless you want to mess with websockets, mimicking live events with PHP is easier but it is always unsustainable and expensive. Other plugins, uses short pooling or WordPress Heartbeat API to serve the notifications, which is both bad and makes your server very slow. ');
    echo '</p>';
    echo '<p>';
    esc_html_e('With services like Pusher, Firebase(soon), and Ably(soon), you do not only get a free version but it\'s cheaper to update in the feature compare to updating your server stack to serve your own notifications.','buddykit');
    echo '</p>';
}

    function buddykit_rtn_pusher_key_view( $args ) {
        echo 'tae';
    } 