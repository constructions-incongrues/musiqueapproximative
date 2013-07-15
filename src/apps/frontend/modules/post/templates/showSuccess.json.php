<?php
$post = $sf_data->getRaw('post')->toArray();
$post['previous'] = $sf_context->getRouting()->generate(
  'post_show', array(
    'slug'   => $post_previous->slug, 
    'format' => $sf_request->getParameter('format')
  ),
  true
);
$post['next'] = $sf_context->getRouting()->generate(
  'post_show', array(
    'slug'   => $post_next->slug,
    'format' => $sf_request->getParameter('format')
  ), 
  true
);
?>
<?php echo json_encode($post) ?>