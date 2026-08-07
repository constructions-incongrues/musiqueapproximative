<?php

/**
 * Encodage et decodage des identifiants Subsonic.
 *
 * Les identifiants du protocole sont des chaines opaques : on les rend
 * reversibles pour eviter toute table de correspondance et toute requete de
 * resolution. Les noms d'artistes passent en base64url, ce qui evite le
 * hachage — lequel aurait impose de parcourir tous les track_author distincts
 * a chaque requete pour retrouver l'original.
 */
class SubsonicId
{
  const PREFIX_ALBUM    = 'al-';
  const PREFIX_ARTIST   = 'ar-';
  const PREFIX_PLAYLIST = 'pl-';
  const PREFIX_COVER    = 'co-';

  public static function forSong($postId)
  {
    return (string) $postId;
  }

  /** @param string $month Au format YYYY-MM */
  public static function forAlbum($month)
  {
    return self::PREFIX_ALBUM.$month;
  }

  public static function forArtist($author)
  {
    return self::PREFIX_ARTIST.self::encode($author);
  }

  public static function forPlaylist($username)
  {
    return self::PREFIX_PLAYLIST.$username;
  }

  public static function forSongCover($postId)
  {
    return self::PREFIX_COVER.$postId;
  }

  public static function forAlbumCover($month)
  {
    return self::PREFIX_COVER.self::PREFIX_ALBUM.$month;
  }

  /** @return int|null */
  public static function parseSong($id)
  {
    return ctype_digit((string) $id) ? (int) $id : null;
  }

  /** @return string|null Le mois au format YYYY-MM */
  public static function parseAlbum($id)
  {
    if (0 !== strpos($id, self::PREFIX_ALBUM)) {
      return null;
    }

    $month = substr($id, strlen(self::PREFIX_ALBUM));

    return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : null;
  }

  /** @return string|null Le track_author d'origine */
  public static function parseArtist($id)
  {
    if (0 !== strpos($id, self::PREFIX_ARTIST)) {
      return null;
    }

    $decoded = self::decode(substr($id, strlen(self::PREFIX_ARTIST)));

    return ('' === $decoded || null === $decoded) ? null : $decoded;
  }

  /** @return string|null Le username du contributeur */
  public static function parsePlaylist($id)
  {
    if (0 !== strpos($id, self::PREFIX_PLAYLIST)) {
      return null;
    }

    $username = substr($id, strlen(self::PREFIX_PLAYLIST));

    return '' === $username ? null : $username;
  }

  /**
   * @return array|null array('type' => 'song'|'album', 'value' => int|string)
   */
  public static function parseCover($id)
  {
    if (0 !== strpos($id, self::PREFIX_COVER)) {
      return null;
    }

    $rest = substr($id, strlen(self::PREFIX_COVER));

    if (null !== ($month = self::parseAlbum($rest))) {
      return ['type' => 'album', 'value' => $month];
    }

    if (null !== ($postId = self::parseSong($rest))) {
      return ['type' => 'song', 'value' => $postId];
    }

    return null;
  }

  public static function encode($value)
  {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
  }

  /**
   * Decodage strict : toute entree qui n'est pas du base64url valide, ou qui
   * ne redonne pas de l'UTF-8 valide une fois decodee, rend null plutot
   * qu'une chaine mojibake. Deux raisons :
   *   - un id Subsonic non resolvable doit devenir l'erreur 70 du
   *     protocole, jamais une valeur plausible construite a partir de
   *     donnees corrompues ;
   *   - ces chaines finissent dans json_encode(), qui echoue (renvoie
   *     false) sur de l'UTF-8 invalide et viderait alors toute la reponse.
   *
   * @return string|null
   */
  public static function decode($value)
  {
    $decoded = base64_decode(strtr($value, '-_', '+/'), true);

    if (false === $decoded) {
      return null;
    }

    return mb_check_encoding($decoded, 'UTF-8') ? $decoded : null;
  }
}
