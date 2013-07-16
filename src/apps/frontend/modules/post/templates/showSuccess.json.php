<?php
// @see http://jsonapi.org/format/#url-based-json-api
$json = html_entity_decode($post->toJson(
  $sf_data->getRaw('sf_request'), 
  $sf_data->getRaw('sf_context'), 
  $sf_data->getRaw('post_previous'), 
  $sf_data->getRaw('post_next')
));

// Even single ressources are displayed as lists
echo sprintf('{ "posts": [%s] }', $json);