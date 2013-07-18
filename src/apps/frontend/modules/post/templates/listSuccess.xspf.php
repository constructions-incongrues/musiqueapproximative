<?php 
// TODO : all this should not be in view

if (defined('E_DEPRECATED')) {
	$errorLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
	$errorLevel = error_reporting(E_ALL & ~E_NOTICE);
}

set_include_path(get_include_path().':/usr/share/php');
require('File/XSPF.php');

$playlist = new File_XSPF();
$playlist->setDate(time());
if ($sf_request->getParameter('contributor')) {
	if (count($posts)) {
		$name = $posts[0]->getContributorDisplayName();
	} else {
		$name = $sf_request->getParameter('contributor');
	}
	$playlist->setTitle('Musique Approximative : Morceaux postés par ' . $name);
} else if ($sf_request->getParameter('q')) {
	$playlist->setTitle('Musique Approximative : Recherche sur le terme "'.$sf_request->getParameter('q').'"');
} else {
	$playlist->setTitle('Musique Approximative : Tous les morceaux');
}

foreach ($posts as $post) {
	$track = new File_XSPF_Track();
	$track->setCreator($post->track_author);
	$track->setTitle($post->track_title);
	$track->setAnnotation($post->body);
	$track->setInfo(url_for('@post_show?slug='.$post->slug, true));
	$location = new File_XSPF_Location();
	$location->setUrl(sprintf('%s%s/tracks/%s', $sf_request->getUriPrefix(), $sf_request->getRelativeUrlRoot(), rawurlencode($post->track_filename)));
	$track->addLocation($location);
	$playlist->addTrack($track);
}
echo str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="utf-8"?>', $playlist->toString());
error_reporting($errorLevel);
