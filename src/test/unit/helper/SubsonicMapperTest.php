<?php

/**
 * Test unitaire pur : aucune ligne ne touche la base, chaque source
 * (PostTable::FIELDS_SUBSONIC, ::getMonths(), ::getDistinctArtists(),
 * ::getContributors()) est simulee par un tableau construit a la main.
 */

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/rest/lib/SubsonicMapper.class.php';

$t = new lime_test(60);

// ---------------------------------------------------------------------
// song() -- a partir d'un tableau associatif (ligne FIELDS_SUBSONIC)
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::song() -- toutes les cles, depuis un tableau');

$row = array(
  'id'             => 42,
  // esperluette et chevron : verifie plus bas qu'ils survivent tels quels
  // (l'echappement est le travail de SubsonicResponse, pas du mapper).
  'track_title'    => 'Rock & Roll <Live>',
  'track_author'   => 'AC/DC',
  'track_filename' => 'un titre.mp3',
  'track_duration' => 245,
  'track_size'     => 5900000,
  'publish_on'     => '2024-06-15 12:00:00',
);

$song = SubsonicMapper::song($row);

$t->is($song['id'], '42', 'id = id du post, en chaine');
$t->is($song['parent'], 'al-2024-06', 'parent = id de l\'album du mois');
$t->is($song['isDir'], false, 'isDir = false');
$t->is($song['title'], 'Rock & Roll <Live>', 'title : passe tel quel, non echappe');
$t->is($song['artist'], 'AC/DC', 'artist : passe tel quel, non echappe');
$t->is($song['artistId'], SubsonicId::forArtist('AC/DC'), 'artistId derive de track_author');
$t->is($song['album'], 'Musique Approximative — 2024-06', 'album = prefixe + mois de publication');
$t->is($song['albumId'], 'al-2024-06', 'albumId derive du mois');
$t->is($song['coverArt'], 'co-42', 'coverArt derive de l\'id du post');
$t->is($song['year'], 2024, 'year issu de publish_on');
$t->is($song['created'], date('c', strtotime('2024-06-15 12:00:00')), 'created au format ISO 8601');
$t->is($song['suffix'], 'mp3', 'suffix issu de l\'extension du fichier');
$t->is($song['contentType'], 'audio/mpeg', 'contentType deduit du suffixe (mp3)');
$t->is($song['path'], '2024-06/un titre.mp3', 'path = mois/nom de fichier');
$t->is($song['type'], 'music', 'type = music');
$t->is($song['duration'], 245, 'duration reprise telle quelle (castee en entier)');
$t->is($song['size'], 5900000, 'size reprise telle quelle (castee en entier)');
$t->ok(!array_key_exists('track', $song), 'track absent quand non fourni a song()');

$t->diag('SubsonicMapper::song() -- duree et taille absentes plutot que nulles');

// isset() ne distinguerait pas une cle absente d'une cle presente a null :
// seul array_key_exists() prouve que la cle n'est meme pas ecrite.
$rowNoMeta = $row;
$rowNoMeta['track_duration'] = null;
$rowNoMeta['track_size']     = null;
$songNoMeta = SubsonicMapper::song($rowNoMeta);

$t->ok(!array_key_exists('duration', $songNoMeta), 'duration absente (pas null) quand track_duration est NULL');
$t->ok(!array_key_exists('size', $songNoMeta), 'size absente (pas null) quand track_size est NULL');

$t->diag('SubsonicMapper::song() -- track, uniquement fourni par l\'appelant');

$songWithTrack = SubsonicMapper::song($row, 3);
$t->ok(array_key_exists('track', $songWithTrack), 'track present quand fourni a song()');
$t->is($songWithTrack['track'], 3, 'track reprend le rang fourni, caste en entier');

// ---------------------------------------------------------------------
// song() -- meme comportement pour un objet (Post hydrate), pas seulement
// un tableau. Un stdClass simule ici l'acces par propriete d'un Post
// Doctrine sans ouvrir de connexion base de donnees.
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::song() -- accepte aussi un objet (simule un Post hydrate)');

$postLike = (object) $row;
$songFromObject = SubsonicMapper::song($postLike);

$t->is($songFromObject['id'], '42', 'objet : id identique au chemin tableau');
$t->is($songFromObject['title'], 'Rock & Roll <Live>', 'objet : title identique au chemin tableau');
$t->is($songFromObject['artist'], 'AC/DC', 'objet : artist identique au chemin tableau');
$t->ok(array_key_exists('duration', $songFromObject), 'objet : duration presente comme pour un tableau');
$t->is($songFromObject, $song, 'objet et tableau produisent exactement le meme resultat');

// ---------------------------------------------------------------------
// album() -- a partir d'une ligne PostTable::getMonths()
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::album()');

$month = array(
  'month'         => '2024-06',
  'song_count'    => 3,
  'duration'      => 485,
  'cover_post_id' => 1,
  'year'          => 2024,
  'created'       => '2024-06-01 08:00:00',
);

$album = SubsonicMapper::album($month);

