/**
 * Client script needed for live notifcations
 *
 * Pusher JS Version 4.2
 * 
 * @since  1.0
 */
jQuery(document).ready(function($){

    var pusher = new Pusher(
        __buddyKit.config.options.buddykit_rtn_pusher_key, 
        {
            cluster: __buddyKit.config.options.buddykit_rtn_pusher_cluster
        }
    );

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
    } );

} );

    

