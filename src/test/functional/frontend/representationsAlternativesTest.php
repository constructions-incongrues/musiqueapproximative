<?php

/**
 * Ce que servent les formats `xspf` et `max`.
 *
 * Ces deux representations sont decrites par onze scenarios de
 * `openspec/specs/formats-de-sortie/spec.md` et aucun n'etait exerce : le test de
 * contrat ne regarde que leur statut et leur type de contenu, jamais leur contenu.
 *
 * Chaque assertion nomme le scenario qu'elle exerce, de sorte qu'un echec dise
 * quelle promesse a cesse d'etre tenue.
 *
 * @see openspec/specs/formats-de-sortie/spec.md
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(36));
$t = $browser->test();

$table = Doctrine_Core::getTable('Post');
$morceau = $table->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != ''")
  ->orderBy('p.publish_on DESC')
  ->fetchOne();

$contributeur = $morceau->getSfGuardUser()->username;

// ---------------------------------------------------------------- XSPF, liste

$t->diag('XSPF d une liste — Requirement: Representation XSPF d une liste');

$browser->get('/posts?format=xspf');
$xspf = $browser->getResponse()->getContent();

$t->like($xspf, '#<playlist[^>]*>#', 'Scenario Document servi : la racine est une playlist XSPF');
$t->like($xspf, '#encoding="utf-8"#i', 'Scenario Document servi : l encodage utf-8 est declare');
$t->is(
  trim(current(explode(';', $browser->getResponse()->getContentType()))),
  'application/xspf+xml',
  'Scenario Document servi : le type de contenu est application/xspf+xml'
);
$t->ok('' !== trim($xspf), 'Scenario Document servi : le corps n est pas vide');

$nbPistes = substr_count($xspf, '<track>');
$nbEnLigne = $table->createQuery('p')->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != ''")->count();
$t->is($nbPistes, $nbEnLigne, 'Scenario Description d un morceau : un element par morceau publiable');

$t->like($xspf, '#<location>[^<]*/tracks/#', 'Scenario Description d un morceau : l adresse absolue du fichier audio');
$t->like($xspf, '#<creator>#', 'Scenario Description d un morceau : l artiste');
$t->like($xspf, '#<title>#', 'Scenario Description d un morceau : le titre');
$t->like($xspf, '#<info>#', 'Scenario Description d un morceau : l adresse de la page');

$t->diag('XSPF — Requirement: Titre de la playlist');

$browser->get('/posts?format=xspf&c='.$contributeur);
$t->like(
  $browser->getResponse()->getContent(),
  '#<title>[^<]*'.preg_quote($contributeur, '#').'#i',
  'Scenario Titre de la playlist : filtree par contributeur, le titre le nomme'
);

$browser->get('/posts?format=xspf&q='.rawurlencode($morceau->track_author));
$t->like(
  $browser->getResponse()->getContent(),
  '#<title>[^<]*'.preg_quote($morceau->track_author, '#').'#i',
  'Scenario Titre de la playlist : sur une recherche, le titre reprend le terme'
);

$browser->get('/posts?format=xspf');
$t->like($browser->getResponse()->getContent(), '#<title>[^<]+</title>#',
  'Scenario Titre de la playlist : sans filtre, un titre designe l ensemble');

$t->diag('XSPF — Requirement: Morceau isole');

$browser->get('/post/'.$morceau->slug.'?format=xspf');
$isole = $browser->getResponse()->getContent();
$t->is(substr_count($isole, '<track>'), 1, 'Scenario Morceau isole : une playlist d un seul element');
$t->like($isole, '#<playlist[^>]*>#', 'Scenario Morceau isole : c est bien un document de playlist');

// ----------------------------------------------------------------- Max/MSP

$t->diag('Max/MSP d une liste — Requirement: Representation Max/MSP d une liste');

$browser->get('/posts?format=max');
$max = trim($browser->getResponse()->getContent());
$lignes = array_values(array_filter(explode("\n", $max), function ($l) { return '' !== trim($l); }));

$t->is(count($lignes), $nbEnLigne, 'Scenario Ligne par morceau : une ligne par morceau publiable');

// rang, puis sept champs entre guillemets
$t->like($lignes[0], '#^0(, ?)"[^"]*" "[^"]*" "[^"]*" "[^"]*" "[^"]*" "[^"]*" "[^"]*";?$#',
  'Scenario Ligne par morceau : rang puis artiste, titre, fichier, page, contributeur, total et corps');
$t->like($lignes[0], '#"'.preg_quote((string) $nbEnLigne, '#').'"#',
  'Scenario Ligne par morceau : la ligne porte le nombre total de morceaux');

$t->diag('Max/MSP d un morceau isole — Requirement: Representation Max/MSP d un morceau isole');

$browser->get('/post/'.$morceau->slug.'?format=max');
$maxIsole = trim($browser->getResponse()->getContent());
$lignesIsole = array_values(array_filter(explode("\n", $maxIsole), function ($l) { return '' !== trim($l); }));

$t->is(count($lignesIsole), 1, 'Scenario Demande du format max sur un morceau : une ligne unique');
$t->like($lignesIsole[0], '#^0(, ?)"#', 'Scenario Demande du format max sur un morceau : le rang vaut 0');
$t->like($lignesIsole[0], '#"1"#', 'Scenario Demande du format max sur un morceau : le total vaut 1');
$t->is(
  trim(current(explode(';', $browser->getResponse()->getContentType()))),
  'application/maxmsp+text',
  'Scenario Demande du format max sur un morceau : le type est application/maxmsp+text'
);

