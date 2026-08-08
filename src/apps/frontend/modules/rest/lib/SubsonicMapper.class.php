<?php

/**
 * Traduction des entites du site vers les objets du protocole Subsonic.
 *
 * ::song() accepte indifferemment un Post hydrate (Doctrine_Record, acces par
 * propriete) ou un tableau associatif issu d'une requete SQL brute (les deux
 * exposent les memes cles, cf. PostTable::FIELDS_SUBSONIC) : certaines
 * requetes (agregats, jointures) ne rendent que des lignes brutes, la ou
 * d'autres rendent des Post complets. ::album(), ::artist() et ::playlist()
 * ne prennent qu'un tableau : leurs sources (getMonths(), getDistinctArtists(),
 * getContributors()) sont toutes des agregats SQL bruts, jamais des entites.
 *
 * @see http://www.subsonic.org/pages/api.jsp
 */
class SubsonicMapper
{
  const ALBUM_PREFIX = 'Musique Approximative — ';
  const ALBUM_ARTIST = 'Various Artists';

  /** Extension de fichier (minuscule) -> type MIME. */
  private static $contentTypes = [
    'mp3'  => 'audio/mpeg',
    'ogg'  => 'audio/ogg',
    'oga'  => 'audio/ogg',
    'flac' => 'audio/flac',
    'm4a'  => 'audio/mp4',
    'wav'  => 'audio/wav',
    'aac'  => 'audio/aac',
    'opus' => 'audio/opus',
  ];

  /**
   * @param array|Post $post  Ligne FIELDS_SUBSONIC ou Post hydrate.
   * @param int|null   $track Rang du morceau dans le mois -- fourni
   *                          uniquement par getAlbum() : MySQL 5.7 n'a pas de
   *                          fonctions de fenetrage, le rang n'est calculable
   *                          en PHP que la ou le mois entier est deja charge.
   * @return array
   */
  public static function song($post, $track = null)
  {
    $get = function ($key) use ($post) {
      return is_array($post) ? (isset($post[$key]) ? $post[$key] : null) : $post->$key;
    };

    $month  = substr($get('publish_on'), 0, 7);
    $suffix = strtolower(pathinfo($get('track_filename'), PATHINFO_EXTENSION));

    $song = [
      'id'          => (string) $get('id'),
      'parent'      => SubsonicId::forAlbum($month),
      'isDir'       => false,
      'title'       => $get('track_title'),
      'artist'      => $get('track_author'),
      'artistId'    => SubsonicId::forArtist($get('track_author')),
      'album'       => self::ALBUM_PREFIX.$month,
      'albumId'     => SubsonicId::forAlbum($month),
      'coverArt'    => SubsonicId::forSongCover($get('id')),
      'year'        => (int) substr($get('publish_on'), 0, 4),
      'created'     => self::iso8601($get('publish_on')),
      'suffix'      => $suffix,
      'contentType' => isset(self::$contentTypes[$suffix]) ? self::$contentTypes[$suffix] : 'application/octet-stream',
      'path'        => sprintf('%s/%s', $month, $get('track_filename')),
      'type'        => 'music',
    ];

    // Attribut absent plutot que valeur nulle : un client gere bien mieux
    // l'absence d'un attribut qu'un 0 qui laisserait croire a un morceau de
    // duree ou de taille nulle (donnees pas encore calculees, cf. schema.yml).
    if (null !== $get('track_duration')) {
      $song['duration'] = (int) $get('track_duration');
    }

    if (null !== $get('track_size')) {
      $song['size'] = (int) $get('track_size');
    }

    if (null !== $track) {
      $song['track'] = (int) $track;
    }

    return $song;
  }

  /**
   * @param array $month Ligne issue de PostTable::getMonths() (ou ::getMonth()).
   * @return array
   */
  public static function album(array $month)
  {
    $album = [
      'id'        => SubsonicId::forAlbum($month['month']),
      'name'      => self::ALBUM_PREFIX.$month['month'],
      'title'     => self::ALBUM_PREFIX.$month['month'],
      // Un mois contient plusieurs artistes et Subsonic n'en accepte qu'un.
      // Pas d'artistId : une reference pendante vers un artiste que
      // getArtists() ne rendra jamais fait planter certains clients.
      'artist'    => self::ALBUM_ARTIST,
      'coverArt'  => SubsonicId::forAlbumCover($month['month']),
      'songCount' => (int) $month['song_count'],
      'created'   => self::iso8601($month['created']),
      'year'      => (int) $month['year'],
      'isDir'     => true,
    ];

    if (null !== $month['duration']) {
      $album['duration'] = (int) $month['duration'];
    }

    return $album;
  }

  /**
   * @param array $artist Ligne issue de PostTable::getDistinctArtists().
   * @return array
   */
  public static function artist(array $artist)
  {
    return [
      'id'         => SubsonicId::forArtist($artist['track_author']),
      'name'       => $artist['track_author'],
      'albumCount' => (int) $artist['album_count'],
    ];
  }

  /**
   * @param array $contributor Ligne issue de PostTable::getContributors().
   * @return array
   */
  public static function playlist(array $contributor)
  {
    $playlist = [
      'id'        => SubsonicId::forPlaylist($contributor['username']),
      'name'      => sprintf('La playlist de %s', $contributor['display_name']),
      'owner'     => $contributor['username'],
      'public'    => true,
      'songCount' => (int) $contributor['song_count'],
      'created'   => self::iso8601($contributor['created']),
    ];

    if (null !== $contributor['duration']) {
      $playlist['duration'] = (int) $contributor['duration'];
    }

    return $playlist;
  }

  /**
   * Lettre d'index d'un nom : son initiale alphabetique (A-Z), ou « # »
   * pour tout le reste (chiffre, ponctuation, lettre accentuee, vide).
   *
   * mb_substr() prend le premier caractere en UTF-8 (pas le premier octet,
   * qui tronquerait un caractere multi-octets) ; le filtre [A-Z] qui suit
   * n'accepte que l'ASCII, donc une initiale accentuee tombe dans « # »
   * plutot que de risquer une casse mal geree caractere par caractere.
   *
   * @param string $name
   * @return string
   */
  public static function indexLetter($name)
  {
    $first = strtoupper(mb_substr(trim((string) $name), 0, 1, 'UTF-8'));

    return preg_match('/^[A-Z]$/', $first) ? $first : '#';
  }

  private static function iso8601($datetime)
  {
    return date('c', strtotime($datetime));
  }
}