$t->is($album['artist'], 'Various Artists', 'artist = "Various Artists" (un mois melange plusieurs artistes)');
$t->ok(!array_key_exists('artistId', $album), 'pas d\'artistId : reference qui ne resoudrait jamais dans getArtists()');
$t->is($album['id'], 'al-2024-06', 'id derive du mois');
$t->is($album['name'], 'Musique Approximative — 2024-06', 'name = prefixe + mois');
$t->is($album['title'], 'Musique Approximative — 2024-06', 'title identique a name');
$t->is($album['coverArt'], 'co-al-2024-06', 'coverArt derive du mois (pochette d\'album)');
$t->is($album['songCount'], 3, 'songCount reprend song_count');
$t->is($album['created'], date('c', strtotime('2024-06-01 08:00:00')), 'created au format ISO 8601');
$t->is($album['year'], 2024, 'year reprend year');
$t->is($album['isDir'], true, 'isDir = true');
$t->ok(array_key_exists('duration', $album), 'duration presente quand non nulle');
$t->is($album['duration'], 485, 'duration reprise telle quelle');

$monthNoDuration = $month;
$monthNoDuration['duration'] = null;
$albumNoDuration = SubsonicMapper::album($monthNoDuration);
$t->ok(!array_key_exists('duration', $albumNoDuration), 'duration absente (pas null) quand SUM() a rendu NULL');

// ---------------------------------------------------------------------
// artist() -- a partir d'une ligne PostTable::getDistinctArtists()
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::artist()');

$artistRow = array('track_author' => 'Sigur Rós', 'album_count' => 2);
$artist = SubsonicMapper::artist($artistRow);

$t->is($artist['id'], SubsonicId::forArtist('Sigur Rós'), 'id derive du nom d\'artiste');
$t->is($artist['name'], 'Sigur Rós', 'name reprend track_author tel quel');
$t->is($artist['albumCount'], 2, 'albumCount reprend album_count');

// ---------------------------------------------------------------------
// playlist() -- a partir d'une ligne PostTable::getContributors()
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::playlist()');

$contributor = array(
  'username'     => 'alice',
  'display_name' => 'Alice',
  'song_count'   => 2,
  'duration'     => 245,
  'created'      => '2024-05-01 09:00:00',
);

$playlist = SubsonicMapper::playlist($contributor);

$t->is($playlist['id'], 'pl-alice', 'id derive du username');
$t->is($playlist['name'], 'La playlist de Alice', 'name utilise le display_name');
$t->is($playlist['owner'], 'alice', 'owner = username');
$t->is($playlist['public'], true, 'public = true');
$t->is($playlist['songCount'], 2, 'songCount reprend song_count');
$t->is($playlist['created'], date('c', strtotime('2024-05-01 09:00:00')), 'created au format ISO 8601');
$t->ok(array_key_exists('duration', $playlist), 'duration presente quand non nulle');

$contributorNoDuration = $contributor;
$contributorNoDuration['duration'] = null;
$playlistNoDuration = SubsonicMapper::playlist($contributorNoDuration);
$t->ok(!array_key_exists('duration', $playlistNoDuration), 'duration absente (pas null) quand SUM() a rendu NULL (contributeur sans duree connue)');

// ---------------------------------------------------------------------
// indexLetter()
// ---------------------------------------------------------------------

$t->diag('SubsonicMapper::indexLetter()');

$t->is(SubsonicMapper::indexLetter('bob'), 'B', 'lettre ASCII simple : initiale majuscule');
$t->is(SubsonicMapper::indexLetter('Étoile filante'), '#', 'nom accentue : hors A-Z, bascule sur #');
$t->is(SubsonicMapper::indexLetter('2 Chainz'), '#', 'nom commencant par un chiffre : #');
$t->is(SubsonicMapper::indexLetter('!!!'), '#', 'nom commencant par une ponctuation : #');
$t->is(SubsonicMapper::indexLetter(''), '#', 'nom vide : #, sans avertissement ni exception');

$t->diag('Auteur vide : ni artist ni artistId');

// getDistinctArtists() exclut les auteurs vides, donc emettre artistId="ar-"
// offrirait un lien vers un artiste que getArtists() ne rend jamais. C'est la
// meme regle que celle appliquee a album(), qui n'emet pas d'artistId non plus.
$sansAuteur = SubsonicMapper::song(array(
  'id'             => 9,
  'track_title'    => 'Anonyme',
  'track_author'   => '',
  'track_filename' => 'anonyme.mp3',
  'track_duration' => 90,
  'track_size'     => 2000000,
  'publish_on'     => '2024-04-05 12:00:00',
));

$t->ok(!array_key_exists('artistId', $sansAuteur), 'auteur vide : pas d artistId pendant');
$t->ok(!array_key_exists('artist', $sansAuteur), 'auteur vide : pas d artist vide');
$t->is($sansAuteur['title'], 'Anonyme', 'auteur vide : le reste du morceau est intact');

$espaces = SubsonicMapper::song(array(
  'id'             => 10,
  'track_title'    => 'Espaces',
  'track_author'   => '   ',
  'track_filename' => 'espaces.mp3',
  'track_duration' => null,
  'track_size'     => null,
  'publish_on'     => '2024-04-06 12:00:00',
));

$t->ok(!array_key_exists('artistId', $espaces), 'auteur uniquement blanc : traite comme vide');
