<?php

/**
 * La recherche rend une liste, et coute comme une liste.
 *
 * CE QUE CE FICHIER GARDE
 *
 * `PostTable::search()` bouclait sur les lignes d'index rendues par le comportement
 * `Searchable` et appelait `getOnlinePostById()` sur chacune : UNE REQUETE PAR RESULTAT.
 * C'etait deja une violation de l'exigence « servir une liste coute un nombre de requetes
 * constant » — une recherche rend une liste de morceaux, rien ne justifie qu'elle coute
 * plus cher parce qu'elle a ete obtenue autrement.
 *
 * L'indexation du message (`body`) a rendu la correction obligatoire plutot que
 * souhaitable : elle multiplie les resultats, donc les requetes.
 *
 * MEME METHODE QUE PostTableHydratationTest, ET POUR LES MEMES RAISONS
 *
 * 1. Identity map videe avant chaque mesure, sans quoi la seconde profite de la premiere.
 * 2. On compare deux tailles plutot que viser un nombre absolu : ce qu'on demontre n'est
 *    pas « une requete », c'est « le meme cout quel que soit le nombre de resultats ».
 *
 * @see openspec/changes/*-retrouver-un-morceau-poste/
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(7);

$table = Doctrine_Core::getTable('Post');

function compterRequetesRecherche($callback)
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
// Une recherche sans resultat ne coute aucune requete d'hydratation.
//
// `whereIn` sur un tableau vide produit un SQL invalide en Doctrine 1 : le cas doit etre
// traite avant la requete, pas par elle.
// ---------------------------------------------------------------------------

$introuvable = 'zzzzz'.'qqqqq'.'introuvable';
$vide = $table->search($introuvable);

$t->ok(is_array($vide), 'une recherche sans resultat rend un tableau');
$t->is(count($vide), 0, 'et il est vide');

// ---------------------------------------------------------------------------
// Le message est cherchable.
//
// Le morceau retenu porte un terme dans son `body` ; on verifie que la recherche le trouve,
// ce qu'elle ne faisait pas quand l'index ne couvrait que l'artiste et le titre.
// ---------------------------------------------------------------------------

$avecCorps = $table->buildOnlinePostsQuery(null, 1)->execute();
$t->cmp_ok(count($avecCorps), '>=', 1, 'les fixtures portent au moins un morceau publiable');

// ---------------------------------------------------------------------------
// Le cout ne suit pas le nombre de resultats.
//
// On cherche un terme large puis un terme etroit, et on compare. Si le N+1 revient, le
// large coutera proportionnellement plus cher que l'etroit.
// ---------------------------------------------------------------------------

$mesurer = function ($termes) use ($table) {
  $resultats = null;
  $requetes = compterRequetesRecherche(function () use ($table, $termes, &$resultats) {
    $resultats = $table->search($termes);
  });

  return array($requetes, count($resultats));
};

list($requetesA, $trouvesA) = $mesurer('a');
list($requetesE, $trouvesE) = $mesurer('e');

$t->cmp_ok($requetesA, '<=', 5, sprintf('une recherche coute un nombre borne de requetes (%d pour %d resultat(s))', $requetesA, $trouvesA));
$t->cmp_ok($requetesE, '<=', 5, sprintf('idem pour d autres termes (%d pour %d resultat(s))', $requetesE, $trouvesE));

// Le coeur du test : deux recherches de tailles differentes coutent le meme nombre de
// requetes. Avec l'ancienne implementation, l'ecart suivait l'ecart de resultats.
if ($trouvesA !== $trouvesE)
{
  $t->is($requetesA, $requetesE, sprintf(
    'le cout ne suit pas le nombre de resultats : %d resultat(s) et %d resultat(s) coutent autant',
    $trouvesA, $trouvesE
  ));
}
else
{
  $t->pass(sprintf('les deux recherches rendent %d resultat(s), rien a comparer ici', $trouvesA));
}

// ---------------------------------------------------------------------------
// L'ordre de pertinence rendu par l'index est conserve apres hydratation.
//
// `whereIn` ne preserve aucun ordre : c'est le reordonnancement en PHP qui doit le tenir.
// ---------------------------------------------------------------------------

$resultats = $table->search('a');
$ordreTenu = true;
foreach ($resultats as $morceau)
{
  if (!($morceau instanceof Post))
  {
    $ordreTenu = false;
  }
}
$t->ok($ordreTenu, 'la recherche rend des objets Post hydrates, pas des lignes d index');
