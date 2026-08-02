<?php
$posts = $sf_data->getRaw('posts');

if ($sf_request->getParameter('contributor')) {
  if (count($posts)) {
    $name = $posts[0]->getContributorDisplayName();
  } else {
    $name = $sf_request->getParameter('contributor');
  }
  $title = 'Musique Approximative : Morceaux postés par ' . $name;
} else if ($sf_request->getParameter('q')) {
  $title = 'Musique Approximative : Recherche sur le terme "' . $sf_request->getParameter('q') . '"';
} else {
  $title = 'Musique Approximative : Tous les morceaux';
}

include_partial('post/xspfPlaylist', array('posts' => $posts, 'title' => $title));
