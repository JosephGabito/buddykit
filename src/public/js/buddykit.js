/**
 * Upload Collection
 */
jQuery(document).ready(function($){
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
			e.preventDefault();
			$.ajax({
				url: __buddyKit.rest_upload_uri + 'user-temporary-flush/' + __buddyKit.current_user_id,
				type: 'DELETE',
				dataType: 'json',
				headers: { 'X-WP-Nonce': __buddyKit.nonce },
				success:function(response){
					if ( response ) {
						buddyKitFiles.remove(buddyKitFiles.models)
					} else {
						console.warn('There was an error: <flushAllItems> success callback.')
					}
				},
				error: function(e,y) {
					console.log(e);
					console.warn('There was an error: <flushAllItems> http_request.')
				}
			});
		},

		deleteItem: function(e) {
			
			var modelId = e.target.getAttribute('data-model-id');
			var fileId = e.target.getAttribute('data-file-id');
			var file = buddyKitFiles.get(modelId);

			e.preventDefault();

			file.destroy({
				wait: true,
				success: function(model, response){
					console.log('success');
				},
				error: function(model, response) {
					console.log(response);
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

	// Sync the files
	Backbone.sync( 'read', buddyKitFiles, {
		url: __buddyKit.rest_upload_uri + 'user-temporary-media',
		headers: { 'X-WP-Nonce': __buddyKit.nonce },
		success: function( response ) {
			if (response) {
				buddyKitFiles.add(response);
			}
		}
	});
	// Index File End ==
	var uploader = new plupload.Uploader({
		runtimes : 'html5,flash,silverlight',
		browse_button : document.getElementById('browse'),
		container: document.getElementById('container'),
		url : __buddyKit.rest_upload_uri + 'activity-new',
		flash_swf_url: 'vendor/plupload/Moxie.swf',
		silverlight_xap_url: 'vendor/plupload/Moxie.xap',
		filters : {},
		headers: {
			'X-WP-Nonce': __buddyKit.nonce
		},
		init: {
			PostInit: function() {},
			FilesAdded: function(up, files) {
				plupload.each(files, function(file) {
					document.getElementById(__buddyKit.file_list_container_id ).innerHTML += '<li id="'+file.id+'" class="buddykit-filelist-item">' + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
				});
				uploader.start();
			},
			FileUploaded: function(up, file, response) { 

				if ( 200 === response.status ) {
					
					var json_response = JSON.parse(response.response);
					var image = json_response.image;
					var image_url = image.url;

					if ( json_response.file_id >= 1 ) {

						var buddykitFile = new BuddykitFileModel({
							name: file.name,
							public_url: image_url,
							ID: json_response.file_id,
							user_id: __buddyKit.current_user_id,
							type: file.type
						});
						
						$('#'+file.id).remove();
						buddyKitFiles.add( buddykitFile );

					} else {
						
						console.log('Error @uploader.FileUploaded: Zero file id.');

					}
				} else {

					console.log('Error @uploader.FileUploaded: Response unknown.');

				}
			},
			UploadProgress: function(up, file) {
				document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			},
			UploadComplete: function(up, files, response) {
			//	var file_add_button_tpl = _.template($('#buddykit-file-add-button').html());
			//	filesView.$el.append(file_add_button_tpl());
			},
			Error: function(up, err) {
				document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
			}
		}
	}); // End uploaded object.

	uploader.init();

});