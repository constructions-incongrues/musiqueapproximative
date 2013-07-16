<?php
// @see http://jsonapi.org/format/#url-based-json-api
$posts = $sf_data->getRaw('posts');
$json = array();
// TODO : previous and next post
foreach ($posts as $post) {
  $json[] = html_entity_decode($post->toJson(
    $sf_data->getRaw('sf_request'), 
    $sf_data->getRaw('sf_context'), 
    null,
    null
  ));
}

// Even single ressources are displayed as lists
echo sprintf('{ "posts": [%s] }', implode(',', $json));