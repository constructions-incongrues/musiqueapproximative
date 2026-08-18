<?php

/**
 * Le cout en requetes de servir une liste ne suit pas sa taille.
 *
 * Ce que ce fichier garde, et pourquoi il existe :
 *
 * `buildOnlinePostsQuery` joignait `sfGuardUser` mais jamais `UserProfile`.
 * Trois lectures par morceau retombaient donc dans la base — `Post::toJson()`
 * pour `UserProfile->website_url`, `sfGuardUser::getDisplayName()` pour
 * `UserProfile->display_name`, et `listSuccess.max.php` pour `u.username`.
 * Servir le catalogue coutait 8 271 requetes et 7,17 s la ou une seule suffit.
 *
 * Deux precautions de methode, apprises en se trompant :
 *
 * 1. VIDER L'IDENTITY MAP AVANT CHAQUE MESURE. Doctrine conserve les entites
 *    deja chargees ; sans `$conn->clear()` la seconde mesure profite de la
 *    premiere et le N+1 devient invisible. Une premiere mesure du diagnostic a
 *    conclu « ce n'est pas 8 100 requetes » pour cette exacte raison, et se
 *    trompait.
 *
 * 2. COMPARER DEUX TAILLES, PAS VISER UN NOMBRE ABSOLU. Un test qui asserait
 *    « exactement une requete » casserait au premier ajout legitime, et serait
 *    desactive plutot que compris. Ce qu'on veut demontrer n'est pas « une »,
 *    c'est « le meme quel que soit n » :
 *
 *      cout(n)  ==  cout(2n)      ->  constant, la jointure fait son travail
 *      cout(2n) ~=  2 x cout(n)   ->  N+1, il est revenu
 *
 * @see openspec/changes/archive/*-hydrater-le-contributeur-en-une-requete/
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(9);

$table = Doctrine_Core::getTable('Post');

/**
 * Compte les requetes emises par $callback, identity map videe au prealable.
 */
function compterRequetes($callback)
{
  $conn = Doctrine_Manager::connection();
  $conn->clear();

  $profiler = new Doctrine_Connection_Profiler();
  $ecouteurPrecedent = $conn->getListener();
  $conn->setListener($profiler);

  $callback();

  $conn->setListener($ecouteurPrecedent);

  $requetes = 0;
  foreach ($profiler as $evenement)
  {
    if (in_array($evenement->getName(), array('query', 'execute')))
    {
      $requetes++;
    }
  }

  return $requetes;
}

// ---------------------------------------------------------------------------
// De quoi mesurer : il faut au moins deux tailles distinctes a comparer.
// ---------------------------------------------------------------------------

$total = $table->countOnlinePosts();
$t->cmp_ok($total, '>=', 2, sprintf('les fixtures portent de quoi comparer deux tailles (%d morceaux publiables)', $total));

$petit = 1;
$grand = min($total, 4);
$t->cmp_ok($grand, '>', $petit, 'la grande taille est bien superieure a la petite');

// ---------------------------------------------------------------------------
// Le cout ne suit pas la taille de la liste.
// ---------------------------------------------------------------------------

$lireLeContributeur = function ($n) use ($table) {
  return function () use ($table, $n) {
    foreach ($table->buildOnlinePostsQuery(null, $n)->execute() as $post)
    {
      // Les trois sites de lecture qui retombaient dans la base.
      $post->getContributorDisplayName();
      $post->getSfGuardUser()->username;
      $post->getSfGuardUser()->UserProfile->website_url;
    }
  };
};

$coutPetit = compterRequetes($lireLeContributeur($petit));
$coutGrand = compterRequetes($lireLeContributeur($grand));

$t->is(
  $coutGrand,
  $coutPetit,
  sprintf(
    'le cout ne suit pas la taille : %d requetes pour %d morceaux, %d pour %d',
    $coutPetit, $petit, $coutGrand, $grand
  )
);

$t->cmp_ok(
  $coutGrand,
  '<',
  $grand,
  sprintf('le cout (%d) reste sous le nombre de morceaux (%d) : pas une requete par morceau', $coutGrand, $grand)
);

// ---------------------------------------------------------------------------
// Chaque site de lecture, isolement — pour que la regression nomme le coupable.
// ---------------------------------------------------------------------------

$sites = array(
  'nom d affichage (UserProfile->display_name)' => function ($post) { return $post->getContributorDisplayName(); },
  'identifiant (u.username)'                    => function ($post) { return $post->getSfGuardUser()->username; },
  'site web (UserProfile->website_url)'         => function ($post) { return $post->getSfGuardUser()->UserProfile->website_url; },
);

foreach ($sites as $nom => $lecture)
{
  $cout = compterRequetes(function () use ($table, $grand, $lecture) {
    foreach ($table->buildOnlinePostsQuery(null, $grand)->execute() as $post)
    {
      $lecture($post);
    }
  });

  $t->cmp_ok(
    $cout,
    '<=',
    1,
    sprintf('%s : chargee avec la liste, %d requete(s) pour %d morceaux', $nom, $cout, $grand)
  );
}

// ---------------------------------------------------------------------------
// La projection restreinte ne paie pas la jointure qu'elle n'a pas demandee.
// ---------------------------------------------------------------------------

$coutSubsonic = compterRequetes(function () use ($table, $grand) {
  foreach ($table->buildOnlinePostsQuery(null, $grand, PostTable::FIELDS_SUBSONIC)->execute() as $post)
  {
    $post->track_title;
  }
});

$t->cmp_ok(
  $coutSubsonic,
  '<=',
  1,
  sprintf('projection restreinte : %d requete(s), la jointure ne lui est pas imposee', $coutSubsonic)
);

// Contre-epreuve. Sans elle, l'assertion precedente passerait aussi si la
// projection restreinte avait ete silencieusement elargie a '*' : un cout d'une
// requete ne prouve rien si tout est charge. Doctrine 1 emet une requete par
// acces a une colonne absente du SELECT d'origine, donc lire `body` — que
// FIELDS_SUBSONIC exclut deliberement, c'est un TEXT — doit faire bouger le
// compteur. S'il ne bouge plus, la projection a ete elargie sans qu'on le veuille.
$connexion = Doctrine_Manager::getInstance()->getCurrentConnection();
$connexion->clear();
$postsSubsonic = $table->buildOnlinePostsQuery(null, $grand, PostTable::FIELDS_SUBSONIC)->execute();

$avantCorps = $connexion->count();
foreach ($postsSubsonic as $post)
{
  $post->body;
}

$t->ok(
  $connexion->count() > $avantCorps,
  'contre-epreuve : lire le corps declenche bien un lazy-load, donc la projection est restee restreinte'
);
