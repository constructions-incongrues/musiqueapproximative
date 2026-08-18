<?php

// Remplace le stub genere par symfony, qui interrogeait /post/index — une
// route qui n'a jamais existe dans ce projet — et ne verifiait donc rien.

include(dirname(__FILE__).'/../../bootstrap/functional.php');
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(12));
$t = $browser->test();

$t->diag('La home redirige vers le dernier post publie');

$browser->get('/');
$t->is($browser->getResponse()->getStatusCode(), 302, 'la home redirige');
$t->like($browser->getResponse()->getHttpHeader('Location'), '#/post/#', 'elle redirige vers un post');

$t->diag('La liste des posts');

$browser->get('/posts');
$t->is($browser->getResponse()->getStatusCode(), 200, '/posts repond');

$browser->get('/posts?format=json');
$t->is($browser->getResponse()->getStatusCode(), 200, '/posts?format=json repond');
$t->like($browser->getResponse()->getContentType(), '#^application/json#', 'la liste JSON est servie en application/json, comme la spec l exige');

$t->diag('Un post publie est servi, un post invisible ne l est pas');

$browser->get('/post/sigur-ros-rock-roll');
$t->is($browser->getResponse()->getStatusCode(), 200, 'un post visible est servi');

// Les fixtures portent quatre posts invisibles, chacun pour une raison
// differente. Aucun ne doit etre atteignable par son slug.
foreach (array('fantome-retire' => 'hors ligne', 'fantome-demain' => 'date dans le futur') as $slug => $raison)
{
  $browser->get('/post/'.$slug);
  $t->is($browser->getResponse()->getStatusCode(), 404, sprintf('post %s : 404', $raison));
}

$t->diag('Le flux RSS');

$browser->get('/posts/feed');
$t->is($browser->getResponse()->getStatusCode(), 200, 'le flux repond');

$contenu = $browser->getResponse()->getContent();
$t->ok(false !== strpos($contenu, '<enclosure'), 'le flux porte des enclosures');
$t->ok(false === strpos($contenu, '/tracks/un titre.mp3'), 'les URL de morceaux sont encodees, pas brutes');
$t->ok(false !== strpos($contenu, 'un%20titre.mp3'), 'l espace est encode en %20 et non en +');
