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
		model: BuddykitFileModel
	});

	// =========================================================
	// Our View
	// =========================================================
	var BuddykitFileView = Backbone.View.extend({
		template: _.template( $('#buddykit-file-list-template').html() ),
		render: function(__model){
			var list = this.template(__model.attributes);
			return list;
		}
	});

	var BuddykitFilesView = Backbone.View.extend({
		el: 'ul#buddykit-filelist',
		initialize: function() {
			this.collection.on("add", function(){
				this.addNode();
				buddyKitGlobalFileCounter++;
			}, this);
			this.collection.on("remove", function(){
				this.render();
				buddyKitGlobalFileCounter--;
			}, this);
		},
		events: {
			"click .buddykit-filelist-item-delete": 'deleteItem'
		},
		deleteItem: function(e) {
			
			e.preventDefault();
			
			var modelId = e.target.getAttribute('data-model-id');
			var fileId = e.target.getAttribute('data-file-id');


			//buddyKitFiles.remove(modelId);
			var file = buddyKitFiles.get(modelId);

			file.destroy({
				success: function(model, response){
					console.log('success');
				},
				error: function(model, response) {
					console.log(response);
				},
				url: __buddyKit.rest_upload_uri + 'user-temporary-media-delete/' + fileId
			});

		},
		addNode: function() {
			
			var current_model_index = buddyKitGlobalFileCounter;
			
			if ( 0 === this.collection.length ) { current_model_index = 0; }
			
			var fileModel = this.collection.at(current_model_index);
			
			var fileView = new BuddykitFileView(fileModel);
			
			if ( fileModel ) {
				this.$el.append( fileView.render(fileModel) );
			}

		},
		render: function() {
			this.$el.html('');
			this.collection.each(function(fileModel){
				var fileView = new BuddykitFileView(fileModel);
					this.$el.append(fileView.render(fileModel));
			}, this);
		}
	});

	window.buddyKitFiles = new BuddykitFileCollection();
	
	// Index File ===
	var filesView = new BuddykitFilesView({ collection: buddyKitFiles });

	filesView.render();

	// Sync the files
	Backbone.sync( 'read', buddyKitFiles, {
		url: __buddyKit.rest_upload_uri + 'user-temporary-media',
		headers: { 'X-WP-Nonce': __buddyKit.nonce },
		success: function( response ) {
			buddyKitFiles.add(response);
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
				
				if (200 === response.status) {

					var image = JSON.parse(response.response);
					var image_url = image.image.url;
				
					var buddykitFile = new BuddykitFileModel({
							name: file.name,
							public_url: image_url,
							ID: file.id,
							user_id: __buddyKit.current_user_id,
							type: file.type
						});
					$('#'+file.id).remove();
					buddyKitFiles.add( buddykitFile );
				}
			},
			UploadProgress: function(up, file) {
				document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			},
			UploadComplete: function(up, files, response) {

			},
			Error: function(up, err) {
				document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
			}
		}
	}); // End uploaded object.

	uploader.init();

});