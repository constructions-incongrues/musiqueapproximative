<?php

// Remplace le stub genere par symfony, qui interrogeait /post/index et ne
// verifiait rien. L'administration est protegee par sfGuard : la propriete
// qui merite un test est qu'elle refuse un visiteur non authentifie.

include(dirname(__FILE__).'/../../bootstrap/functional.php');

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(4));
$t = $browser->test();

$t->diag('L administration exige une authentification');

foreach (array('/post', '/post/new', '/sfGuardUser') as $url)
{
  $browser->get($url);
  $code = $browser->getResponse()->getStatusCode();

  $t->ok(
    in_array($code, array(302, 401, 404), true),
    sprintf('%s ne sert pas de contenu a un visiteur anonyme (code %s)', $url, $code)
  );
}

// Et surtout : la page servie ne doit pas contenir le formulaire d'edition.
$browser->get('/post');
$t->unlike(
  $browser->getResponse()->getContent(),
  '#name="post\[track_title\]"#',
  'le formulaire d edition n est pas expose'
);
