/**
 * Upload Collection
 */
jQuery(document).ready(function($){

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
				this.render();
			}, this);
			this.collection.on("remove", function(){
				this.render();
			}, this);
		},
		events: {
			"click .buddykit-filelist-item-delete": 'deleteItem'
		},
		deleteItem: function(e) {
			
			e.preventDefault();
			var modelId = e.target.getAttribute('data-model-id');
				buddyKitFiles.remove(modelId);
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
	
	var filesView = new BuddykitFilesView({ collection: buddyKitFiles });

	filesView.render();
	// Sync the files
	Backbone.sync( 'read', buddyKitFiles, {
		url: __buddyKit.rest_upload_uri + 'user-temporary-media',
		headers: {
			'X-WP-Nonce': __buddyKit.nonce
		},
		success: function( response ) {
			buddyKitFiles.add(response);
		}
	});
});