$(document).ready(function() {
	
	$('#post_is_unique').click(function() {
		var url = 'http://www.google.com/search?q='+encodeURIComponent('site:http://www.musiqueapproximative.net ' + $('#post_track_title').val());
		window.open(url);
	});
	
});