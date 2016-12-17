<?php use_helper('Markdown') ?>

<?php slot('browse') ?>
<p>
Parcourir :
<a href="<?php echo url_for('@post_list') ?>" class="index-toggle-all">tous les morceaux</a> |
<a href="<?php echo url_for('@post_list?c='.$contributor->username) ?>" class="index-toggle-contributor"><?php echo $post->getContributorDisplayName() ?></a>
<span id="loading" style="display: none;">(chargement...)</span>
</p>
<?php end_slot() ?>

<?php slot('formats_head') ?>
<?php foreach ($formats as $name => $format): ?>
  <link rel="alternate" type="<?php echo $format['contentType'] ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&format=%s', $post->slug, $name)) ?>" />
<?php endforeach; ?>
<?php end_slot() ?>

<?php slot('formats_footer') ?>
<h2>Servez-vous !</h2>
<p>Ce post est aussi disponible aux formats suivants :
<?php foreach ($formats as $name => $format): ?>
  <?php if ($format['display']): ?>
  <a href="<?php echo url_for(sprintf('@post_show?slug=%s&format=%s', $post->slug, $name)) ?>" title="<?php echo $format['contentType'] ?> <?php if ($format['about']): ?> (<?php echo $format['about'] ?>) <?php endif ?>"><?php echo $name ?></a>
  <?php endif ?>
<?php endforeach; ?>
</p>
<br />
<br />
<?php end_slot() ?>

<script>
  window.trackUrl = '<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>';
</script>

<section class="content">
  <article class="grid-100">
    <div class="nav-l grid-5 hide-on-mobile">
      <p>
<?php if ($post_previous): ?>
        <a title="<?php echo sprintf('%s - %s', $post_previous->track_author, $post_previous->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_previous->slug, $sf_data->getRaw('common_query_string'))) ?>"><img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/left4.svg"></a>
 <?php endif; ?>
      </p>
    </div>

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

      <div class="descriptif">
        <?php echo Markdown($post->body) ?>
      </div>

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
<?php if ($sf_request->getParameter('random') == '1'): ?>
                          <a href="#" id="random">random</a>
<?php else: ?>
                          <a href="#" id="random" class="not">random</a>
<?php endif; ?>
                      </li>
                  </ul>
                  <div class="jp-time-holder hide-on-mobile">
                      <div class="jp-duration"></div>
                      <div class="jp-current-time"></div>
                  </div>
              </div>
          </div><!-- .jp-audio -->
      </div><!-- .wrapper -->

      <p class="author">
        <span title="Posté le <?php echo strftime('%d/%m/%Y', $post->getDateTimeObject('created_at')->getTimestamp()) ?> à <?php echo $post->getDateTimeObject('created_at')->format('H:i') ?>">Contribué par</span> : <a rel="author" href="<?php echo url_for('@homepage?c='.$post->getSfGuardUser()->username) ?>" title="Écouter la playlist de <?php echo $post->getContributorDisplayName() ?>"><?php echo $post->getContributorDisplayName() ?></a><br />
        <a href="" title="Partager ce morceau sur Facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href), 'facebook-share-dialog', 'width=626,height=436'); return false;">Partager</a>
         / <a id="download" href="<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>" data-postid="<?php echo $post->id ?>">Télécharger</a>
<?php if ($post->buy_url): ?>
         / <a href="<?php echo $post->buy_url ?>" title="Soutenez l'artiste !">Acheter</a>
<?php endif ?>
      </p>
          </div>
<!-- grid-70 -->

      <div class="nav-r grid-5 hide-on-mobile">
        <p>
<?php if ($post_next): ?>
          <a title="<?php echo sprintf('%s - %s', $post_next->track_author, $post_next->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_next->slug, $sf_data->getRaw('common_query_string'))) ?>">
            <img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/right4.svg">
          </a>
<?php endif; ?>
        </p>
      </div>
      <div class="nav-r grid-5 hide-on-desktop">
        <p class="display:none;"><!-- Mobile debug --> </p>
      </div>
    </article>
  </section>
