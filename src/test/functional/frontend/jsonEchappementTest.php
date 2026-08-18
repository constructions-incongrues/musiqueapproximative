<?php

/**
 * Ce que le site garantit sur la FORME de ses reponses JSON.
 *
 * Defaut mesure en production le 18 aout 2026 : `/posts?format=json` servait un
 * document syntaxiquement invalide. `json_decode()` echouait sur « Invalid
 * \escape ». Deux morceaux sur 8 098 suffisaient — le corps de `hyacinthe-
 * retour-d-emeute-piege` porte un antislash, Markdown le rend en « &#92; », et
 * les gabarits JSON passaient le document DEJA ENCODE dans
 * `html_entity_decode()`. L'entite redevenait un « \ » nu au milieu d'une
 * chaine JSON, et le document entier cessait d'etre analysable, pour tous ses
 * consommateurs, a cause de deux morceaux.
 *
 * La suite ne l'a pas vu parce qu'aucune fixture ne portait d'antislash, et
 * parce que les tests JSON lisaient les champs sans jamais affirmer que le
 * document se laissait lire. Ce fichier tient les deux bouts : il POSE le
 * caractere qui manquait, et il n'affirme rien d'autre que l'analysabilite et
 * la fidelite du corps.
 *
 * Le morceau est insere ici plutot que dans `data/fixtures/musiqueapproximative.sql`,
 * pour la meme raison que dans unicodeTest.php : un morceau de plus dans le
 * fichier partage fait basculer les suites qui comptent des morceaux.
 *
 * @see openspec/specs/formats-de-sortie/spec.md — Requirement: Representation JSON d'un morceau
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

// Les caracteres que Markdown echappe en entites, et qui reviennent donc
// mordre quiconque decode apres avoir encode.
//
//   \\   antislash echappe en Markdown  -> « &#92; »  -> « \ » nu : le defaut
//   &    esperluette nue                -> « &amp; »  -> « & » nu : HTML casse
//   "    guillemet                      -> « &quot; » -> guillemet nu : chaine coupee
$slug = 'echappement-json-antislash';
$markdown = '\\\\ Chaque epoque a son petit diable & son "guillemet" \\\\';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(7));
$t = $browser->test();

$table = Doctrine_Core::getTable('Post');
$connexion = Doctrine_Manager::getInstance()->getCurrentConnection();

// Identifiant haut, pour ne croiser ni les fixtures ni les morceaux d'unicodeTest.
$id = 950;
$md5 = str_pad('e5ca9e', 32, '0');
$contributeur = $table->createQuery('p')->where('p.is_online = 1')->fetchOne()->contributor_id;

// Insertion parametree, et non par concatenation : `quote()` ajoute un niveau
// d'echappement que MySQL retire, et les antislashs — le sujet meme de ce test —
// n'arrivaient pas en base tels qu'ils sont ecrits ici.
$connexion->exec(
  "INSERT INTO post (id, body, track_title, track_author, track_filename, track_md5,"
  ." publish_on, is_online, contributor_id, slug, created_at, updated_at)"
  ." VALUES (?, ?, 'Retour d emeute', 'Hyacinthe', 'echappement.mp3', ?,"
  ." '2024-09-01 12:00:00', 1, ?, ?, '2024-09-01 12:00:00', '2024-09-01 12:00:00')",
  array($id, $markdown, $md5, $contributeur, $slug)
);

$t->is(
  $table->createQuery('p')->where('p.id = ?', $id)->fetchOne()->body,
  $markdown,
  'la base restitue le corps avec ses antislashs : le caractere du defaut est bien pose'
);

// Requete de chauffe. Le premier rendu Markdown d'un processus emet des
// E_DEPRECATED (markdown.php:910, syntaxe `{0}` en PHP 7.4) qui atterrissent
// dans le corps de la reponse et la rendent inanalysable — ce qui ferait
// echouer ce test pour une raison qui n'est pas la sienne. Meme contournement
// que representationJsonTest.php et unicodeTest.php, et meme defaut consigne.
$browser->get('/post/'.$slug);

/**
 * Demande une URL et rend son corps decode, ou fait echouer l'assertion.
 */
function analyser($browser, $t, $url, $intitule)
{
  $browser->get($url);
  $brut = $browser->getResponse()->getContent();
  $decode = json_decode($brut, true);

  $t->ok(
    null !== $decode,
    sprintf('%s : le document est du JSON analysable (%s)', $intitule, json_last_error_msg())
  );

  return $decode;
}

$t->diag('Requirement: Representation JSON d un morceau');

// 1. Le morceau seul.
$corps = analyser($browser, $t, '/post/'.$slug.'?format=json', 'GET /post/'.$slug.'?format=json');

// 2. La collection entiere. C'est LA regression : un seul morceau fautif y
//    rendait les 8 098 autres illisibles.
analyser($browser, $t, '/posts?format=json', 'GET /posts?format=json');

// 3. La route par empreinte, qui sert la meme enveloppe.
$parEmpreinte = analyser($browser, $t, '/post/md5/'.$md5, 'GET /post/md5/'.$md5);

// 4. Les deux designations d'un meme morceau decrivent le meme morceau. C'est
//    ce que l'echappement de vue avait rompu : le gabarit du morceau isole
//    lisait une valeur echappee, cette route une valeur brute, et le meme corps
//    en ressortait ecrit de deux facons.
//
//    La comparaison exclut `links` : cette route n'a ni precedent ni suivant a
//    donner, et le contrat le dit. Elle porte sur ce qui decrit le morceau.
function decrire($document)
{
  $morceau = isset($document['posts'][0]) ? $document['posts'][0] : array();
  unset($morceau['links'], $morceau['href']);

  return $morceau;
}

$t->is(
  decrire($corps),
  decrire($parEmpreinte),
  'par identifiant d URL ou par empreinte, le morceau est decrit a l identique'
);

// 5. Analysable ne suffit pas : le corps doit encore porter ce qu'on a ecrit.
//    L'affirmation porte sur ce que le HTML RESTITUE, non sur la facon dont il
//    l'ecrit : « \ » et « &#92; » disent la meme chose au lecteur.
$html = isset($corps['posts'][0]['body']['html']) ? $corps['posts'][0]['body']['html'] : '';

$t->ok(
  false !== strpos(html_entity_decode($html, ENT_QUOTES, 'UTF-8'), '\\'),
  'body.html restitue l antislash du corps, au lieu de le perdre ou de casser le document'
);

// 6. Et il doit etre du HTML valide : l esperluette y figure echappee. C'est ce
//    que le decodage d apres-coup detruisait, en plus du JSON.
$t->ok(
  false !== strpos($html, '&amp;') && false === strpos($html, ' & '),
  'body.html echappe l esperluette : le rendu Markdown est servi tel qu il est produit'
);
