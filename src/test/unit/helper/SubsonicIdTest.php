<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/SubsonicId.php';

$t = new lime_test(28);

$t->diag('Morceaux');

$t->is(SubsonicId::forSong(42), '42', '::forSong() rend l id du post');
$t->is(SubsonicId::parseSong('42'), 42, '::parseSong() rend un entier');
$t->is(SubsonicId::parseSong('al-2024-06'), null, '::parseSong() refuse un id d album');
$t->is(SubsonicId::parseSong('abc'), null, '::parseSong() refuse du texte');

$t->diag('Albums');

$t->is(SubsonicId::forAlbum('2024-06'), 'al-2024-06', '::forAlbum() prefixe');
$t->is(SubsonicId::parseAlbum('al-2024-06'), '2024-06', '::parseAlbum() fait l aller-retour');
$t->is(SubsonicId::parseAlbum('al-2024-6'), null, '::parseAlbum() exige deux chiffres de mois');
$t->is(SubsonicId::parseAlbum('2024-06'), null, '::parseAlbum() exige le prefixe');

$t->diag('Artistes');

// Une assertion par nom : le total planifie ne doit pas dependre de la
// reussite ou de l echec d une iteration (contrairement a un $t->fail()
// dans la boucle suivi d un $t->pass() unique apres coup).
foreach (array('Bowie', 'Sigur Rós', 'Björk', 'AC/DC', 'Simon + Garfunkel', 'Café Tacvba') as $name) {
  $t->is(
    SubsonicId::parseArtist(SubsonicId::forArtist($name)),
    $name,
    sprintf('::forArtist()/::parseArtist() font l aller-retour pour « %s »', $name)
  );
}

$t->ok(
  false === strpos(SubsonicId::forArtist('AC/DC'), '/'),
  '::forArtist() ne produit ni / ni + (base64url)'
);
$t->ok(
  false === strpos(SubsonicId::forArtist('Simon + Garfunkel'), '+'),
  '::forArtist() ne produit pas de +'
);
$t->ok(
  false === strpos(SubsonicId::forArtist('abc'), '='),
  '::forArtist() ne produit pas de padding'
);
$t->is(SubsonicId::parseArtist('pl-tristan'), null, '::parseArtist() exige le prefixe');
$t->is(SubsonicId::parseArtist('ar-!!!!'), null, '::parseArtist() refuse un base64 invalide');
// "_w" decode en un seul octet 0xFF, qui n est pas de l UTF-8 valide : un
// decodage base64 reussi ne suffit pas, le resultat doit rester un texte
// exploitable (json_encode echoue sinon sur de l UTF-8 invalide).
$t->is(SubsonicId::parseArtist('ar-_w'), null, '::parseArtist() refuse un decodage UTF-8 invalide');
// Un track_author vide (14 posts en ligne sont dans ce cas) produit tout de
// meme un id ('ar-'), mais il n y a pas d artiste a nommer : null est la
// reponse honnete, pas un artiste fantome id="ar-" name="". C est a
// l appelant (getDistinctArtists()) de filtrer les auteurs vides en amont.
$t->is(SubsonicId::parseArtist('ar-'), null, '::parseArtist() refuse un auteur vide (rien a resoudre)');

$t->diag('Playlists et pochettes');

$t->is(SubsonicId::parsePlaylist(SubsonicId::forPlaylist('tristan')), 'tristan', 'playlist : aller-retour');
$t->is(SubsonicId::parsePlaylist('ar-tristan'), null, '::parsePlaylist() exige le prefixe');
$t->is(SubsonicId::parseCover('co-42'), array('type' => 'song', 'value' => 42), 'pochette de morceau');
$t->is(SubsonicId::parseCover('co-al-2024-06'), array('type' => 'album', 'value' => '2024-06'), 'pochette d album');
// Les deux constructeurs de pochette n etaient converts que par des
// litteraux ecrits a la main : un typo qui inverserait les prefixes dans
// ::forAlbumCover() passerait inapercu sans ce passage par le vrai builder.
$t->is(
  SubsonicId::parseCover(SubsonicId::forSongCover(42)),
  array('type' => 'song', 'value' => 42),
  '::forSongCover()/::parseCover() font l aller-retour'
);
$t->is(
  SubsonicId::parseCover(SubsonicId::forAlbumCover('2024-06')),
  array('type' => 'album', 'value' => '2024-06'),
  '::forAlbumCover()/::parseCover() font l aller-retour'
);
$t->is(SubsonicId::parseCover('co-abc'), null, '::parseCover() refuse un contenu ni album ni morceau');
