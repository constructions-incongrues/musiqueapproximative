<?php
$posts = $sf_data->getRaw('posts');

// Le contributeur passe par `c`, comme partout ailleurs — routing, action et les
// autres formats. Ce gabarit lisait `contributor`, un paramètre qui n'existe pas :
// une playlist filtrée s'annonçait donc « Tous les morceaux ».
if ($sf_request->getParameter('c')) {
  if (count($posts)) {
    $name = $posts[0]->getContributorDisplayName();
  } else {
    $name = $sf_request->getParameter('c');
  }
  $title = 'Musique Approximative : Morceaux postés par ' . $name;
} else if ($sf_request->getParameter('q')) {
  $title = 'Musique Approximative : Recherche sur le terme "' . $sf_request->getParameter('q') . '"';
} else {
  $title = 'Musique Approximative : Tous les morceaux';
}

include_partial('post/xspfPlaylist', array(
  'posts' => $posts,
  'title' => $title,
  'baseUrl' => $sf_request->getUriPrefix() . $sf_request->getRelativeUrlRoot(),
));
