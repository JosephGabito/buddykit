/**
 * Upload Collection
 */
jQuery(document).ready(function($){
	if ( 0 === parseInt(__buddyKit.current_user_id) ) {
		return;
	}
	if ( typeof __buddyKit === 'undefined' ) {
		return;
	}

	window.buddyKitGlobalFileCounter = 0;
	// =========================================================
	// Our Model
	// =========================================================
	var BuddykitFileModel = Backbone.Model.extend({
		idAttribute: 'ID',
		defaults: { ID: 0, type: 'image', name: '', user_id: 0, public_url: ''},
	});


	// =========================================================
	// Our Collection
	// =========================================================
	var BuddykitFileCollection = Backbone.Collection.extend({
		model: BuddykitFileModel,
	});

	// =========================================================
	// Our Views
	// =========================================================

	// ==========
	// View: BuddyKitUploaderView (Upload Wrapper)
	// ==========
	var BuddyKitUploaderView = Backbone.View.extend({
		template: _.template($('#buddykit-file-uploader').html()),
		initialize: function() {
			this.render();
		},
		render: function(){
			$('#'+__buddyKit.config.upload_form_container).append(this.template());
		}
	});
	window.buddyKitUploaderView = new BuddyKitUploaderView();
	// ==========
	// View: ul#buddykit-filelist The list of files (individual item)
	// ==========
	var BuddykitFileView = Backbone.View.extend({
		template: _.template( $('#buddykit-file-list-template').html() ),
		render: function(__model){
			var list = this.template(__model.attributes);
			return list;
		}
	});
	// ==========
	// View: #buddykit-filelist-wrap (Just right above the ul#buddykit-filelist)
	// ==========
	var BuddykitFilesView = Backbone.View.extend({
		el: '#buddykit-filelist-wrap',
		ul: $('#buddykit-filelist'),
		initialize: function() {
			this.collection.on("add", function(){
				this.addNode();
				buddyKitGlobalFileCounter++;

			}, this);
			this.collection.on("remove", function(){
				this.render();
				buddyKitGlobalFileCounter--;
			}, this);
			this.collection.on("change add remove reset", function(){
				this.showFlushButton();
				$('#buddykit-filelist-wrap').show();
				if ( 0 == buddyKitGlobalFileCounter) {
					$('#buddykit-filelist-wrap').hide();
				}
			}, this)
		},
		events: {
			"click .buddykit-filelist-item-delete": 'deleteItem',
			"click #buddykit-flush-temporary-files-btn": 'flushAllItems'
		},

		showFlushButton: function() {
			$('#buddykit-flush-temporary-files-btn').hide();
			if ( this.collection.models.length >= 1) {
				$('#buddykit-flush-temporary-files-btn').show();
			}
		},

		flushAllItems: function(e){
			if (e) {
				e.preventDefault();
			}
			$('.buddykit-filelist-item').addClass('loading');
			$.ajax({
				url: __buddyKit.rest_upload_uri + 'user-temporary-flush/' + __buddyKit.current_user_id,
				type: 'DELETE',
				dataType: 'json',
				headers: { 'X-WP-Nonce': __buddyKit.nonce },
				success:function(response){
					if ( response ) {
						buddyKitFiles.remove(buddyKitFiles.models)
					} else {
						$.fancybox.open({src: '#buddykit-hidden-error-message', type: 'inline' });
					}
				},
				error: function(e,y) {
					$.fancybox.open({src: '#buddykit-hidden-error-message', type: 'inline' });
				}
			});
		},

		deleteItem: function(e) {

			var modelId = e.target.getAttribute('data-model-id');
			var fileId = e.target.getAttribute('data-file-id');
			var file = buddyKitFiles.get(modelId);

			e.preventDefault();
			$(e.target).parent().addClass('loading');
			file.destroy({
				wait: true,
				error: function(model, response) {
					$.fancybox.open({src: '#buddykit-hidden-error-message', type: 'inline' });
				},
				url: __buddyKit.rest_upload_uri + 'user-temporary-media-delete/' + fileId,
				headers: { 'X-WP-Nonce': __buddyKit.nonce }
			});

			return;
		},

		addNode: function() {

			var current_model_index = buddyKitGlobalFileCounter;

			if ( 0 === this.collection.length ) { current_model_index = 0; }

			var fileModel = this.collection.at(current_model_index);

			var fileView = new BuddykitFileView(fileModel);

			if ( fileModel ) {
				this.ul.append( fileView.render(fileModel) );
			}

		},

		render: function() {
			this.ul.html('');
			this.collection.each(function(fileModel){
				var fileView = new BuddykitFileView(fileModel);
					this.ul.append(fileView.render(fileModel));
			}, this);

		}
	});

	window.buddyKitFiles = new BuddykitFileCollection();

	// Index File ===
	window.buddyKitFilesView = new BuddykitFilesView({ collection: buddyKitFiles });

	buddyKitFilesView.render();


	/////////////////////////////////////
	//	My Upgrades
	////////////////////////////////////

	// Sync the files
	function syncTheFiles(){ //I've wrapped existing code in the function so it can be called later
		Backbone.sync( 'read', buddyKitFiles, {
			url: __buddyKit.rest_upload_uri + 'user-temporary-media',
			headers: { 'X-WP-Nonce': __buddyKit.nonce },
			success: function( response ) {
				if (response) {
					buddyKitFiles.add(response);
				}
			}
		});
	}
	syncTheFiles();
	// Index File End ==

	////////////////////////////////////////////////////////////////////////////////////////
	//	On SUBMIT button click - hide Submit and Cancel buttons and display spinning loader
	///////////////////////////////////////////////////////////////////////////////////////
	$(document).on('click', '#aw-whats-new-submit', function(){
		var textarea = $('#whats-new-textarea textarea');
		var text_content = textarea.val().trim();


		// Activity textarea must not be empty
		if (text_content === '') {
			// Mark - textarea as a problem
			textarea.addClass('textarea-empty-error');

			// Load and display uploaded media files again - if deleted
			var chk_attached_media_files = setInterval(function(){
				if ( $('#buddykit-filelist li').length == 0 ) {
					syncTheFiles();
					clearInterval(chk_attached_media_files);
				}
				// console.log('Checking if there are media files attached.');
			}, 250);

			return;
		}


		// Unmark textarea
		textarea.removeClass('textarea-empty-error');



		// DISPLAY SPINNER ONLY IF THERE IS NO ERROR DETECTED BY BUDDYPRESS

		// On submit, hide Submit and Cancel buttons and display Spinner
		$(this).hide(); //Hide submit button
		$('#aw-whats-new-reset').hide(); //Hide cancel button
		//Display spinner
		$(this).after('<div id="buddykit-publish-spinner" class="loader"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve"><path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/><path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0C22.32,8.481,24.301,9.057,26.013,10.047z"><animateTransform attributeType="xml"attributeName="transform"type="rotate"from="0 20 20"to="360 20 20"dur="0.5s"repeatCount="indefinite"/></path></svg></div>');


		var chk_submit_status = setInterval(function(){
				// CHECK IF SUBMIT BUTTON EL - REMOVED
				if ( $('#aw-whats-new-submit').length == 0 ) {
						// Submit button el removed
						// There is no need to Hide or remove Spinner
						// Becasue parent element has been removed
						// and it is going to be recreated dynamically without spinner.
						clearInterval(chk_submit_status);


				// CHECK IF ERROR MESSAGE DISPLAYED
			} else if($('#whats-new-form #message').length > 0){

					// Hide spinner and Display Submit and Cancel buttons
					$('#buddykit-publish-spinner').remove();// Hide or remove Spinner
					$('#aw-whats-new-submit').show(); // Display submit button
					$('#aw-whats-new-reset').show(); // Display cancel button

					clearInterval(chk_submit_status);
				}

					// console.log('Checking submit status.');
				}, 250);


	});

	///////////////////////////////////////////////////////////////////////



	// PlUpload Script
	var uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight',
		browse_button : document.getElementById('buddykit-browse'),
		container: document.getElementById('buddykit-container'),
		url : __buddyKit.rest_upload_uri + 'upload',
		flash_swf_url: 'vendor/plupload/Moxie.swf',
		silverlight_xap_url: 'vendor/plupload/Moxie.xap',
		filters: {
			  	mime_types : [
			    	{ title: "Image files", extensions: "jpeg,jpg,gif,png" },
			    	{ title: "Video files", extensions: "mp4" }
			  	]
			},

		unique_names: true,
		headers: {
			'X-WP-Nonce': __buddyKit.nonce
		},
		init: {
			PostInit: function() {},
			FileFiltered: function(up, file) {

				var max_img_size = parseInt(__buddyKit.config.options.buddykit_field_max_image_size) * 1000000;
				var max_vid_size = parseInt(__buddyKit.config.options.buddykit_field_max_video_size) * 1000000;

				var size_collection = [max_vid_size,max_img_size];

					size_collection.sort(function (a,b) {
				   		 return a - b;
					});

				this.setOption('max_file_size', size_collection[size_collection.length-1] );

				return;

			},
			FilesAdded: function(up, files) {
				$('#buddykit-filelist-wrap').show();

				// Filter maximum number of downloads.
				if ( buddyKitFiles.length >= __buddyKit.config.options.buddykit_field_max_image_number ) {
					alert('You have reached the allowed number of images per post.');
				} else {
					plupload.each( files,
						function(file) {
							document.getElementById(__buddyKit.file_list_container_id ).innerHTML += '<li id="'+file.id+'" class="buddykit-filelist-item">' + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
						});
						// It is not neccesary to clear text area after media file uploaded.
						// Therefore I've disabled the line of code below.
						// $('#whats-new').focus().val('').selectRange(0,0);
					uploader.start();
				}
			},
			FileUploaded: function(up, file, response) {

				var __response = JSON.parse(response.response);

				if ( 200 === __response.status ) {

					var image = __response.image;
					var image_url = image.url;

					if ( __response.file_id >= 1 ) {

						var buddykitFile = new BuddykitFileModel({
							name: file.name,
							public_url: image_url,
							ID: __response.file_id,
							user_id: __buddyKit.current_user_id,
							type: file.type
						});

						$('#'+file.id).remove();
						buddyKitFiles.add( buddykitFile );

					} else {
						console.log('Error @uploader.FileUploaded: Zero file id.');
					}

				} else {
					$('#'+file.id).html( __response.error_message ).addClass('error');

					setTimeout(function(){
						$('#'+file.id).addClass('done');
						setTimeout(function(){
							$('#'+file.id).remove();
						}, 1500);
					}, 1500);
				}
			},
			UploadProgress: function(up, file) {
				document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
				//Disable activity post form when uploading is going on to prevent unexpected things from happening.
				$('#aw-whats-new-submit').attr('disabled', true);

				/////////////////////////////////////////////////
				// MY UPGRADE
				/////////////////////////////////////////////////

				//Disable cancel button as well
				$('#aw-whats-new-reset').attr('disabled', true);


				//Video processing notification - with loading spinner
				if (file.percent == 100) {
					document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span class="buddykit-file-processing">Processing...<br><div class="loader"><svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve"><path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/><path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0C22.32,8.481,24.301,9.057,26.013,10.047z"><animateTransform attributeType="xml"attributeName="transform"type="rotate"from="0 20 20"to="360 20 20"dur="0.5s"repeatCount="indefinite"/></path></svg></div></span>';

				}

				////////////////////////////////////////////////


			},
			UploadComplete: function(up, files, response) {
				$('#aw-whats-new-submit').attr('disabled', false);

				/////////////////////////////////////////////////
				// MY UPGRADE
				/////////////////////////////////////////////////

				//Enable cancel button again
				$('#aw-whats-new-reset').attr('disabled', false);

				/////////////////////////////////////////////////
			},
			Error: function(up, err) {

				$('#buddykit-filelist-wrap').show();

				var file_el = document.getElementById(__buddyKit.file_list_container_id );
				var file_id = err.file.id;

				///////////////////////////////////////////////////////////////////////////////
				// My upgrade for displaying errors - actual error will be displayed in console
				//////////////////////////////////////////////////////////////////////////////
				file_el.innerHTML += '<li id="'+file_id+'" class="buddykit-filelist-item error"><span>Server Error<br> try again later</span></div>';
				console.log("Media file upload error: " + err.message);
				// I've disabled original line of code below, responsible for displaying actuall errors in user interface
				// file_el.innerHTML += '<li id="'+file_id+'" class="buddykit-filelist-item error"><span>'+err.message+'</span></div>';
				/////////////////////////////////////////////////////////////////////////////

				setTimeout(function(){
					$('#'+file_id).addClass('done');
					setTimeout(function(){
						$('#'+file_id).remove();
						// Clear if necessary
						if ( 0 === $('.buddykit-filelist-item').length ) {
							$('#buddykit-filelist-wrap').hide();
						}
					},1000);
				}, 2000);

				$('#aw-whats-new-submit').attr('disabled', false);

				/////////////////////////////////////////////////
				// MY UPGRADE
				/////////////////////////////////////////////////

				//Enable cancel button again
				$('#aw-whats-new-reset').attr('disabled', false);

				/////////////////////////////////////////////////

				return;
			}
		}
	}); // End uploaded object.

	uploader.init();

});

