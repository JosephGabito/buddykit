<?php
add_action( 'wp_enqueue_scripts', 'buddykit_real_time_notifications' );

function buddykit_real_time_notifications_get_notification( $notification ) {
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
function buddykit_real_time_notifications() {

    wp_enqueue_style( 'buddykit-rtn-style', BUDDYKIT_PUBLIC_URI . 'css/vendor/snackbar/snackbar.css', false );
    wp_enqueue_script( 'buddykit-rtn-js'  , BUDDYKIT_PUBLIC_URI .  'js/vendor/snackbar/snackbar.js', false );

    return;
}

add_action('bp_notification_after_save', function( $notification ){
	
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
			'message' => 'hello world',
			'notification' => $notifications,
			'format' => $format
		)
	);
});


// Add pusher script
add_action('wp_footer', function(){
?>

<script src="https://js.pusher.com/4.2/pusher.min.js"></script>
<script>
    var pusher = new Pusher('61d3abc0df34615bffe8', {
		cluster: 'mt1'
    });
    var channel = pusher.subscribe('buddykit-notification-channel');
    channel.bind('buddykit-notification-event', function(data) {
    	console.log(data);
        if ( __buddyKit.current_user_id == data.notification.user_id ) {
        
        	var snack_options = {
	    		content: data.notification,
	    		timeout: 10000,
	    		htmlAllowed: true
	    	};
	    	jQuery.snackbar(snack_options);
        }
    });
    
</script>
<?php
});

