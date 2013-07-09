<?php use_helper('Markdown') ?>
<?php decorate_with(false) ?>
<?php sfConfig::set('sf_web_debug', false) ?>
<style>
div.musiqueapproximative {width:500px;border:solid 1px black;background-color: #fff;}
.musiqueapproximative h3, .musiqueapproximative h4 {background-color:#000;color:#fff;padding:0.3em;margin:0;text-align: center;}
.musiqueapproximative a {color:#fff;}
.musiqueapproximative p {padding:0.3em;}
</style>
<div class="musiqueapproximative">
	<h3><a title="Voir le morceau sur le site Musique Approximative" href="<?php echo url_for('@post_show?slug='.$post->slug) ?>" target="_blank"><?php echo $post->track_author ?> - <?php echo $post->track_title ?></a></h3>
	<?php echo Markdown($post->body) ?>
	<audio controls="controls" style="width:500px;">
	  <source src="http://www.musiqueapproximative.net/tracks/<?php echo rawurlencode($post->track_filename) ?>" />
	</audio>
	<h4>Contribué par <a title="Écouter les autres morceaux contribués par <?php echo $post->getContributorDisplayName() ?>" href="<?php echo url_for('@homepage') ?>?c=<?php echo $post->getSfGuardUser()->username ?>" target="_blank"><?php echo $post->getContributorDisplayName() ?></a> sur <a href="http://www.musiqueapproximative.net">Musique Approximative</a></h4>
</div>