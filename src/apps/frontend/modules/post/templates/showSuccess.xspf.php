<?php
// Un morceau isolé est servi comme une playlist d'un seul élément, à l'image des formats json et max.
$post = $sf_data->getRaw('post');

$title = sprintf(
  'Musique Approximative : %s — %s',
  $post->track_author,
  $post->track_title
);

include_partial('post/xspfPlaylist', array(
  'posts' => array($post),
  'title' => $title,
  'trackScheme' => $sf_request->isSecure() ? 'https' : 'http',
));
