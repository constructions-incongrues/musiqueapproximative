<?php

/**
 * Serialiseur de reponses Subsonic (protocole 1.16.1).
 *
 * Convention de structure :
 *   - valeur scalaire    -> attribut XML / cle JSON
 *   - tableau associatif -> element enfant unique
 *   - tableau indexe     -> elements repetes (XML) / tableau (JSON)
 *   - valeur null        -> omise (attribut XML absent, cle JSON absente),
 *     y compris a l'interieur d'une liste repetable (ex: un element non-
 *     tableau glisse dans 'song' est ignore plutot que transmis tel quel :
 *     voir toXml()/toJsonValue()).
 *
 * Les collections repetables sont declarees dans self::$repeatable. Sans cette
 * liste, une collection vide est indistinguable d'un objet vide en PHP (les
 * deux valent []), et json_encode() emet [] la ou les clients strictement
 * types attendent {}. Une collection *non vide* mais absente de la liste est
 * en revanche detectable et leve une exception (voir assertDeclared()) :
 * seule la variante vide echappe a ce garde-fou, faute d'un autre signal.
 *
 * Un nom de $repeatable peut aussi porter un objet UNIQUE ailleurs dans
 * l'arbre (le "album" de getAlbum, racine de la reponse, contre le "album"
 * repete d'albumList2) : c'est alors la forme de la valeur -- liste indexee
 * 0..n-1 ou non -- qui decide, pas seulement son nom (voir ::isList()).
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
   * Un nom declare dans self::$repeatable (« album », « song »...) sert dans
   * deux roles distincts selon l'endroit ou il apparait : cle d'une liste
   * (albumList2.album = plusieurs albums) ou cle d'un objet unique (le
   * getAlbum de premier niveau = un seul album, dont les champs -- id, name,
   * song... -- ne sont pas indexes 0..n-1). isRepeatable($key) seul ne peut
   * pas distinguer les deux : c'est la forme de $value qui tranche. Sans ce
   * garde-fou, un objet unique sous une cle repetable se ferait iterer champ
   * par champ (ses scalaires disparaitraient, silencieusement, cf. Task 9).
   */
  private static function isList(array $value)
  {
    return [] === $value || array_keys($value) === range(0, count($value) - 1);
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
   * @param string|null $callback Nom de la fonction JSONP. Contraint a un
   *                              identifiant JavaScript simple ; toute autre
   *                              valeur est remplacee par "callback" (un
   *                              callback arbitraire serait execute par le
   *                              navigateur : c'est une injection XSS sinon).
   * @return string
   *
   * Une valeur null, a n'importe quel niveau -- y compris a l'interieur
   * d'une liste repetable -- est omise du rendu plutot que serialisee.
   *
   * @throws InvalidArgumentException si $body contient une liste non vide
   *         sous une cle absente de self::$repeatable (voir assertDeclared).
   * @throws RuntimeException si l'encodage JSON ou le rendu XML echoue
   *         (typiquement des octets non UTF-8 dans les donnees).
   */
  public static function render(array $body, $format = 'xml', $callback = null)
  {
    $status = isset($body['status']) ? $body['status'] : 'ok';

    // Les cles de l'enveloppe sont reservees : un $body qui en porterait une
    // par erreur (ex: 'version') ne doit jamais l'ecraser.
    foreach (['status', 'version', 'type'] as $reserved) {
      unset($body[$reserved]);
    }

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

      if (false === $json) {
        throw new RuntimeException('SubsonicResponse : echec de l\'encodage JSON.');
      }

      if ('jsonp' === $format && $callback) {
        if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $callback)) {
          $callback = 'callback';
        }

        return sprintf('%s(%s);', $callback, $json);
      }

      return $json;
    }

    $xml = new SimpleXMLElement(sprintf(
      '<?xml version="1.0" encoding="UTF-8"?><subsonic-response xmlns="%s"/>',
      self::XMLNS
    ));
    self::toXml($envelope, $xml);

    $rendered = $xml->asXML();
    if (false === $rendered) {
      throw new RuntimeException('SubsonicResponse : echec du rendu XML.');
    }

    return $rendered;
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
   * Une liste (tableau indexe 0..n-1) non vide sous une cle absente de
   * self::$repeatable produirait du XML invalide (des elements nommes "0",
   * "1"...) et, en JSON, un objet {} qui masquerait silencieusement des
   * donnees -- exactement le defaut que cette classe existe pour eviter. On
   * leve donc une exception plutot que de laisser passer.
   *
   * Une liste *vide* n'a pas ce probleme en XML (rien n'est omis) mais reste
   * indiscernable d'un objet vide en JSON : ce cas ne peut pas etre detecte
   * ici. self::$repeatable reste le seul recours pour lui.
   *
   * @throws InvalidArgumentException
   */
  private static function assertDeclared($key, array $value)
  {
    if (self::isRepeatable($key) || [] === $value) {
      return;
    }

    if (array_keys($value) === range(0, count($value) - 1)) {
      throw new InvalidArgumentException(sprintf(
        'Element "%s" est une liste mais n\'est pas declare dans SubsonicResponse::$repeatable.',
        $key
      ));
    }
  }

  /**
   * Un tableau associatif vide devient un objet, sauf si sa cle est declaree
   * repetable -- auquel cas il reste un tableau.
   *
   * $key est le nom sous lequel $value est range chez son parent (null a la
   * racine). Meme concept que la variable $key de toXml(), pour que les deux
   * methodes se lisent comme des jumelles.
   */
  private static function toJsonValue($value, $key = null)
  {
    if (!is_array($value)) {
      return $value;
    }

    if (null !== $key && self::isRepeatable($key) && self::isList($value)) {
      $items = [];
      foreach ($value as $item) {
        if (!is_array($item)) {
          // Element non-tableau (ex: null issu d'un array_map qui a rate
          // une correspondance) : ignore comme n'importe quelle valeur
          // null, pour ne pas diverger du comportement de toXml() qui,
          // lui, fatalerait sur le typehint array de la recursion.
          continue;
        }
        // Pas de $key transmis ici : un element de la liste "song" n'est
        // pas lui-meme une liste "song" -- en transmettre le nom ferait
        // perdre un niveau au prochain appel.
        $items[] = self::toJsonValue($item);
      }

      return $items;
    }

    $out = [];
    foreach ($value as $childKey => $item) {
      if (null === $item) {
        continue;
      }
      if (is_array($item)) {
        self::assertDeclared($childKey, $item);
      }
      $out[$childKey] = self::toJsonValue($item, $childKey);
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

      if (is_array($value) && self::isRepeatable($key) && self::isList($value)) {
        foreach ($value as $item) {
          if (!is_array($item)) {
            // Cf. toJsonValue() : un element non-tableau est ignore plutot
            // que de faire fataler addChild()/toXml() sur son typehint array.
            continue;
          }
          $child = $parent->addChild($key);
          self::toXml($item, $child);
        }
        continue;
      }

      if (is_array($value)) {
        self::assertDeclared($key, $value);
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
