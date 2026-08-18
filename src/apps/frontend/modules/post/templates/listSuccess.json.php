<?php
// @see http://jsonapi.org/format/#url-based-json-api
//
// `posts` est lu au brut, et le document n'est pas retouche apres json_encode() :
// voir showSuccess.json.php pour ce que la retouche detruisait.
$posts = $sf_data->getRaw('posts');
$json = array();
// TODO : previous and next post
foreach ($posts as $post) {
  $json[] = $post->toJson(
    $sf_data->getRaw('sf_request'),
    $sf_data->getRaw('sf_context'),
    null,
    null
  );
}

// Even single ressources are displayed as lists
echo sprintf('{ "posts": [%s] }', implode(',', $json));
