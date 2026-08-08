<?php

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(5);

$t->diag('Plomberie de la base de test');

$connection = Doctrine_Manager::getInstance()->getCurrentConnection();
$t->is($connection->fetchOne('SELECT DATABASE()'), 'musiqueapproximative_test', 'les tests parlent bien a la base de test');
$t->is((int) $connection->fetchOne('SELECT COUNT(*) FROM post'), 9, 'les neuf posts des fixtures sont charges');

$t->diag('Regle de visibilite, via le modele');

$slugs = array();
foreach (Doctrine_Core::getTable('Post')->getOnlinePosts() as $post) {
  $slugs[] = $post->slug;
}
sort($slugs);

$t->is($slugs, array('acdc-a-b', 'anonyme', 'carol-sans-profil', 'sigur-ros-ancien', 'sigur-ros-rock-roll'), 'seuls les cinq posts visibles remontent');
$t->ok(!in_array('fantome-retire', $slugs), 'le post hors ligne est exclu');
$t->ok(!in_array('fantome-demain', $slugs), 'le post date dans le futur est exclu');
