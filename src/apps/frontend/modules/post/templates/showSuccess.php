<?php use_helper('Markdown') ?>

<script type="text/javascript">
// Those variables are used in behaviors.js
soundManager.url   = '<?php echo $sf_request->getRelativeUrlRoot() ?>/swf/';
window.script_name = '<?php echo $sf_request->getScriptName() ?>';
window.autoplay    = <?php echo $sf_request->getParameter('play', 0) ?>;
window.random      = <?php echo $sf_request->getParameter('random', 0) ?>;
<?php if ($sf_request->getParameter('c')): ?>
window.c           = '<?php echo $sf_request->getParameter('c') ?>';
<?php endif; ?>
</script>

<div id="browse-all">
  <p>
    <?php echo link_to(sprintf('Browse all tracks (%d)', $posts_count), '@post_list', array('class' => 'index-toggle')) ?>
<?php if ($sf_request->getParameter('c')): ?>
  <a> | </a>
  <?php echo link_to(sprintf('Browse tracks by %s', $post->getContributorDisplayName()), '@post_list?contributor='.$sf_request->getParameter('c'), array('class' => 'index-toggle')) ?>
  <?php if ($contributor->UserProfile->website_url): ?>
    <a> | </a>
    <?php echo link_to($contributor->UserProfile->website_url, $contributor->UserProfile->website_url, array('title' => 'Accéder au site internet de '.$contributor->username)); ?>
  <?php endif; ?>
<?php endif; ?>
    <span id="close" style="display: none;"><a href=""> | close</a></span>
    <span id="loading" style="display: none;">(loading...)</span>
  </p>
</div>

<div id="browse">
  <form method="get" action="<?php echo url_for('@post_list') ?>">
    <input type="text" class="search" name="q" value="<?php echo $sf_request->getParameter('q') ?>"/>
    <input type="submit" class="submit" value="Search !" />
  </form>
</div>

<div id="index"></div>

<div id="posts">
  <div style="display: block;">

    <p id="navbar">
      <?php if ($post_previous): ?>
        <?php echo link_to(image_tag('left-epsilon.jpg'), sprintf('@post_show?slug=%s&%s', $post_previous->slug, $sf_data->getRaw('common_query_string')), array('class' => 'past', 'title' => sprintf('%s - %s', $post_previous->track_author, $post_previous->track_title))) ?>
      <?php endif; ?>
      <?php if ($post_next): ?>
        <?php echo link_to(image_tag('right-epsilon.jpg'), sprintf('@post_show?slug=%s&%s', $post_next->slug, $sf_data->getRaw('common_query_string')), array('class' => 'nav', 'title' => sprintf('%s - %s', $post_next->track_author, $post_next->track_title))) ?>
      <?php endif; ?>
    </p>

    <p><?php echo Markdown($post->body) ?></p>

    <p id="author">par <a href="<?php echo url_for('@homepage?c='.$post->getSfGuardUser()->username) ?>" title="Écouter la playlist de <?php echo $post->getContributorDisplayName() ?>"><?php echo $post->getContributorDisplayName() ?></a></p>

    <div>
      <div class="controls">
        <div class="statusbar">
          <div class="loading">&nbsp;</div>
          <div class="position">&nbsp;</div>
          <span id="timing" style="float:right; font-size: 0.7em;">
          	<span class="current">0:00</span> / <span class="total">0:00</span>
          </span>
        </div>
      </div>
    </div>

   <p style="clear:both;"></p>

   <p>
   	<center id="track-infos"><span title="Posté le <?php echo strftime('%d/%m/%Y', $post->getDateTimeObject('created_at')->getTimestamp()) ?> à <?php echo $post->getDateTimeObject('created_at')->format('H:i') ?>"><?php echo $post->track_author ?> - <?php echo $post->track_title ?></span></center>
   </p>

   <p>
     <a id="play" x-js-postid="<?php echo $post->id ?>" href="<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>">Play</a> |
   	 <a id="pause" href="#">Pause</a> /
   	 <a href="<?php echo sfConfig::get('app_urls_tracks') ?>/<?php echo $post->track_filename ?>">Download</a>
   	 <?php if ($post->buy_url): ?>
   	   | <a href="<?php echo $post->buy_url?>" title="Support the artist !">Buy</a>
   	 <?php endif; ?>
   	 | <a href="http://www.musiques-incongrues.net/forum/discussion/816/9/web-musique-approximative/" title="Discuter du morceau en bonne compagnie sur le forum des Musiques Incongrues">En causer</a>

   </p>
   <p style="font-size: 0.7em;">
     [<a id="random" title="Trigger random mode" class="<?php echo $sf_request->getParameter('random', false) ? '' : 'not' ?>" href="<?php echo url_for(sprintf('@post_show?slug=%s&%s', $post->slug, $sf_data->getRaw('common_query_string')))?>">Random</a>]
   </p>

  </div>
</div>