// Structure identique a celle d une liste : memes champs, meme ordre, meme
// echappement — seuls le rang et le total peuvent differer.
$champs = function ($ligne) {
  preg_match_all('#"([^"]*)"#', $ligne, $m);

  return $m[1];
};
$champsListe = $champs($lignes[0]);
$champsIsole = $champs($lignesIsole[0]);

$t->is(count($champsIsole), count($champsListe),
  'Scenario Structure identique : le meme nombre de champs qu une ligne de liste');

// Les quatre premiers champs (artiste, titre, fichier, page) et le contributeur
// ne dependent ni du rang ni du total.
// Le morceau isole est le plus recent ; c'est aussi la premiere ligne de la liste,
// qui est ordonnee du plus recent au plus ancien. Artiste, titre, adresse du fichier
// et adresse de page doivent donc etre identiques — seuls le rang et le total peuvent
// differer.
$t->is(
  array_slice($champsIsole, 0, 4),
  array_slice($champsListe, 0, 4),
  'Scenario Structure identique : artiste, titre, fichier et page au meme rang que dans la liste'
);

// ------------------------------------------- Adresse du fichier audio

$t->diag('Adresse du fichier audio — Requirement: Selection du format');

// Un morceau dont le nom de fichier porte un espace : c'est lui qui revele un
// defaut d'encodage, et les fixtures en portent un.
$espace = $table->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.track_filename LIKE '% %'")
  ->fetchOne();

$t->ok($espace, 'les fixtures portent un morceau au nom de fichier espace : sans lui ce bloc ne demontre rien');

$adresse = function ($contenu, $motif) {
  preg_match($motif, str_replace('\\/', '/', $contenu), $x);

  return isset($x[1]) ? $x[1] : null;
};

$browser->get('/post/'.$espace->slug.'?format=json');
$jsonHref = $adresse($browser->getResponse()->getContent(), '#"href":"([^"]*tracks[^"]*)"#');

$browser->get('/post/'.$espace->slug.'?format=max');
$maxHref = $adresse($browser->getResponse()->getContent(), '#"(https?://[^"]*tracks[^"]*)"#');

$browser->get('/post/'.$espace->slug.'?format=xspf');
$xspfHref = $adresse($browser->getResponse()->getContent(), '#<location>([^<]*)</location>#');

$t->is($maxHref, $jsonHref,
  'Scenario Adresse du fichier identique d une representation a l autre : max et json');
$t->is($xspfHref, $jsonHref,
  'Scenario Adresse du fichier identique d une representation a l autre : xspf et json');

$t->unlike($maxHref, '/ /',
  'Scenario Nom de fichier encode : aucun espace brut dans l adresse servie par max');
$t->like($maxHref, '/%20/',
  'Scenario Nom de fichier encode : l espace est encode en %20');

$configure = rtrim(sfConfig::get('app_urls_tracks'), '/');
$t->like($maxHref, '#'.preg_quote(ltrim($configure, '/'), '#').'#',
  'Scenario Emplacement configure honore : l adresse designe app_urls_tracks, non l hote de la requete');

// Meme morceau, seul puis dans une liste, dans la meme representation.
$browser->get('/posts?format=xspf');
$corpsListe = str_replace('\\/', '/', $browser->getResponse()->getContent());
preg_match_all('#<location>([^<]*)</location>#', $corpsListe, $toutes);

$t->ok(in_array($xspfHref, $toutes[1], true),
  'Scenario Adresse du fichier identique d une route a l autre : le morceau isole porte la meme adresse que dans la liste');

// --------------------------------------------------- Formats annonces

$t->diag('Formats annonces — Requirement: Selection du format');

$browser->get('/posts');
$liste = $browser->getResponse()->getContent();

$t->like($liste, '#<link[^>]+rel="alternate"[^>]+application/json#',
  'Scenario Formats annonces sur une page de liste : json en link alternate');
$t->like($liste, '#<link[^>]+rel="alternate"[^>]+application/xspf\+xml#',
  'Scenario Formats annonces sur une page de liste : xspf en link alternate');
$t->like($liste, '#<link[^>]+rel="alternate"[^>]+application/maxmsp\+text#',
  'Scenario Formats annonces sur une page de liste : max en link alternate');

$browser->get('/post/'.$morceau->slug);
$page = $browser->getResponse()->getContent();

// La page porte aussi des `rel="alternate"` d'oEmbed et du flux de syndication,
// qui relevent d'autres capacites. Le scenario parle des FORMATS DE SORTIE : on
// ne regarde donc que ceux-la.
$formatsDeSortie = array('application/json', 'application/xspf+xml', 'application/maxmsp+text');
$annonces = array();

foreach ($formatsDeSortie as $type) {
  if (preg_match('#<link[^>]+rel="alternate"[^>]+type="'.preg_quote($type, '#').'"#', $page)) {
    $annonces[] = $type;
  }
}

$t->is(
  implode(', ', $annonces),
  'application/json',
  'Scenario Formats annonces sur une page de morceau : json est le seul format de sortie annonce'
);

$browser->get('/post/'.$morceau->slug.'?format=xspf');
$t->is(
  $browser->getResponse()->getStatusCode(),
  200,
  'Scenario Formats annonces sur une page de morceau : xspf reste accessible sans etre annonce'
);
$browser->get('/post/'.$morceau->slug.'?format=max');
$t->is(
  $browser->getResponse()->getStatusCode(),
  200,
  'Scenario Formats annonces sur une page de morceau : max reste accessible sans etre annonce'
);