/**
* Old good jQuery events
*/

jQuery(document).ready(function($){

	// Delete
	$('body').on('click','.buddykit-profile-tabs-media-delete', function(e){
		e.preventDefault();
		// Confirm deletion.
		if ( !confirm( __buddyKit.i18.confirm_media_delete ) ) {
			return;
		}
		var element = $(this);
		$.ajax({
			url: __buddyKit.rest_upload_uri + 'delete/' + $(this).attr('data-file-id'),
			headers: {
				'X-WP-Nonce': __buddyKit.nonce,
			},
			method: 'DELETE',
			success: function(response) {
				if ( response.status ) {
					if ( 200 == response.status ) {
						element.parent().remove();
					}
				}
			}
		});
	});
	// auto focus textarea
	$.fn.selectRange = function(start, end) {
	    if(!end) end = start;
	    return this.each(function() {
	        if (this.setSelectionRange) {
	            this.focus();
	            this.setSelectionRange(start, end);
	        } else if (this.createTextRange) {
	            var range = this.createTextRange();
	            range.collapse(true);
	            range.moveEnd('character', end);
	            range.moveStart('character', start);
	            range.select();
	        }
	    });
	};

	// Magnific popup

	$('.buddykit-activity-media-gallery').magnificPopup({
		delegate: 'a', type: 'image',
	  	gallery: {
	  		enabled: true
	  	}
	});

	$('.buddykit-profile-tabs-image-item').magnificPopup({
		type: 'image',
	  	gallery: {
	  		enabled: true
	  	}
	});

	$('body').on('click', '.buddykit-media-wrap', function(){

		var videoHtml = $(this).find('div.buddykit-video-inner-wrap').html();
			console.log($(this).find('div.buddykit-video-inner-wrap'));
		$.magnificPopup.open({
		  	items: {
		    	src: '<div class="buddykit-video-popup"><div class="buddykit-media-video-popup-wrap">'+videoHtml+'</div></div>', // can be a HTML string, jQuery object, or CSS selector
		    	type: 'inline'
		  	},
		  	callbacks: {
		  		open: function(){
		  			var player_options = {
						controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', '', 'pip', 'airplay', 'fullscreen'],
					};
					var player = new Plyr( document.getElementById($(videoHtml).attr('id')) );
		  		}
		  	},
		});

	});

	$(document).ajaxComplete(function(event,request,settings){

		if ( settings.data ) {

			var http_request_data = JSON.parse('{"' + decodeURI(settings.data.replace(/&/g, "\",\"").replace(/=/g,"\":\"")) + '"}');

			if ( typeof http_request_data === 'object') {

				var valid_actions = ['activity_filter', 'post_update'];

				if ( http_request_data.action ) {

					// Hide the button on succesful post update
					if ( valid_actions[1] === http_request_data.action ){
						setTimeout(function(){
							if( "bp-messages bp-feedback error" !== $('#whats-new-submit').next().attr('class') ) {
								buddyKitFiles.remove(buddyKitFiles.models);
							}
						}, 500);

					}

					// Now we know that this is a BuddyPress activity object
					if ( $.inArray( http_request_data.action , valid_actions) >= 0 ) {
						window.buddykitMagnificPopUpped = false;
						$('body').on('mouseover', '.buddykit-activity-media-gallery', function(){
							if ( ! window.buddykitMagnificPopUpped )  {
								$('.buddykit-activity-media-gallery').each(function(){
									$(this).magnificPopup({
										delegate: 'li.buddykit-activity-media-gallery-item > a', type: 'image',
										gallery: { enabled: true }
									});
								});

								window.buddykitMagnificPopUpped = true;
							}
						});
					}
				}
			}
		}
	});
});


////////////////////////////
// My Upgrades
///////////////////////////
jQuery(document).ready(function($){

	// On CANCEL button click - flush all tmp. loaded files
	$(document).on('click', '#aw-whats-new-reset', function(){
		$('#buddykit-flush-temporary-files-btn').click();
		//Remove red background - in case of previous error
		$('#whats-new-textarea textarea').removeClass('textarea-empty-error');
	});


});
