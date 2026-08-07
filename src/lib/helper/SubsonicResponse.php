<?php

/**
 * Serialiseur de reponses Subsonic (protocole 1.16.1).
 *
 * Convention de structure :
 *   - valeur scalaire    -> attribut XML / cle JSON
 *   - tableau associatif -> element enfant unique
 *   - tableau indexe     -> elements repetes (XML) / tableau (JSON)
 *
 * Les collections repetables sont declarees dans self::$repeatable. Sans cette
 * liste, une collection vide est indistinguable d'un objet vide en PHP (les
 * deux valent []), et json_encode() emet [] la ou les clients strictement
 * types attendent {}.
 *
 * @see http://www.subsonic.org/pages/api.jsp
 */
class SubsonicResponse
{
  const API_VERSION = '1.16.1';
  const SERVER_TYPE = 'musiqueapproximative';
  const XMLNS       = 'http://subsonic.org/restapi';

  /** Noms d'elements pouvant apparaitre plusieurs fois. */
  private static $repeatable = [
    'album',
    'artist',
    'child',
    'entry',
    'index',
    'musicFolder',
    'playlist',
    'song',
  ];

  public static function isRepeatable($name)
  {
    return in_array($name, self::$repeatable, true);
  }

  /**
   * @param array $body Contenu de la reponse, sans l'enveloppe.
   * @return array
   */
  public static function ok(array $body = [])
  {
    return array_merge(['status' => 'ok'], $body);
  }

  /**
   * @param int    $code    Code d'erreur Subsonic (0, 10, 50, 70)
   * @param string $message Message lisible
   * @return array
   */
  public static function error($code, $message)
  {
    return [
      'status' => 'failed',
      'error'  => ['code' => (int) $code, 'message' => $message],
    ];
  }

  /**
   * @param array       $body     Resultat de ::ok() ou ::error()
   * @param string      $format   'xml', 'json' ou 'jsonp'
   * @param string|null $callback Nom de la fonction JSONP
   * @return string
   */
  public static function render(array $body, $format = 'xml', $callback = null)
  {
    $status = isset($body['status']) ? $body['status'] : 'ok';
    unset($body['status']);

    $envelope = array_merge(
      [
        'status'  => $status,
        'version' => self::API_VERSION,
        'type'    => self::SERVER_TYPE,
      ],
      $body
    );

    if ('json' === $format || 'jsonp' === $format) {
      $json = json_encode(['subsonic-response' => self::toJsonValue($envelope)]);

      if ('jsonp' === $format && $callback) {
        return sprintf('%s(%s);', $callback, $json);
      }

      return $json;
    }

    $xml = new SimpleXMLElement(sprintf(
      '<?xml version="1.0" encoding="UTF-8"?><subsonic-response xmlns="%s"/>',
      self::XMLNS
    ));
    self::toXml($envelope, $xml);

    return $xml->asXML();
  }

  public static function contentType($format)
  {
    switch ($format) {
      case 'json':
        return 'application/json; charset=utf-8';
      case 'jsonp':
        return 'text/javascript; charset=utf-8';
      default:
        return 'text/xml; charset=utf-8';
    }
  }

  /**
   * Un tableau associatif vide devient un objet, sauf si sa cle est declaree
   * repetable — auquel cas il reste un tableau.
   */
  private static function toJsonValue($value, $name = null)
  {
    if (!is_array($value)) {
      return $value;
    }

    if (null !== $name && self::isRepeatable($name)) {
      $items = [];
      foreach ($value as $item) {
        $items[] = self::toJsonValue($item);
      }

      return $items;
    }

    $out = [];
    foreach ($value as $key => $item) {
      if (null === $item) {
        continue;
      }
      $out[$key] = self::toJsonValue($item, $key);
    }

    return empty($out) ? new stdClass() : $out;
  }

  /**
   * addAttribute() echappe deja : ne jamais pre-echapper la valeur.
   */
  private static function toXml(array $data, SimpleXMLElement $parent)
  {
    foreach ($data as $key => $value) {
      if (null === $value) {
        continue;
      }

      if (is_array($value) && self::isRepeatable($key)) {
        foreach ($value as $item) {
          $child = $parent->addChild($key);
          self::toXml($item, $child);
        }
        continue;
      }

      if (is_array($value)) {
        $child = $parent->addChild($key);
        self::toXml($value, $child);
        continue;
      }

      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }

      $parent->addAttribute($key, (string) $value);
    }
  }
}
