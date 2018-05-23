/**
 * Client script needed for live notifcations
 *
 * Pusher JS Version 4.2
 * 
 * @since  1.0
 */
jQuery(document).ready(function($){
    var pusher = new Pusher('61d3abc0df34615bffe8', {
        cluster: 'mt1'
    });

    var channel = pusher.subscribe('buddykit-notification-channel');

    channel.bind('buddykit-notification-event', function(data) {
        
        if ( __buddyKit.current_user_id == data.user_id ) {
            
            var snack_options = {
                content: data.notification,
                timeout: 10000,
                htmlAllowed: true
            };
            
            $.snackbar(snack_options);
            
        }
    });
});