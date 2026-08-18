<?php

/**
 * Ce que le site conserve de ce qu'on lui confie.
 *
 * La base de PRODUCTION est en `latin1` : tout caractere hors cp1252 y est
 * remplace par « ? » a l'ecriture, definitivement. 81 morceaux ont deja un titre
 * ou un auteur detruit, 37 contributeurs sont concernes, et cinq degats datent
 * de 2026.
 *
 * Ce fichier dit ce que le site DOIT faire. Il passe en environnement de test —
 * porte en utf8mb4 par ce meme change — PENDANT QUE LA PRODUCTION RESTE CASSEE.
 * C'est voulu : l'ecart devient constatable avant d'etre repare, ce qui est la
 * seule facon de demontrer ensuite que la migration opere. Un test ecrit apres
 * la migration passerait du premier coup, sans qu'on sache s'il aurait echoue.
 *
 * Les morceaux sont poses ici plutot que dans `data/fixtures/subsonic.sql` :
 * quatre morceaux de plus dans le fichier partage font basculer 42 assertions
 * ailleurs, ces suites comptant des morceaux et des artistes.
 *
 * @see openspec/specs/catalogue-morceaux/spec.md — Requirement: Definition d'un morceau publiable
 * @see openspec/discovery.md — axe Unicode, stories 18 a 20
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

/**
 * Les quatre familles qui bornent le probleme.
 *
 * Chacune est un cas REEL du corpus de production, ou son titre est aujourd'hui
 * mutile : « Pawe? Zadro?niak », « Somnoroase P?s?rele », « ?zdemir Erdo?an ».
 * Des chaines inventees auraient exerce la meme mecanique ; celles-ci disent en
 * plus ce qui a ete perdu.
 */
$familles = array(
  'latines etendues' => array(
    'slug'   => 'unicode-pawel-zadrozniak',
    'auteur' => 'Paweł Zadrożniak',
    'titre'  => 'Zadrożniak',
    'note'   => 'ł ż — hors cp1252, dans le BMP',
  ),
  'roumain et turc' => array(
    'slug'   => 'unicode-somnoroase',
    'auteur' => 'Özdemir Erdoğan',
    'titre'  => 'Somnoroase Păsărele',
    'note'   => 'le Ö passe en cp1252, le ğ non',
  ),
  'cyrillique et ideogrammes' => array(
    'slug'   => 'unicode-piatoe-vremia',
    'auteur' => 'Сергей Прокофьев 坂本龍一',
    'titre'  => 'Пятое время года',
    'note'   => 'autres systemes d ecriture, BMP',
  ),
  'emoji' => array(
    'slug'   => 'unicode-emoji',
    'auteur' => 'Le collectif 🔥',
    'titre'  => 'Musique 🎵 en boucle 🔁',
    'note'   => 'quatre octets : ni latin1 ni utf8 sur trois octets ne les tient',
  ),
);

// Deux assertions de contexte, puis cinq par famille.
$browser = new sfTestFunctional(new sfBrowser(), new lime_test(2 + 5 * count($familles)));
$t = $browser->test();

$table = Doctrine_Core::getTable('Post');
$connexion = Doctrine_Manager::getInstance()->getCurrentConnection();

$t->diag('L environnement doit pouvoir porter ce qu on va lui demander');

$jeu = $connexion->fetchOne('SELECT @@character_set_database');
$t->is($jeu, 'utf8mb4',
  'la base de test est en utf8mb4 : utf8 tient sur trois octets et rejette les emoji');

$connexionJeu = $connexion->fetchOne("SHOW VARIABLES LIKE 'character_set_connection'", array(), 1);
$t->is($connexionJeu, 'utf8mb4',
  'la connexion aussi : sans elle MySQL convertit, et c est ce qui detruit en production');

// Poser les morceaux. Identifiants hauts pour ne croiser aucune fixture.
$id = 900;
$contributeur = $table->createQuery('p')->where('p.is_online = 1')->fetchOne()->contributor_id;

foreach ($familles as $nom => $f) {
  $connexion->exec(sprintf(
    "INSERT INTO post (id, body, track_title, track_author, track_filename, track_md5,"
    ." publish_on, is_online, contributor_id, slug, created_at, updated_at)"
    ." VALUES (%d, %s, %s, %s, 'unicode.mp3', %s, '2024-08-0%d 12:00:00', 1, %d, %s,"
    ." '2024-08-01 12:00:00', '2024-08-01 12:00:00')",
    $id,
    $connexion->quote($f['note']),
    $connexion->quote($f['titre']),
    $connexion->quote($f['auteur']),
    $connexion->quote(str_pad((string) $id, 32, 'u')),
    $id - 899,
    $contributeur,
    $connexion->quote($f['slug'])
  ));
  $familles[$nom]['id'] = $id;
  $id++;
}

// Requete de chauffe, et il faut qu'elle rende du Markdown : c'est le PREMIER
// rendu Markdown d'un processus qui emet les avertissements E_DEPRECATED, et
// ceux-ci atterrissent dans le corps de la reponse, laquelle cesse d'etre du
// JSON analysable. Une page de LISTE ne suffit pas — elle n'affiche que l'artiste
// et le titre, jamais le corps. Il faut une page de morceau.
// Defaut d'environnement, consigne en story 17 du plan de release.
$premier = reset($familles);
$browser->get('/post/'.$premier['slug']);

foreach ($familles as $nom => $f) {
  $t->diag(sprintf('%s — %s', $nom, $f['note']));

  // 1. La base restitue ce qui a ete saisi.
  $enBase = $table->createQuery('p')->where('p.id = ?', $f['id'])->fetchOne();
  $t->is(
    array($enBase->track_author, $enBase->track_title),
    array($f['auteur'], $f['titre']),
    sprintf('%s : la base restitue le morceau tel qu il a ete saisi', $nom)
  );

  // 2 a 5. Chaque representation le sert intact.
  //
  // La comparaison porte sur ce que la base contient, non sur les constantes de
  // ce fichier : une constante recopiee peut porter la meme faute que le code.
  $attenduAuteur = $enBase->track_author;
  $attenduTitre = $enBase->track_title;

  // Le JSON est traite a part : `json_encode` echappe le non-ASCII en `\uXXXX`,
  // et `\u0142` EST bien `ł`. Chercher les octets dans le corps brut echouerait
  // sur une representation pourtant correcte — c'est la valeur qu'il faut lire,
  // pas sa forme.
  $browser->get('/post/'.$f['slug'].'?format=json');
  $decode = json_decode($browser->getResponse()->getContent(), true);
  $objet = isset($decode['posts'][0]) ? $decode['posts'][0] : array();

  $t->is(
    array(
      isset($objet['track']['author']) ? $objet['track']['author'] : null,
      isset($objet['track']['title']) ? $objet['track']['title'] : null,
    ),
    array($attenduAuteur, $attenduTitre),
    sprintf('%s : JSON sert le morceau intact', $nom)
  );

  foreach (array(
    'page HTML' => '/post/'.$f['slug'],
    'XSPF'      => '/post/'.$f['slug'].'?format=xspf',
    'max'       => '/post/'.$f['slug'].'?format=max',
  ) as $representation => $url) {
    $browser->get($url);
    $corps = html_entity_decode($browser->getResponse()->getContent(), ENT_QUOTES, 'UTF-8');

    $porteAuteur = false !== strpos($corps, $attenduAuteur);
    $porteTitre = false !== strpos($corps, $attenduTitre);

    $t->ok(
      $porteAuteur && $porteTitre,
      sprintf('%s : %s sert le morceau intact, sans « ? » de substitution', $nom, $representation)
    );
  }
}
