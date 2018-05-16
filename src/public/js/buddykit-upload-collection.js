/**
 * Upload Collection
 */
jQuery(document).ready(function(){

	// =========================================================
	// Our Model
	// =========================================================
	var BuddykitFileModel = Backbone.Model.extend({
		idAttribute: 'ID',
		defaults: {
			ID: 0,
			type: 'image',
			name: '',
			user_id: 0,
			public_url: '',
		},
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
		el: 'li.buddykit-filelist-item',
		render: function(){

		}
	});

	var BuddykitFilesView = Backbone.View.extend({
		el: 'ul#buddykit-filelist',
		render: function() {
			this.collection.each(function(file){
				console.log(file);
			});
		}
	});

	var files = new BuddykitFileCollection();
		file1 = new BuddykitFileModel({name: 'test22', ID: 1});
		files.add(file1);

	var filesView = new BuddykitFilesView({ collection: files });

	filesView.render();
});