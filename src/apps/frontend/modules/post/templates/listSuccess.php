<section class="all-tracks">
    <div class="grid-100">
        <div class="grid-50">
            <ul>
<?php $i = 0; ?>
<?php foreach ($posts as $post): ?>
                <li>
<?php if ($sf_request->hasParameter('c')): ?>
                    <a href="<?php echo url_for('@post_show?slug='.$post->slug) ?>?c=<?php echo $sf_request->getParameter('c') ?>" class="all-tracks-l">
<?php else: ?>
                    <a href="<?php echo url_for('@post_show?slug='.$post->slug) ?>" class="all-tracks-l">
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
