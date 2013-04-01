<?php use_helper('Markdown') ?>

<?php slot('browse') ?>
<a href="<?php echo url_for('post_list') ?>" class="index-toggle">Parcourir tous les morceaux (<?php echo $posts_count; ?>)</a>
<br />
<?php echo link_to(sprintf('Parcourir les morceaux de %s', $post->getContributorDisplayName()), '@post_list?c='.$sf_request->getParameter('c'), array('class' => 'index-toggle')) ?>
<?php if ($contributor->UserProfile->website_url): ?>
  <?php echo link_to('(web)', $contributor->UserProfile->website_url, array('title' => 'Accéder au site internet de '.$contributor->username)); ?>
<?php endif; ?>
<span id="close" style="display: none;"><a href=""> | fermer</a></span>
<span id="loading" style="display: none;">(chargement...)</span>
<?php end_slot() ?>

<script>
  window.trackUrl = '<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>';
</script>

<section class="content">
  <article class="grid-100">
<?php if ($post_previous): ?>
    <div class="nav-l grid-5 hide-on-mobile">
      <p>
        <a title="<?php echo sprintf('%s - %s', $post_previous->track_author, $post_previous->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_previous->slug, $sf_data->getRaw('common_query_string'))) ?>"><img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/left4.svg"></a>
      </p>
    </div>
 <?php endif; ?>

    <div class="nav-l grid-5 hide-on-desktop">
      <p>
<?php if ($post_previous): ?>
        <a title="<?php echo sprintf('%s - %s', $post_previous->track_author, $post_previous->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_previous->slug, $sf_data->getRaw('common_query_string'))) ?>">Précédent</a> /
<?php endif; ?>
<?php if ($post_next): ?>
        <a title="<?php echo sprintf('%s - %s', $post_next->track_author, $post_next->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_next->slug, $sf_data->getRaw('common_query_string'))) ?>">Suivant</a>
<?php endif; ?>
      </p>
    </div>

    <div class="grid-90 content-text">
      <h1 class="hide-on-mobile">
        <?php echo $post->track_author ?>
      </h1>
      <h1 class="hide-on-desktop">
        <?php echo $post->track_author ?>
      </h1>
      <h2 class="hide-on-mobile">
          <?php echo $post->track_title ?>
      </h2>
      <h2 class="hide-on-desktop">
          <?php echo $post->track_title ?>
      </h2>

      <div id="skin-loader"></div>
      <div id="skin-wrapper">
          <div id="jquery_jplayer_1" class="jp-jplayer"></div>
          <div id="jp_container_1" class="jp-audio">
              <div class="jp-gui jp-interface">
                  <div class="jp-progress">
                      <div class="jp-seek-bar">
                          <div class="jp-play-bar"></div>
                      </div>
                  </div>
                  <ul class="jp-controls">
                      <li>
                          <a href="javascript:;" class="jp-play" tabindex="1">play</a>
                      </li>
                      <li>
                          <a href="javascript:;" class="jp-pause" tabindex="1">pause</a>
                      </li>
                      <li>
                          <a href="#" id="random">random</a>
                      </li>
                  </ul>
                  <div class="jp-time-holder hide-on-mobile">
                      <div class="jp-duration"></div>
                      <div class="jp-current-time"></div>
                  </div>
              </div>         
          </div><!-- .jp-audio -->
      </div><!-- .wrapper -->
               
      <div class="descriptif">
        <?php echo Markdown($post->body) ?>
      </div>
      <p class="author">
        <span title="Posté le <?php echo strftime('%d/%m/%Y', $post->getDateTimeObject('created_at')->getTimestamp()) ?> à <?php echo $post->getDateTimeObject('created_at')->format('H:i') ?>">Contribué par</span> : <a rel="author" href="<?php echo url_for('@homepage?c='.$post->getSfGuardUser()->username) ?>" title="Écouter la playlist de <?php echo $post->getContributorDisplayName() ?>"><?php echo $post->getContributorDisplayName() ?></a><br />
        <a id="download" href="<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>" data-postId="<?php echo $post->id ?>">Télécharger</a>
<?php if ($post->buy_url): ?>
         / <a href="<?php echo $post->buy_url ?>" title="Soutenez l'artiste !">Acheter</a>
<?php endif ?>
      </p>
          </div>
<!-- grid-70 -->
    
<?php if ($post_next): ?>
      <div class="nav-r grid-5 hide-on-mobile">
        <p>
          <a title="<?php echo sprintf('%s - %s', $post_next->track_author, $post_next->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_next->slug, $sf_data->getRaw('common_query_string'))) ?>">
            <img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/right4.svg">
          </a>
        </p>
      </div>
      <div class="nav-r grid-5 hide-on-desktop">
        <p class="display:none;"><!-- Mobile debug --> </p>
      </div>
<?php endif; ?>
    </article>
  </section>