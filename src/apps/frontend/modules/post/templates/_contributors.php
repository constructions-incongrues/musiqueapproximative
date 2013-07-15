<h1>Les <?php echo count($contributors) ?> contributeurs</h1>
<p>Cliquer sur le nom d'un contributeur pour écouter sa playlist</p>

<div class="grid-25">
	<ul>
<?php $i = 0; ?>
<?php foreach ($contributors as $contributor): ?>
    <li>
    	<?php echo link_to($contributor->getDisplayName(), '@homepage?c='.$contributor->username, array('title' => 'Écouter la playlist de ' . $contributor->getDisplayName())) ?>
    </li>
<?php $i++; ?>
<?php if ($i % round(count($contributors) / 4) == 0): ?>
	</ul>
</div>
<div class="grid-25">
	<ul>
<?php endif; ?>
<?php endforeach; ?>
	</ul>
</div>