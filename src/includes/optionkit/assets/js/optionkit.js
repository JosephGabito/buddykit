jQuery(document).ready(function($){
	'use strict';
	// Add Color Picker to all inputs that have 'data-colorpicker=optionkit-wp-colorpicker' class
 
    if ( typeof wpColorPicker !== 'undefined' ) {
        $('input[data-colorpicker=optionkit-wp-colorpicker]').wpColorPicker();
    }
    

    // Media Upload.
    // Set all variables to be used in scope
  	var frame;

    $('input[data-media-upload-button=yes]').on('click', function(e){
    	e.preventDefault();
    	var input = $(this);
    	if ( frame ) {
    		frame.open();
    		return;
    	}

    	frame = wp.media({
	    	title: 'Select or Upload Media',
	      	button: {
	        	text: 'Use this media'
	     	},
	     	library: {
	     		type: ['image']
	     	},
	     	multiple: false  // Set to true to allow multiple files to be selected
	    });

	    // When an image is selected in the media frame...
	    frame.on( 'select', function() {
	      	var attachment = frame.state().get('selection').first().toJSON();
	     		$(input.attr('data-input-id')).val( attachment.id );
	     		$(input.attr('data-preview-id')).html("<img class='optionkit-preview' src='"+attachment.url+"' />");
	    });

	    // Finally, open the modal on click
	    frame.open();
	  });

    $('.optionkit-image-upload-delete').on('click', function(e){
    	e.preventDefault();
    	var input = $(this);
    	$(input.attr('data-input-id')).val('');
	    $(input.attr('data-preview-id')).html("");
	    $('input[data-media-upload-button=yes]').removeClass('hidden');
	    $(this).addClass('hidden');
    })
    
});


    

