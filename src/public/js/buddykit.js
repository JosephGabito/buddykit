jQuery(document).ready(function($){
	
	var uploader = new plupload.Uploader({
		runtimes : 'html5',
		browse_button : 'browse', // you can pass an id...
		container: document.getElementById('container'), // ... or DOM Element itself
		url : 'http://localhost/thrive/wp-json/buddykit/v1/activity-new',
		//flash_swf_url : '../js/Moxie.swf',
		//silverlight_xap_url : '../js/Moxie.xap',
	
		filters : {
			//max_file_size : '10mb',
			/**
			mime_types: [
				{title : "Image files", extensions : "jpg,gif,png"},
				{title : "Zip files", extensions : "zip"}
			]**/
		},
		init: {
			PostInit: function() {
				document.getElementById('filelist').innerHTML = '';
				document.getElementById('start-upload').onclick = function() {
					uploader.start();
					return false;
				};
			},
		
			FilesAdded: function(up, files) {
				plupload.each(files, function(file) {
					document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
				});
			},

			FileUploaded: function(up, files) {
				console.log(up);
				console.log(files);
			},

			UploadProgress: function(up, file) {
				document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
			},
			
			UploadComplete: function(up, files) {
				//console.log(up);
				//console.log(files);
			},

			Error: function(up, err) {
				document.getElementById('console').appendChild(document.createTextNode("\nError #" + err.code + ": " + err.message));
			}
	}
});
uploader.init();
});