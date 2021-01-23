<?php use_helper('Text') ?>

<?php slot('formats_head') ?>
<?php foreach ($formats as $name => $format): ?>
  <link rel="alternate" type="<?php echo $format['contentType'] ?>" href="<?php echo url_for(sprintf('@post_list?c=%s&q=%s&format=%s', $sf_request->getParameter('c'), $sf_request->getParameter('q'), $name)) ?>" />
<?php endforeach; ?>
<?php end_slot() ?>

<?php slot('formats_footer') ?>
<h2>Servez-vous !</h2>
<p>Cette playlist est aussi disponible aux formats suivants :
<?php foreach ($formats as $name => $format): ?>
  <?php if ($format['display']): ?>
  <a href="<?php echo url_for(sprintf('@post_list?c=%s&q=%s&format=%s', $sf_request->getParameter('c'), $sf_request->getParameter('q'), $name)) ?>" title="<?php echo $format['contentType'] ?> <?php if ($format['about']): ?> (<?php echo $format['about'] ?>) <?php endif ?>"><?php echo $name ?></a> 
  <?php endif ?>
<?php endforeach; ?>
</p>
<br />
<br />
<?php end_slot() ?>

<h2><a href="https://www.musiqueapproximative.net/login">Se connecter</a></h2>

<section class="all-tracks">
    <div class="grid-100">
        <p class="formats">
        Autre formats : 
<?php foreach ($formats as $name => $format): ?>
  <?php if ($format['display']): ?>
          <a href="<?php echo url_for(sprintf('@post_list?c=%s&q=%s&format=%s', $sf_request->getParameter('c'), $sf_request->getParameter('q'), $name)) ?>" title="<?php echo $format['contentType'] ?> <?php if ($format['about']): ?> (<?php echo $format['about'] ?>) <?php endif ?>"><?php echo $name ?></a> 
  <?php endif ?>
<?php endforeach; ?>
        </p>
        <div class="grid-50">
            <ul>
<?php $i = 0; ?>
<?php foreach ($posts as $post): ?>
                <li>
<?php if ($sf_request->hasParameter('c')): ?>
                    <a title="#<?php echo $post->id ?>" href="<?php echo url_for('@post_show?slug='.$post->slug) ?>?c=<?php echo $sf_request->getParameter('c') ?>" class="all-tracks-l">
<?php else: ?>
                    <a title="#<?php echo $post->id ?>" href="<?php echo url_for('@post_show?slug='.$post->slug) ?>" class="all-tracks-l">
<?php endif; ?>
                      <span><?php echo $post->track_author ?> 
                        <span>- 
                          <span><?php echo $post->track_title ?> 
                            <span>(<?php echo $post->getContributorDisplayName() ?>)</span>
                          </span>
                        </span>
                      </span>
                    </a>
                </li>
<?php $i++; ?>
<?php if ($i == count($posts) / 2): ?>
            </ul>
        </div>
        <div class="grid-50">
            <ul>
<?php endif; ?>
<?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
