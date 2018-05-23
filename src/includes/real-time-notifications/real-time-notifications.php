<?php


add_action('bp_notification_after_save', function( $data ){
	
	require_once __DIR__ . '/vendor/autoload.php';

	$key = '61d3abc0df34615bffe8';
	$secret = 'a16e4e8b559447fff2d4';
	$app_id = '147971';
	$cluster = 'mt1';

	$pusher = new Pusher\Pusher(
		$key, $secret, $app_id, 
		array('cluster' => $cluster)
	);

	$notification = bp_notifications_get_notification( $data->id );

	$format = bp_activity_format_notifications(
			$notification->component_action,
			$notification->item_id,
			$notification->secondary_item_id,
			$single = 1, 'array',
			$notification->id
		);

	$pusher->trigger('my-channel', 'my-event', 
		array(
			'message' => 'hello world',
			'notification' => $notification,
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
    var channel = pusher.subscribe('my-channel');
    channel.bind('my-event', function(data) {
    	console.log(data.format);
        if ( __buddyKit.current_user_id == data.notification.user_id ) {
        	alert(data.format.text);
        }
    });
</script>
<?php
});

