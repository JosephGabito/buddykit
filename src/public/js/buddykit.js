jQuery(document).ready(function($){
	
	var uploadTemplate = "";

	var uploader = new plupload.Uploader({
		browse_button: document.getElementById('browse'), // this can be an id of a DOM element or the DOM element itself
		url: 'http://thrive.dsc/wp-json/buddykit/v1/author/1'
	});

	uploader.bind('FilesAdded', function(up, files) {
		var html = '';
		plupload.each(files, function(file) {
			html += '<li id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></li>';
		});
		document.getElementById('filelist').innerHTML += html;
	});

	uploader.bind('UploadProgress', function(up, file) {
		document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
	});

	uploader.init();

	document.getElementById('start-upload').onclick = function() {
		uploader.start();
	};
});