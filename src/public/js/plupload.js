var uploader = new plupload.Uploader({
runtimes : 'html5',
browse_button : this.get('browse_button'),
container: this.get('container'),
url : __buddyKit.rest_upload_uri,
filters : {},
headers: {
	'X-WP-Nonce': __buddyKit.nonce
},
init: {
	PostInit: function() {},
	FilesAdded: function(up, files) {
		plupload.each(files, function(file) {
			document.getElementById(__buddyKit.file_list_container_id ).innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
		});
		uploader.start();
	},
	FileUploaded: function(up, files) {
		//console.log(up);
		//console.log(files);
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