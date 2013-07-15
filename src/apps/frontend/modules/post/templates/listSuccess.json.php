<?php $posts = $sf_data->getRaw('posts') ?>
<?php $posts = $posts->toArray() ?>
<?php echo json_encode($posts) ?>
