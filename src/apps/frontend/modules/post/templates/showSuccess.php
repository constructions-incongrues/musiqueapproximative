<?php use_helper('Markdown') ?>

<?php slot('browse') ?>
<p>
Parcourir :
<a href="<?php echo url_for('@post_list') ?>">tous les morceaux</a> |
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

<?php if (sfConfig::get('app_theme', 'musiqueapproximative') == 'musiqueapproximative'): ?>
<style>
  @keyframes glitch-flash {
    0%   { opacity: 0; }
    5%   { opacity: 1; }
    10%  { opacity: 0; }
    15%  { opacity: 1; }
    20%  { opacity: 0; }
    25%  { opacity: 1; }
    30%  { opacity: 0.2; }
    35%  { opacity: 1; }
    40%  { opacity: 0.1; }
    45%  { opacity: 0.8; }
    50%  { opacity: 0; }
    100% { opacity: 0; }
  }

  @keyframes content-reveal {
    0%   { background-color: #000; }
    45%  { background-color: #000; }
    50%  { background-color: #fff; }
    100% { background-color: #fff; }
  }

  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    background-color: transparent !important;
  }

  body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('<?php echo sprintf('https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=https://www.musiqueapproximative.net/images/logo_500.png', $post->id, rand(25, 100)) ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    z-index: -1;
    opacity: 0;
    animation: glitch-flash 2s ease-out forwards;
  }

  body::after {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: #fff;
    z-index: -2;
    animation: content-reveal 2s ease-out forwards;
  }

  .grid-container,
  section.content, 
  section.content article, 
  section.content .content-text,
  section.content .descriptif,
  section.content h1,
  section.content h2,
  section.content p,
  section.content div {
    background: transparent !important;
  }
</style>
<?php endif; ?>

<section class="content">
  <article class="grid-100">
    <div class="nav-l grid-5 hide-on-mobile">
      <p>
<?php if ($post_previous): ?>
        <a title="<?php echo sprintf('%s - %s', $post_previous->track_author, $post_previous->track_title) ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post_previous->slug, $sf_data->getRaw('common_query_string'))) ?>"><img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme', 'musiqueapproximative') ?>/images/left4.svg"></a>
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
         <a id="download" href="<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>" data-postid="<?php echo $post->id ?>">Télécharger</a>
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
            <img src="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme', 'musiqueapproximative') ?>/images/right4.svg">
          </a>
<?php endif; ?>
        </p>
      </div>
      <div class="nav-r grid-5 hide-on-desktop">
        <p class="display:none;"><!-- Mobile debug --> </p>
      </div>
    </article>
  </section>
