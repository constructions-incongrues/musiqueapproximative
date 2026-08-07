# Subsonic API Support — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expose the Musique Approximative archive through a read-only Subsonic 1.16.1 API so it can be browsed and streamed from any Subsonic client.

**Architecture:** A `rest` module in the `frontend` app routes `/rest/:method(.view)` to a single dispatcher that fans out to `subsonic*` handler methods. Handlers return plain PHP arrays; a `SubsonicResponse` helper serialises them to XML or JSON. Every database read goes through `PostTable::buildOnlinePostsQuery()`, which owns the one visibility rule. Streaming is a 302 to the existing static file.

**Tech Stack:** Symfony 1.5, Doctrine 1.4, PHP 7.4, MySQL 5.7, Lime (tests), getid3 (MP3 duration).

**Spec:** `docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md`

---

## Conventions for every task

- Run commands from the repo root. Every `php symfony` command runs in the container: prefix with `docker-compose exec php`.
- Code style in this repo: 2-space indent, `array()` long syntax in `lib/model/`, `[]` short syntax in `lib/helper/`. Match the file you are editing.
- Commit after every task. Conventional Commits, message body in French.

**Test invocation.** `test:unit` and `test:functional` take the path **without** the `Test` suffix: the file `test/unit/helper/ApiResponseTest.php` runs as `test:unit helper/ApiResponse`. Passing `helper/ApiResponseTest` prints `no tests found` and exits 0 — a silent false pass. (The example in `CLAUDE.md` has this wrong.)

**Local URLs.** The plan writes `http://localhost:8080`. That is the Nginx port from `docker-compose.yml`; if another stack of this project already holds it, yours will be elsewhere. Confirm with `docker-compose ps` and substitute. The JSON list is `/posts?format=json` — `/posts.json` is a 404, format is a query parameter, not a suffix.

**Known red test before you start.** `test:unit filter/JsonApiFilter` fails 2 assertions and errors on a third. It is pre-existing — introduced in `chore: init gsd`, unrelated to this branch. Do not attribute it to your work and do not fix it unless your task says to.

## File structure

| File | Responsibility |
| --- | --- |
| `src/lib/helper/SubsonicResponse.php` | Envelope + XML/JSON/JSONP serialisation. Knows nothing about posts. |
| `src/lib/helper/SubsonicId.php` | Encode/decode the opaque ids. Pure functions. |
| `src/lib/model/doctrine/Post.class.php` | Adds `buildTrackUrl()` / `getTrackUrl()`. |
| `src/lib/model/doctrine/PostTable.class.php` | Owns the visibility rule and every Subsonic query. |
| `src/apps/frontend/modules/rest/actions/actions.class.php` | Dispatcher + one `subsonicX()` method per API method. |
| `src/apps/frontend/modules/rest/lib/SubsonicMapper.class.php` | Post/month/artist → Subsonic array. Keeps the actions thin. |
| `src/apps/frontend/modules/rest/config/cache.yml` | Disables the page cache for this module. |
| `src/lib/task/musiqueapproximativeScanTracksTask.class.php` | Batch metadata backfill. |

---

## Task 1: `Post::buildTrackUrl()` — one definition of a track URL

Two shipped endpoints currently emit URLs that do not resolve. This task fixes both and gives the Subsonic code something correct to call.

**Files:**
- Modify: `src/lib/model/doctrine/Post.class.php`
- Modify: `src/apps/frontend/modules/post/actions/actions.class.php` (lines 56, 204)
- Modify: `src/apps/frontend/modules/post/templates/listSuccess.xspf.php` (line 33)
- Create: `src/test/unit/model/PostTrackUrlTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/unit/model/PostTrackUrlTest.php`:

```php
<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(7);

sfConfig::set('app_urls_tracks', '//example.net/tracks');

$t->diag('Post::buildTrackUrl()');

$t->is(
  Post::buildTrackUrl('simple.mp3'),
  '//example.net/tracks/simple.mp3',
  '->buildTrackUrl() garde la base telle quelle par defaut'
);

$t->is(
  Post::buildTrackUrl('un titre.mp3'),
  '//example.net/tracks/un%20titre.mp3',
  '->buildTrackUrl() encode un espace en %20 et non en +'
);

$t->is(
  Post::buildTrackUrl('café.mp3'),
  '//example.net/tracks/caf%C3%A9.mp3',
  '->buildTrackUrl() encode les accents'
);

$t->is(
  Post::buildTrackUrl('rock & roll.mp3'),
  '//example.net/tracks/rock%20%26%20roll.mp3',
  '->buildTrackUrl() encode une esperluette'
);

$t->is(
  Post::buildTrackUrl('simple.mp3', 'https'),
  'https://example.net/tracks/simple.mp3',
  '->buildTrackUrl() qualifie le schema quand on le demande'
);

$t->is(
  Post::buildTrackUrl('simple.mp3', 'http'),
  'http://example.net/tracks/simple.mp3',
  '->buildTrackUrl() accepte http'
);

sfConfig::set('app_urls_tracks', 'https://cdn.example.net/tracks/');
$t->is(
  Post::buildTrackUrl('simple.mp3', 'https'),
  'https://cdn.example.net/tracks/simple.mp3',
  '->buildTrackUrl() ne double pas le schema ni la barre finale'
);
```

Note the `é` escape above is literal source text to avoid encoding surprises in this plan — type the actual character `café` when you create the file.

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:unit model/PostTrackUrl
```

Expected: FAIL — `Call to undefined method Post::buildTrackUrl()`.

- [ ] **Step 3: Add the method**

In `src/lib/model/doctrine/Post.class.php`, add immediately after `getContributorDisplayName()`:

```php
  /**
   * Construit l'URL canonique d'un fichier audio.
   *
   * Seul le nom de fichier est encode, et avec rawurlencode : dans un segment
   * de chemin, urlencode() produirait un « + » la ou il faut « %20 ».
   *
   * @param string      $filename Nom du fichier dans web/tracks/
   * @param string|null $scheme   'http' ou 'https' pour forcer une URL absolue.
   *                              Null conserve app_urls_tracks tel quel
   *                              (relatif au protocole par defaut).
   * @return string
   */
  public static function buildTrackUrl($filename, $scheme = null)
  {
    $base = rtrim(sfConfig::get('app_urls_tracks'), '/');

    if (null !== $scheme && 0 === strpos($base, '//')) {
      $base = $scheme.':'.$base;
    }

    return sprintf('%s/%s', $base, rawurlencode($filename));
  }

  /**
   * @param string|null $scheme voir self::buildTrackUrl()
   * @return string
   */
  public function getTrackUrl($scheme = null)
  {
    return self::buildTrackUrl($this->track_filename, $scheme);
  }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:unit model/PostTrackUrl
```

Expected: PASS, 7/7.

- [ ] **Step 5: Migrate `Post::toJson()`**

In `src/lib/model/doctrine/Post.class.php`, replace the `track` block (currently around line 42):

```php
    // Track
    $post['track'] = array(
      'href' =>  sprintf(
        '%s%s/tracks/%s',
        $request->getUriPrefix(),
        $request->getRelativeUrlRoot(),
        urlencode($post['track_filename'])
      ),
```

with:

```php
    // Track
    $post['track'] = array(
      'href'   => $this->getTrackUrl($request->isSecure() ? 'https' : 'http'),
```

Leave the `'title'`, `'author'` and `'md5'` entries and the `unset()` below them untouched.

- [ ] **Step 6: Migrate `executeShow()`**

In `src/apps/frontend/modules/post/actions/actions.class.php`, replace line 56:

```php
    $urlTrack = rawurlencode(sprintf('%s/%s', sfConfig::get('app_urls_tracks'), $post->track_filename));
```

with:

```php
    $urlTrack = $post->getTrackUrl($request->isSecure() ? 'https' : 'http');
```

- [ ] **Step 7: Migrate `executeFeed()`**

In the same file, replace line 204:

```php
      $track_file_url = htmlspecialchars(sprintf('%s/tracks/%s', sfConfig::get('app_url_root'), rawurlencode($post->track_filename)));
```

with:

```php
      $track_file_url = htmlspecialchars($post->getTrackUrl($request->isSecure() ? 'https' : 'http'));
```

- [ ] **Step 8: Migrate the XSPF template**

In `src/apps/frontend/modules/post/templates/listSuccess.xspf.php`, replace:

```php
	$location->setUrl(sprintf('%s%s/tracks/%s', $sf_request->getUriPrefix(), $sf_request->getRelativeUrlRoot(), rawurlencode($post->track_filename)));
```

with:

```php
	$location->setUrl($post->getTrackUrl($sf_request->isSecure() ? 'https' : 'http'));
```

- [ ] **Step 9: Verify nothing else builds a track URL**

```bash
grep -rn "tracks/" src --include=*.php | grep -v vendor | grep -v test
```

Expected: only `Post::buildTrackUrl()` remains. If another site appears, migrate it the same way.

- [ ] **Step 10: Commit**

```bash
git add src/lib/model/doctrine/Post.class.php src/apps/frontend/modules/post src/test/unit/model/PostTrackUrlTest.php
git commit -m "fix: une seule construction d'URL de morceau, correctement encodee

urlencode() sur un segment de chemin produisait « + » au lieu de « %20 » dans
toJson(), et executeShow() encodait l'URL entiere y compris ses barres obliques."
```

---

## Task 2: `SubsonicResponse` — the serialiser

**Files:**
- Create: `src/lib/helper/SubsonicResponse.php`
- Create: `src/test/unit/helper/SubsonicResponseTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/unit/helper/SubsonicResponseTest.php`:

```php
<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/SubsonicResponse.php';

$t = new lime_test(16);

$t->diag('SubsonicResponse::ok() et ::error()');

$ok = SubsonicResponse::ok(array('musicFolders' => array()));
$t->is($ok['status'], 'ok', '::ok() marque le statut');

$err = SubsonicResponse::error(70, 'Not found');
$t->is($err['status'], 'failed', '::error() marque le statut');
$t->is($err['error']['code'], 70, '::error() porte le code');

$t->diag('Rendu XML');

$xml = SubsonicResponse::render(SubsonicResponse::ok(), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc['status'], 'ok', 'XML : status en attribut');
$t->is((string) $doc['version'], '1.16.1', 'XML : version annoncee');

$xml = SubsonicResponse::render(
  SubsonicResponse::ok(array('song' => array(
    array('id' => '1', 'title' => 'Rock & Roll'),
    array('id' => '2', 'title' => 'A < B'),
  ))),
  'xml'
);
$doc = new SimpleXMLElement($xml);
$t->is(count($doc->song), 2, 'XML : une collection repetable produit N elements');
$t->is((string) $doc->song[0]['title'], 'Rock & Roll', 'XML : esperluette echappee une seule fois');
$t->is((string) $doc->song[1]['title'], 'A < B', 'XML : chevron echappe une seule fois');
$t->ok(false === strpos($xml, '&amp;amp;'), 'XML : aucun double encodage');

$xml = SubsonicResponse::render(
  SubsonicResponse::ok(array('album' => array(array('id' => 'al-2024-06', 'duration' => null)))),
  'xml'
);
$t->ok(false === strpos($xml, 'duration'), 'XML : un attribut null est omis');

$t->diag('Rendu JSON');

$json = json_decode(SubsonicResponse::render(SubsonicResponse::ok(), 'json'), true);
$t->is($json['subsonic-response']['status'], 'ok', 'JSON : enveloppe subsonic-response');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('starred2' => array())), 'json');
$t->ok(false !== strpos($raw, '"starred2":{}'), 'JSON : un conteneur vide est {} et non []');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('song' => array())), 'json');
$t->ok(false !== strpos($raw, '"song":[]'), 'JSON : une collection repetable vide reste []');

$raw = SubsonicResponse::render(
  SubsonicResponse::ok(array('song' => array(array('id' => '1')))),
  'json'
);
$decoded = json_decode($raw, true);
$t->ok(is_array($decoded['subsonic-response']['song']), 'JSON : une collection a un element reste un tableau');

$t->diag('JSONP');

$raw = SubsonicResponse::render(SubsonicResponse::ok(), 'jsonp', 'cb');
$t->ok(0 === strpos($raw, 'cb('), 'JSONP : enveloppe par le callback');
$t->ok(substr($raw, -2) === ');', 'JSONP : parenthese fermante');
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicResponse
```

Expected: FAIL — file not found / class not found.

- [ ] **Step 3: Write the implementation**

Create `src/lib/helper/SubsonicResponse.php`:

```php
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
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicResponse
```

Expected: PASS, 16/16.

- [ ] **Step 5: Commit**

```bash
git add src/lib/helper/SubsonicResponse.php src/test/unit/helper/SubsonicResponseTest.php
git commit -m "feat: serialiseur de reponses Subsonic XML/JSON/JSONP"
```

---

## Task 3: `SubsonicId` — reversible opaque ids

**Files:**
- Create: `src/lib/helper/SubsonicId.php`
- Create: `src/test/unit/helper/SubsonicIdTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/unit/helper/SubsonicIdTest.php`:

```php
<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/SubsonicId.php';

$t = new lime_test(15);

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

foreach (array('Bowie', 'Sigur Ros', 'Bjork', 'AC/DC', 'Simon + Garfunkel', 'Cafe Tacvba') as $name) {
  $id = SubsonicId::forArtist($name);
  if (SubsonicId::parseArtist($id) !== $name) {
    $t->fail(sprintf('aller-retour rate pour « %s »', $name));
    continue;
  }
}
$t->pass('::forArtist()/::parseArtist() font l aller-retour sur espaces, / et +');

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

$t->diag('Playlists et pochettes');

$t->is(SubsonicId::parsePlaylist(SubsonicId::forPlaylist('tristan')), 'tristan', 'playlist : aller-retour');
$t->is(SubsonicId::parseCover('co-42'), array('type' => 'song', 'value' => 42), 'pochette de morceau');
$t->is(SubsonicId::parseCover('co-al-2024-06'), array('type' => 'album', 'value' => '2024-06'), 'pochette d album');
```

Type the real accented names (`Sigur Rós`, `Björk`, `Café Tacvba`) when creating the file.

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicId
```

Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

Create `src/lib/helper/SubsonicId.php`:

```php
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

    return '' === $decoded ? null : $decoded;
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

  public static function decode($value)
  {
    return (string) base64_decode(strtr($value, '-_', '+/'));
  }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicId
```

Expected: PASS, 15/15.

- [ ] **Step 5: Commit**

```bash
git add src/lib/helper/SubsonicId.php src/test/unit/helper/SubsonicIdTest.php
git commit -m "feat: encodage reversible des identifiants Subsonic"
```

---

## Task 4: Schema — metadata columns and indexes

**Files:**
- Modify: `src/config/doctrine/schema.yml`
- Regenerate: `src/lib/model/doctrine/base/BasePost.class.php`

- [ ] **Step 1: Add the columns and indexes**

In `src/config/doctrine/schema.yml`, inside the `Post:` block, add the two columns after `track_md5` and add an `indexes:` block between `columns:` and `relations:`:

```yaml
    track_md5: string(32)
    track_duration:
      type: integer
      comment: Duree du morceau en secondes, nulle tant que non calculee
    track_size:
      type: integer
      comment: Taille du fichier en octets, nulle tant que non calculee
    buy_url: string(255)
```

and:

```yaml
  indexes:
    online_publish_idx:
      fields: [is_online, publish_on]
    track_author_idx:
      fields:
        track_author:
          length: 191
```

The prefix length on `track_author` is not decorative: the column is `varchar(2000)` in latin1, i.e. 2000 bytes. That fits under MySQL 5.7's 3072-byte DYNAMIC limit, but the production server version is not recorded anywhere in this repo and 191 costs nothing.

- [ ] **Step 2: Regenerate the model**

```bash
docker-compose exec php php symfony doctrine:build-model
docker-compose exec php php symfony cache:clear
```

- [ ] **Step 3: Verify the columns landed in the base class**

```bash
grep -n "track_duration\|track_size" src/lib/model/doctrine/base/BasePost.class.php
```

Expected: both appear in `setTableDefinition()`.

- [ ] **Step 4: Apply the DDL to the local database**

```bash
docker-compose exec db mysql -uroot -proot musiqueapproximative -e "
ALTER TABLE post
  ADD COLUMN track_duration INT NULL COMMENT 'Duree du morceau en secondes',
  ADD COLUMN track_size INT NULL COMMENT 'Taille du fichier en octets',
  ADD INDEX online_publish_idx (is_online, publish_on),
  ADD INDEX track_author_idx (track_author(191));
SHOW INDEX FROM post;"
```

Expected: `online_publish_idx` and `track_author_idx` listed.

**Keep this exact statement.** Task 16 puts it in the deploy runbook, and running it before shipping the model is what stops the whole site from throwing `Unknown column`.

- [ ] **Step 5: Verify the site still works**

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/posts?format=json
```

Expected: `200`.

- [ ] **Step 6: Commit**

```bash
git add src/config/doctrine/schema.yml src/lib/model/doctrine/base/BasePost.class.php
git commit -m "feat: colonnes track_duration/track_size et index (is_online, publish_on)"
```

---

## Task 5: Test database and fixtures

Without a seeded database, the most important assertion on this branch — that an unpublished post is unreachable through Subsonic — cannot be written.

**Files:**
- Modify: `src/config/databases.yml-dist`
- Modify: `etc/musiqueapproximative.localhost/.env-dist`
- Create: `src/data/fixtures/subsonic.yml`

- [ ] **Step 1: Add the test connection**

Replace the contents of `src/config/databases.yml-dist` with:

```yaml
all:
  doctrine:
    class: sfDoctrineDatabase
    param:
      dsn:      mysql:host=${DATABASE_HOST};dbname=${DATABASE_NAME}
      username: ${DATABASE_USER}
      password: ${DATABASE_PASSWORD}

test:
  doctrine:
    class: sfDoctrineDatabase
    param:
      dsn:      mysql:host=${DATABASE_HOST};dbname=${DATABASE_NAME_TEST}
      username: ${DATABASE_USER}
      password: ${DATABASE_PASSWORD}
```

- [ ] **Step 2: Add the variable to the profile template**

Append to `etc/musiqueapproximative.localhost/.env-dist`:

```
DATABASE_NAME_TEST=musiqueapproximative_test
```

Then do the same in every other `etc/*/.env-dist`. List them:

```bash
ls etc/*/.env-dist
```

- [ ] **Step 3: Regenerate the config and create the database**

```bash
docker-compose exec php make configure
docker-compose exec db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS musiqueapproximative_test CHARACTER SET utf8mb4;"
docker-compose exec php php symfony doctrine:build --sql --env=test
docker-compose exec php php symfony doctrine:insert-sql --env=test
```

Expected: tables created in `musiqueapproximative_test`.

- [ ] **Step 4: Write the fixtures**

Create `src/data/fixtures/subsonic.yml`:

```yaml
sfGuardUser:
  user_alice:
    username: alice
    algorithm: sha1
    salt: test
    password: test
    is_active: true
  user_bob:
    username: bob
    algorithm: sha1
    salt: test
    password: test
    is_active: true

UserProfile:
  profile_alice:
    user_id: user_alice
    display_name: Alice
    email: alice@example.net
    website_url: https://alice.example.net
  profile_bob:
    user_id: user_bob
    display_name: Bob
    email: bob@example.net
    website_url: https://bob.example.net

Post:
  post_june_1:
    body: Premier morceau de juin
    track_title: Rock & Roll
    track_author: Sigur Ros
    track_filename: un titre.mp3
    track_md5: aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
    track_duration: 245
    track_size: 5900000
    publish_on: '2024-06-01 12:00:00'
    is_online: true
    contributor_id: user_alice
    slug: sigur-ros-rock-roll
  post_june_2:
    body: Deuxieme morceau de juin
    track_title: A < B
    track_author: AC/DC
    track_filename: cafe & the beat.mp3
    track_md5: bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb
    track_duration: 180
    track_size: 4300000
    publish_on: '2024-06-15 12:00:00'
    is_online: true
    contributor_id: user_bob
    slug: acdc-a-b
  post_may:
    body: Un morceau de mai
    track_title: Ancien
    track_author: Sigur Ros
    track_filename: ancien.mp3
    track_md5: cccccccccccccccccccccccccccccccc
    publish_on: '2024-05-10 12:00:00'
    is_online: true
    contributor_id: user_alice
    slug: sigur-ros-ancien
  post_offline:
    body: Ne doit jamais sortir
    track_title: Retire
    track_author: Fantome
    track_filename: retire.mp3
    track_md5: dddddddddddddddddddddddddddddddd
    publish_on: '2024-06-02 12:00:00'
    is_online: false
    contributor_id: user_alice
    slug: fantome-retire
  post_future:
    body: Programme, pas encore publie
    track_title: Demain
    track_author: Fantome
    track_filename: demain.mp3
    track_md5: eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee
    publish_on: '2099-01-01 12:00:00'
    is_online: true
    contributor_id: user_alice
    slug: fantome-demain
  post_no_slug:
    body: Slug vide
    track_title: Sans slug
    track_author: Fantome
    track_filename: sans-slug.mp3
    track_md5: ffffffffffffffffffffffffffffffff
    publish_on: '2024-06-03 12:00:00'
    is_online: true
    contributor_id: user_alice
    slug: ''
```

`post_june_1` deliberately has a space in its filename, `post_june_2` an ampersand — those are the cases Task 1 fixed. `Sigur Ros`, `Fantome` and `cafe` should carry their real accents (`Sigur Rós`, `Fantôme`, `café`) when you create the file.

- [ ] **Step 5: Load and verify**

```bash
docker-compose exec php php symfony doctrine:data-load --env=test src/data/fixtures/subsonic.yml
docker-compose exec db mysql -uroot -proot musiqueapproximative_test -e "SELECT id, slug, is_online, publish_on FROM post;"
```

Expected: 6 rows, including one with `is_online = 0`, one dated 2099 and one with an empty slug.

- [ ] **Step 6: Commit**

```bash
git add src/config/databases.yml-dist etc/*/.env-dist src/data/fixtures/subsonic.yml
git commit -m "test: base de test dediee et fixtures Subsonic

Ajoute DATABASE_NAME_TEST aux profils. Sans base seedee, l'assertion « un post
hors ligne n'est pas atteignable » est impossible a ecrire."
```

---

## Task 6: `PostTable` — one visibility rule, one field contract

**Files:**
- Modify: `src/lib/model/doctrine/PostTable.class.php`
- Create: `src/test/unit/model/PostTableSubsonicTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/unit/model/PostTableSubsonicTest.php`:

```php
<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

new sfDatabaseManager(ProjectConfiguration::getActive());

$t = new lime_test(9);
$table = Doctrine_Core::getTable('Post');

$t->diag('Regle de visibilite');

$posts = $table->getOnlinePosts();
$slugs = array();
foreach ($posts as $post) {
  $slugs[] = $post->slug;
}

$t->ok(in_array('sigur-ros-rock-roll', $slugs), 'un post en ligne est visible');
$t->ok(!in_array('fantome-retire', $slugs), 'un post hors ligne est exclu');
$t->ok(!in_array('fantome-demain', $slugs), 'un post date dans le futur est exclu');
$t->ok(!in_array('', $slugs), 'un post au slug vide est exclu');

$t->diag('getMonths()');

$months = $table->getMonths();
$t->is($months[0]['month'], '2024-06', 'les mois sortent du plus recent au plus ancien');
$t->is((int) $months[0]['song_count'], 2, 'song_count ne compte que les posts visibles');
$t->is((int) $months[0]['duration'], 425, 'duration somme les durees du mois');

$t->diag('getDistinctArtists()');

$artists = $table->getDistinctArtists();
$names = array();
foreach ($artists as $artist) {
  $names[$artist['track_author']] = (int) $artist['album_count'];
}

$t->ok(!isset($names['Fantome']), 'un artiste dont tous les posts sont invisibles disparait');
$t->is($names['Sigur Ros'], 2, 'album_count compte les mois distincts');
```

Use the accented forms consistently with the fixtures.

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:unit model/PostTableSubsonic
```

Expected: FAIL — `getMonths()` undefined.

- [ ] **Step 3: Extract the visibility rule into a constant**

In `src/lib/model/doctrine/PostTable.class.php`, add below `FIELDS_BASIC`:

```php
  /**
   * Regle de visibilite unique. Les noms de colonnes etant identiques en DQL
   * et en SQL, cette expression sert aux deux : au constructeur de requetes
   * Doctrine et aux agregats en SQL brut.
   */
  const WHERE_ONLINE = "p.is_online = 1 AND p.publish_on <= DATE_ADD(NOW(), INTERVAL 2 HOUR) AND p.slug IS NOT NULL AND p.slug != ''";

  /** Champs necessaires a la serialisation Subsonic. Exclut body (TEXT). */
  const FIELDS_SUBSONIC = 'p.id, p.track_title, p.track_author, p.track_filename, p.track_duration, p.track_size, p.publish_on, p.slug, u.username';
```

- [ ] **Step 4: Make the builder public, parameterised, and rule-driven**

Replace the whole `buildOnlinePostsQuery()` method with:

```php
  /**
   * Socle de toute lecture publique de posts.
   *
   * @param string|null $contributor username, ou null
   * @param int|null    $count       LIMIT, ou null
   * @param string      $fields      liste de champs DQL, '*' par defaut
   * @return Doctrine_Query
   */
  public function buildOnlinePostsQuery($contributor = null, $count = null, $fields = '*')
  {
    $q = Doctrine_Query::create()
      ->select($fields)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where(self::WHERE_ONLINE)
      ->orderBy('p.publish_on DESC');

    if ($contributor) {
      $q->andWhere('u.username = ?', (string) $contributor);
    }

    if ($count) {
      $q->limit($count);
    }

    return $q;
  }
```

Note this drops the separate `andWhere` for the slug — it now lives in `WHERE_ONLINE`. Behaviour is identical.

- [ ] **Step 5: Add the aggregate queries**

Append to the same class, before the closing brace:

```php
  /**
   * Liste des mois de publication, du plus recent au plus ancien.
   *
   * Agregat en SQL brut : Doctrine 1 hydrate mal un GROUP BY sans entite, et
   * MySQL 5.7 active ONLY_FULL_GROUP_BY, ce qui interdit de conserver le
   * ORDER BY p.publish_on du socle.
   *
   * @return array [['month' => '2024-06', 'song_count' => 2, 'duration' => 425], ...]
   */
  public function getMonths($limit = null, $offset = 0, $order = 'DESC')
  {
    $sql = sprintf(
      "SELECT DATE_FORMAT(p.publish_on, '%%Y-%%m') AS month,
              COUNT(p.id) AS song_count,
              SUM(p.track_duration) AS duration,
              MIN(p.id) AS first_post_id,
              YEAR(MIN(p.publish_on)) AS year,
              MIN(p.publish_on) AS created
         FROM post p
        WHERE %s
     GROUP BY month
     ORDER BY month %s",
      self::WHERE_ONLINE,
      'ASC' === strtoupper($order) ? 'ASC' : 'DESC'
    );

    if (null !== $limit) {
      $sql .= sprintf(' LIMIT %d OFFSET %d', (int) $limit, (int) $offset);
    }

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
  }

  /**
   * @return array|null La ligne du mois demande, ou null s'il est vide.
   */
  public function getMonth($month)
  {
    foreach ($this->getMonths() as $row) {
      if ($row['month'] === $month) {
        return $row;
      }
    }

    return null;
  }

  /** @return Doctrine_Collection */
  public function getPostsByMonth($month)
  {
    return $this->buildOnlinePostsQuery(null, null, self::FIELDS_SUBSONIC)
      ->andWhere("DATE_FORMAT(p.publish_on, '%Y-%m') = ?", $month)
      ->orderBy('p.publish_on ASC')
      ->execute();
  }

  /**
   * @return array [['track_author' => 'X', 'album_count' => 2], ...]
   */
  public function getDistinctArtists($like = null, $limit = null, $offset = 0)
  {
    $params = array();
    $sql = sprintf(
      "SELECT p.track_author,
              COUNT(DISTINCT DATE_FORMAT(p.publish_on, '%%Y-%%m')) AS album_count
         FROM post p
        WHERE %s",
      self::WHERE_ONLINE
    );

    if (null !== $like) {
      $sql .= ' AND p.track_author LIKE ?';
      $params[] = '%'.$like.'%';
    }

    $sql .= ' GROUP BY p.track_author ORDER BY p.track_author ASC';

    if (null !== $limit) {
      $sql .= sprintf(' LIMIT %d OFFSET %d', (int) $limit, (int) $offset);
    }

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql, $params);
  }

  /** @return array Liste de mois ['month' => ..., 'song_count' => ...] */
  public function getMonthsByArtist($author)
  {
    $sql = sprintf(
      "SELECT DATE_FORMAT(p.publish_on, '%%Y-%%m') AS month,
              COUNT(p.id) AS song_count,
              SUM(p.track_duration) AS duration,
              MIN(p.id) AS first_post_id,
              YEAR(MIN(p.publish_on)) AS year,
              MIN(p.publish_on) AS created
         FROM post p
        WHERE %s AND p.track_author = ?
     GROUP BY month
     ORDER BY month DESC",
      self::WHERE_ONLINE
    );

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql, array($author));
  }

  /** @return Doctrine_Collection */
  public function getPostsByArtist($author, $limit = null, $offset = 0)
  {
    $q = $this->buildOnlinePostsQuery(null, null, self::FIELDS_SUBSONIC)
      ->andWhere('p.track_author = ?', $author);

    if (null !== $limit) {
      $q->limit($limit)->offset($offset);
    }

    return $q->execute();
  }

  /**
   * Contributeurs ayant au moins un post visible.
   *
   * @return array [['username' => 'alice', 'display_name' => 'Alice',
   *                 'song_count' => 2, 'duration' => 425,
   *                 'created' => '2024-05-10 12:00:00'], ...]
   */
  public function getContributors()
  {
    $sql = sprintf(
      "SELECT u.username,
              COALESCE(pr.display_name, u.username) AS display_name,
              COUNT(p.id) AS song_count,
              SUM(p.track_duration) AS duration,
              MIN(p.publish_on) AS created
         FROM post p
         JOIN sf_guard_user u ON u.id = p.contributor_id
    LEFT JOIN user_profile pr ON pr.user_id = u.id
        WHERE %s
     GROUP BY u.username, display_name
     ORDER BY u.username ASC",
      self::WHERE_ONLINE
    );

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
  }

  /**
   * Recherche de morceaux par titre ou auteur.
   *
   * N'utilise pas self::search() : celle-ci ne renvoie que des identifiants de
   * posts classes — donc aucun resultat « artiste » — et declenche une requete
   * par resultat, sans borne.
   *
   * @return Doctrine_Collection
   */
  public function searchSongs($query, $limit = 20, $offset = 0)
  {
    $q = $this->buildOnlinePostsQuery(null, null, self::FIELDS_SUBSONIC);

    if ('' !== (string) $query) {
      $q->andWhere('(p.track_title LIKE ? OR p.track_author LIKE ?)', array('%'.$query.'%', '%'.$query.'%'));
    }

    return $q->limit($limit)->offset($offset)->execute();
  }
```

- [ ] **Step 6: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:unit model/PostTableSubsonic
```

Expected: PASS, 9/9.

- [ ] **Step 7: Verify the site still works**

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/posts?format=json
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/posts/feed
```

Expected: `200` twice. `buildOnlinePostsQuery()` changed signature and visibility — this confirms existing callers still work.

- [ ] **Step 8: Commit**

```bash
git add src/lib/model/doctrine/PostTable.class.php src/test/unit/model/PostTableSubsonicTest.php
git commit -m "feat: requetes Subsonic sur un socle de visibilite unique

WHERE_ONLINE devient la seule expression de la regle. buildOnlinePostsQuery()
devient publique et accepte une liste de champs, ce qui evite le chargement
paresseux colonne par colonne de Doctrine 1."
```

---

## Task 7: The `rest` module — routes, dispatcher, configuration

**Files:**
- Modify: `src/apps/frontend/config/routing.yml`
- Create: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Create: `src/apps/frontend/modules/rest/config/cache.yml`
- Modify: `src/lib/filter/JsonApiFilter.class.php`
- Create: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/functional/frontend/restActionsTest.php`:

```php
<?php

include(dirname(__FILE__).'/../../bootstrap/functional.php');

$browser = new sfTestFunctional(new sfBrowser());

$t = $browser->test();

// --- ping ---

$browser->get('/rest/ping.view');
$t->is($browser->getResponse()->getStatusCode(), 200, 'ping repond 200');

$xml = new SimpleXMLElement($browser->getResponse()->getContent());
$t->is((string) $xml['status'], 'ok', 'ping : status ok en XML');

$browser->get('/rest/ping');
$t->is($browser->getResponse()->getStatusCode(), 200, 'la route sans .view fonctionne aussi');

$browser->get('/rest/ping.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['status'], 'ok', 'ping : status ok en JSON');
$t->like($browser->getResponse()->getHttpHeader('Content-Type'), '#application/json#', 'Content-Type JSON preserve');

// --- erreurs ---

$browser->get('/rest/getNothing.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($browser->getResponse()->getStatusCode(), 200, 'une methode inconnue repond quand meme 200');
$t->is($json['subsonic-response']['status'], 'failed', 'methode inconnue : status failed');
$t->is($json['subsonic-response']['error']['code'], 70, 'methode inconnue : code 70');

$browser->get('/rest/getAlbum.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 10, 'parametre requis manquant : code 10');

$browser->get('/rest/star.view?f=json&id=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 50, 'star : code 50 (lecture seule)');

// --- pas de fuite de contenu invisible ---

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$months = array();
foreach ($json['subsonic-response']['albumList2']['album'] as $album) {
  $months[] = $album['id'];
}
$t->ok(!in_array('al-2099-01', $months), 'aucun mois issu d un post futur');
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: FAIL — 404 on `/rest/ping.view`.

- [ ] **Step 3: Add the routes**

At the **top** of `src/apps/frontend/config/routing.yml`, before `homepage:` (order matters — `post_show` would otherwise swallow these):

```yaml
subsonic_view:
  url:   /rest/:method.view
  param: { module: rest, action: index }
  requirements: { method: \w+ }

subsonic:
  url:   /rest/:method
  param: { module: rest, action: index }
  requirements: { method: \w+ }
```

- [ ] **Step 4: Disable the page cache for the module**

Create `src/apps/frontend/modules/rest/config/cache.yml`:

```yaml
# Le cache.yml applicatif declare default: { enabled: true, lifetime: 86400 }
# et s'applique a TOUS les modules. Sa cle de cache ne contient que les
# parametres de route : id, query, size, offset, f et callback en sont absents,
# donc getAlbum?id=al-2024-06 et ?id=al-2019-03 partageraient la meme entree.
default:
  enabled: false
```

- [ ] **Step 5: Guard `JsonApiFilter`**

In `src/lib/filter/JsonApiFilter.class.php`, insert immediately after `$filterChain->execute();`:

```php
    // Le module rest sert du Subsonic : ses reponses JSON doivent rester
    // application/json, pas application/vnd.api+json.
    if ('rest' === $this->context->getModuleName()) {
      return;
    }
```

- [ ] **Step 6: Write the dispatcher**

Create `src/apps/frontend/modules/rest/actions/actions.class.php`:

```php
<?php

/**
 * API Subsonic 1.16.1, en lecture seule.
 *
 * Repartition : le nom de methode recu dans l'URL est prefixe par « subsonic »
 * puis resolu sur une methode protegee de cette classe. Le prefixe sert de
 * liste blanche implicite — aucune methode arbitraire de sfActions n'est
 * atteignable depuis l'URL.
 *
 *   /rest/getAlbum.view  ->  subsonicGetAlbum()
 *
 * Chaque gestionnaire renvoie un tableau PHP. Il ne touche ni a la reponse,
 * ni a la serialisation, ni aux en-tetes.
 *
 * @see docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md
 */
class restActions extends sfActions
{
  /** Plafond impose a tout parametre de taille. */
  const MAX_SIZE = 500;

  public function executeIndex(sfWebRequest $request)
  {
    sfConfig::set('sf_web_debug', false);

    $format   = $this->resolveFormat($request);
    $callback = $request->getParameter('callback');
    $method   = 'subsonic'.ucfirst($request->getParameter('method'));

    try {
      if (!method_exists($this, $method)) {
        $body = SubsonicResponse::error(70, 'Requested method not found.');
      } else {
        $body = $this->$method($request);
      }
    } catch (SubsonicException $e) {
      $body = SubsonicResponse::error($e->getCode(), $e->getMessage());
    }

    // Un gestionnaire qui a deja emis sa reponse (stream, getCoverArt)
    // renvoie null.
    if (null === $body) {
      return sfView::NONE;
    }

    $response = $this->getResponse();
    $response->setContentType(SubsonicResponse::contentType($format));
    // L'API varie par query string : ni Symfony ni Cloudflare ne doivent la
    // mettre en cache.
    $response->setHttpHeader('Cache-Control', 'no-store');

    return $this->renderText(SubsonicResponse::render($body, $format, $callback));
  }

  protected function resolveFormat(sfWebRequest $request)
  {
    $format = strtolower($request->getParameter('f', 'xml'));

    if ('jsonp' === $format || ('json' === $format && $request->getParameter('callback'))) {
      return 'jsonp';
    }

    return 'json' === $format ? 'json' : 'xml';
  }

  /**
   * @throws SubsonicException code 10 si le parametre est absent
   */
  protected function requireParameter(sfWebRequest $request, $name)
  {
    $value = $request->getParameter($name);

    if (null === $value || '' === $value) {
      throw new SubsonicException(sprintf('Required parameter "%s" is missing.', $name), 10);
    }

    return $value;
  }

  /** Borne une taille demandee par un client. */
  protected function boundedSize(sfWebRequest $request, $name = 'size', $default = 10)
  {
    $size = (int) $request->getParameter($name, $default);

    if ($size < 1) {
      return $default;
    }

    return min($size, self::MAX_SIZE);
  }

  protected function offset(sfWebRequest $request, $name = 'offset')
  {
    return max(0, (int) $request->getParameter($name, 0));
  }

  // --- Methodes triviales ---------------------------------------------------

  protected function subsonicPing(sfWebRequest $request)
  {
    return SubsonicResponse::ok();
  }

  protected function subsonicGetLicense(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['license' => ['valid' => true]]);
  }

  protected function subsonicGetMusicFolders(sfWebRequest $request)
  {
    return SubsonicResponse::ok([
      'musicFolders' => ['musicFolder' => [['id' => 0, 'name' => 'Musique Approximative']]],
    ]);
  }

  // --- Talons ---------------------------------------------------------------
  // Les clients les appellent au demarrage ; une erreur y produit des popups
  // inutiles. On repond vide plutot qu'en echec.

  protected function subsonicGetUser(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['user' => [
      'username'       => $request->getParameter('u', 'guest'),
      'scrobblingEnabled' => false,
      'adminRole'      => false,
      'settingsRole'   => false,
      'downloadRole'   => true,
      'uploadRole'     => false,
      'playlistRole'   => false,
      'coverArtRole'   => true,
      'commentRole'    => false,
      'podcastRole'    => false,
      'streamRole'     => true,
      'jukeboxRole'    => false,
      'shareRole'      => false,
    ]]);
  }

  protected function subsonicGetStarred(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['starred' => []]);
  }

  protected function subsonicGetStarred2(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['starred2' => []]);
  }

  protected function subsonicGetGenres(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['genres' => []]);
  }

  protected function subsonicGetNowPlaying(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['nowPlaying' => []]);
  }

  protected function subsonicGetVideos(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['videos' => []]);
  }

  protected function subsonicScrobble(sfWebRequest $request)
  {
    return SubsonicResponse::ok();
  }

  // --- Refusees -------------------------------------------------------------

  protected function subsonicStar(sfWebRequest $request)
  {
    return $this->readOnly();
  }

  protected function subsonicUnstar(sfWebRequest $request)
  {
    return $this->readOnly();
  }

  protected function subsonicCreatePlaylist(sfWebRequest $request)
  {
    return $this->readOnly();
  }

  protected function subsonicUpdatePlaylist(sfWebRequest $request)
  {
    return $this->readOnly();
  }

  protected function subsonicDeletePlaylist(sfWebRequest $request)
  {
    return $this->readOnly();
  }

  protected function readOnly()
  {
    return SubsonicResponse::error(50, 'This server is read-only.');
  }
}
```

- [ ] **Step 7: Add the exception class**

Create `src/lib/helper/SubsonicException.php`:

```php
<?php

/**
 * Erreur applicative Subsonic. Le code de l'exception est le code d'erreur du
 * protocole (10 parametre manquant, 50 non autorise, 70 introuvable).
 */
class SubsonicException extends Exception
{
}
```

- [ ] **Step 8: Clear the cache and run the test**

```bash
docker-compose exec php php symfony cache:clear
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: the ping, error, and read-only assertions pass. The final `getAlbumList2` assertion still fails — that method arrives in Task 9.

- [ ] **Step 9: Verify the page cache really is off**

```bash
docker-compose exec php php symfony cache:clear
curl -s 'http://localhost:8080/rest/ping.view?f=json' | head -c 120; echo
curl -s 'http://localhost:8080/rest/getLicense.view?f=json' | head -c 120; echo
```

Expected: two different bodies. If the second returns the ping response, `modules/rest/config/cache.yml` is not being read — check the path.

- [ ] **Step 10: Measure the session behaviour**

The review flagged that `sfDesastreFilter` touches `sfUser` on every request, which starts a session. Symfony 1 has no per-module session switch, so establish whether this is new or pre-existing:

```bash
curl -sI 'http://localhost:8080/posts?format=json' | grep -i set-cookie || echo "pas de cookie sur la liste JSON"
curl -sI 'http://localhost:8080/rest/ping.view' | grep -i set-cookie || echo "pas de cookie sur /rest"
```

If both set a cookie, this is site-wide pre-existing behaviour: record it in `TODOS.md` as an item ("sessions créées pour chaque requête API — envisager une application `api` dédiée si le volume de fichiers de session devient un problème") and move on. If only `/rest` sets one, stop and investigate before continuing.

- [ ] **Step 11: Commit**

```bash
git add src/apps/frontend/config/routing.yml src/apps/frontend/modules/rest src/lib/filter/JsonApiFilter.class.php src/lib/helper/SubsonicException.php src/test/functional/frontend/restActionsTest.php
git commit -m "feat: module rest, repartiteur Subsonic et sortie du cache de pages"
```

---

## Task 8: `SubsonicMapper` — entities to protocol arrays

**Files:**
- Create: `src/apps/frontend/modules/rest/lib/SubsonicMapper.class.php`
- Create: `src/test/unit/helper/SubsonicMapperTest.php`

- [ ] **Step 1: Write the failing test**

Create `src/test/unit/helper/SubsonicMapperTest.php`:

```php
<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_root_dir').'/apps/frontend/modules/rest/lib/SubsonicMapper.class.php';

$t = new lime_test(12);

sfConfig::set('app_urls_tracks', '//example.net/tracks');

$row = array(
  'id'             => 42,
  'track_title'    => 'Rock & Roll',
  'track_author'   => 'Sigur Ros',
  'track_filename' => 'un titre.mp3',
  'track_duration' => 245,
  'track_size'     => 5900000,
  'publish_on'     => '2024-06-01 12:00:00',
);

$t->diag('SubsonicMapper::song()');

$song = SubsonicMapper::song($row);

$t->is($song['id'], '42', 'id = id du post');
$t->is($song['title'], 'Rock & Roll', 'titre brut, non echappe');
$t->is($song['artist'], 'Sigur Ros', 'artiste = track_author');
$t->is($song['album'], 'Musique Approximative — 2024-06', 'album = mois de publication');
$t->is($song['albumId'], 'al-2024-06', 'albumId derive du mois');
$t->is($song['year'], 2024, 'annee issue de publish_on');
$t->is($song['suffix'], 'mp3', 'suffixe issu de l extension');
$t->is($song['contentType'], 'audio/mpeg', 'contentType deduit du suffixe');
$t->is($song['duration'], 245, 'duree reprise telle quelle');
$t->ok(!isset($song['track']), 'pas de numero de piste hors getAlbum');

$row['track_duration'] = null;
$row['track_size']     = null;
$song = SubsonicMapper::song($row);
$t->ok(!array_key_exists('duration', $song), 'duree absente plutot que nulle');
$t->ok(!array_key_exists('size', $song), 'taille absente plutot que nulle');
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicMapper
```

Expected: FAIL — class not found.

- [ ] **Step 3: Write the implementation**

Create `src/apps/frontend/modules/rest/lib/SubsonicMapper.class.php`:

```php
<?php

/**
 * Traduction des entites du site vers les objets du protocole Subsonic.
 *
 * Accepte indifferemment un Post hydrate ou un tableau associatif issu d'une
 * requete SQL brute : les deux exposent les memes cles.
 */
class SubsonicMapper
{
  const ALBUM_PREFIX  = 'Musique Approximative — ';
  const ALBUM_ARTIST  = 'Various Artists';

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
   * @param array|Post $post
   * @param int|null   $track Rang dans le mois — uniquement depuis getAlbum.
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

    // Attribut absent plutot que valeur nulle : les clients gerent bien mieux
    // l'absence qu'un 0.
    if (null !== $get('track_duration')) {
      $song['duration'] = (int) $get('track_duration');
    }

    if (null !== $get('track_size')) {
      $song['size'] = (int) $get('track_size');
    }

    // MySQL 5.7 n'a pas de fonctions de fenetrage : le rang n'est calculable
    // que la ou l'on tient deja tout le mois, c'est-a-dire dans getAlbum.
    if (null !== $track) {
      $song['track'] = (int) $track;
    }

    return $song;
  }

  /**
   * @param array $month Ligne issue de PostTable::getMonths()
   * @return array
   */
  public static function album(array $month)
  {
    $album = [
      'id'        => SubsonicId::forAlbum($month['month']),
      'name'      => self::ALBUM_PREFIX.$month['month'],
      'title'     => self::ALBUM_PREFIX.$month['month'],
      // Un mois contient trente artistes et Subsonic n'en accepte qu'un.
      // Pas d'artistId : une reference pendante vers un artiste absent de
      // getArtists fait planter certains clients.
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
   * @param array $artist Ligne issue de PostTable::getDistinctArtists()
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
   * @param array $contributor Ligne issue de PostTable::getContributors()
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
   * Lettre d'index d'un nom : initiale alphabetique, ou « # ».
   */
  public static function indexLetter($name)
  {
    $first = strtoupper(mb_substr(trim($name), 0, 1, 'UTF-8'));

    return preg_match('/^[A-Z]$/', $first) ? $first : '#';
  }

  private static function iso8601($datetime)
  {
    return date('c', strtotime($datetime));
  }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:unit helper/SubsonicMapper
```

Expected: PASS, 12/12.

- [ ] **Step 5: Commit**

```bash
git add src/apps/frontend/modules/rest/lib/SubsonicMapper.class.php src/test/unit/helper/SubsonicMapperTest.php
git commit -m "feat: traduction des posts, mois et contributeurs vers les objets Subsonic"
```

---

## Task 9: Browsing — `getAlbumList2`, `getAlbum`, `getSong`

**Files:**
- Modify: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Modify: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Add the failing assertions**

Append to `src/test/functional/frontend/restActionsTest.php`:

```php
// --- getAlbumList2 ---

$browser->get('/rest/getAlbumList2.view?f=json&type=newest');
$json = json_decode($browser->getResponse()->getContent(), true);
$albums = $json['subsonic-response']['albumList2']['album'];
$t->is($albums[0]['id'], 'al-2024-06', 'getAlbumList2 : le mois le plus recent en tete');
$t->is($albums[0]['songCount'], 2, 'getAlbumList2 : songCount present');
$t->is($albums[0]['artist'], 'Various Artists', 'getAlbumList2 : artiste de compilation');
$t->ok(!isset($albums[0]['artistId']), 'getAlbumList2 : pas d artistId pendant');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['albumList2']['album']), 1, 'getAlbumList2 : size respecte');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=1&offset=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['albumList2']['album'][0]['id'], 'al-2024-05', 'getAlbumList2 : offset respecte');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=99999');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->ok(count($json['subsonic-response']['albumList2']['album']) <= 500, 'getAlbumList2 : size plafonne a 500');

// --- getAlbum ---

$browser->get('/rest/getAlbum.view?f=json&id=al-2024-06');
$json = json_decode($browser->getResponse()->getContent(), true);
$album = $json['subsonic-response']['album'];
$t->is(count($album['song']), 2, 'getAlbum : les deux morceaux du mois');
$t->is($album['song'][0]['track'], 1, 'getAlbum : numero de piste calcule');
$t->is($album['song'][0]['title'], 'Rock & Roll', 'getAlbum : titre non echappe en JSON');

$browser->get('/rest/getAlbum.view?f=json&id=al-1999-01');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getAlbum : mois vide -> 70');

// --- getSong ---

$browser->get('/rest/getSong.view?f=json&id=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['song']['id'], '1', 'getSong : renvoie le morceau');

$browser->get('/rest/getSong.view?f=json&id=999999');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getSong : id inconnu -> 70');
```

- [ ] **Step 2: Run to verify the new assertions fail**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: the new assertions fail with error 70 (method not found).

- [ ] **Step 3: Implement the three methods**

Add to `restActions`, before `readOnly()`:

```php
  // --- Navigation ------------------------------------------------------------

  protected function subsonicGetAlbumList2(sfWebRequest $request)
  {
    $type   = $request->getParameter('type', 'newest');
    $size   = $this->boundedSize($request);
    $offset = $this->offset($request);
    $table  = Doctrine_Core::getTable('Post');

    switch ($type) {
      case 'alphabeticalByName':
      case 'byYear':
        $months = $table->getMonths($size, $offset, 'ASC');
        break;

      case 'random':
        $all = $table->getMonths();
        shuffle($all);
        $months = array_slice($all, 0, $size);
        break;

      // frequent et recent retombent sur newest : aucune statistique d'ecoute
      // n'est collectee.
      case 'newest':
      case 'frequent':
      case 'recent':
      default:
        $months = $table->getMonths($size, $offset, 'DESC');
    }

    $albums = [];
    foreach ($months as $month) {
      $albums[] = SubsonicMapper::album($month);
    }

    return SubsonicResponse::ok(['albumList2' => ['album' => $albums]]);
  }

  protected function subsonicGetAlbum(sfWebRequest $request)
  {
    $id    = $this->requireParameter($request, 'id');
    $month = SubsonicId::parseAlbum($id);

    if (null === $month) {
      throw new SubsonicException('Album not found.', 70);
    }

    $table = Doctrine_Core::getTable('Post');
    $row   = $table->getMonth($month);

    if (null === $row) {
      throw new SubsonicException('Album not found.', 70);
    }

    $album = SubsonicMapper::album($row);
    $songs = [];
    $track = 1;

    foreach ($table->getPostsByMonth($month) as $post) {
      $songs[] = SubsonicMapper::song($post, $track++);
    }

    $album['song'] = $songs;

    return SubsonicResponse::ok(['album' => $album]);
  }

  protected function subsonicGetSong(sfWebRequest $request)
  {
    $post = $this->findPost($this->requireParameter($request, 'id'));

    return SubsonicResponse::ok(['song' => SubsonicMapper::song($post)]);
  }

  /**
   * @throws SubsonicException code 70 si l'id est invalide ou le post invisible
   * @return Post
   */
  protected function findPost($id)
  {
    $postId = SubsonicId::parseSong($id);

    if (null === $postId) {
      throw new SubsonicException('Song not found.', 70);
    }

    $post = Doctrine_Core::getTable('Post')
      ->buildOnlinePostsQuery(null, null, PostTable::FIELDS_SUBSONIC)
      ->andWhere('p.id = ?', $postId)
      ->fetchOne();

    if (!$post) {
      throw new SubsonicException('Song not found.', 70);
    }

    return $post;
  }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony cache:clear
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: all assertions pass, including the earlier `al-2099-01` exclusion.

- [ ] **Step 5: Commit**

```bash
git add src/apps/frontend/modules/rest src/test/functional/frontend/restActionsTest.php
git commit -m "feat: getAlbumList2, getAlbum et getSong avec pagination plafonnee"
```

---

## Task 10: Artists — `getArtists`, `getArtist`

**Files:**
- Modify: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Modify: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Add the failing assertions**

Append to the functional test:

```php
// --- getArtists ---

$browser->get('/rest/getArtists.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$indexes = $json['subsonic-response']['artists']['index'];

$found = null;
foreach ($indexes as $index) {
  foreach ($index['artist'] as $artist) {
    if ('Sigur Ros' === $artist['name']) {
      $found = $artist;
    }
    $t->ok('Fantome' !== $artist['name'], 'getArtists : aucun artiste issu de posts invisibles');
  }
}
$t->ok(null !== $found, 'getArtists : l artiste attendu est present');
$t->is($found['albumCount'], 2, 'getArtists : albumCount = nombre de mois distincts');

// --- getArtist ---

$browser->get('/rest/getArtist.view?f=json&id='.$found['id']);
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['artist']['album']), 2, 'getArtist : les mois ou l artiste apparait');

$browser->get('/rest/getArtist.view?f=json&id=ar-bm9ib2R5');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getArtist : artiste inconnu -> 70');
```

Adjust `'Sigur Ros'` and `'Fantome'` to the accented forms used in the fixtures.

- [ ] **Step 2: Run to verify the new assertions fail**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: error 70, method not found.

- [ ] **Step 3: Implement**

Add to `restActions`:

```php
  protected function subsonicGetArtists(sfWebRequest $request)
  {
    $buckets = [];

    foreach (Doctrine_Core::getTable('Post')->getDistinctArtists() as $row) {
      $letter = SubsonicMapper::indexLetter($row['track_author']);
      $buckets[$letter][] = SubsonicMapper::artist($row);
    }

    ksort($buckets);

    $indexes = [];
    foreach ($buckets as $letter => $artists) {
      $indexes[] = ['name' => $letter, 'artist' => $artists];
    }

    return SubsonicResponse::ok([
      'artists' => ['ignoredArticles' => '', 'index' => $indexes],
    ]);
  }

  protected function subsonicGetArtist(sfWebRequest $request)
  {
    $id     = $this->requireParameter($request, 'id');
    $author = SubsonicId::parseArtist($id);

    if (null === $author) {
      throw new SubsonicException('Artist not found.', 70);
    }

    $months = Doctrine_Core::getTable('Post')->getMonthsByArtist($author);

    if (!$months) {
      throw new SubsonicException('Artist not found.', 70);
    }

    $albums = [];
    foreach ($months as $month) {
      $albums[] = SubsonicMapper::album($month);
    }

    return SubsonicResponse::ok(['artist' => [
      'id'         => $id,
      'name'       => $author,
      'albumCount' => count($albums),
      'album'      => $albums,
    ]]);
  }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

Expected: all pass.

- [ ] **Step 5: Commit**

```bash
git add src/apps/frontend/modules/rest src/test/functional/frontend/restActionsTest.php
git commit -m "feat: getArtists indexe par initiale et getArtist"
```

---

## Task 11: `search3` and `getRandomSongs`

**Files:**
- Modify: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Modify: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Add the failing assertions**

```php
// --- search3 ---

$browser->get('/rest/search3.view?f=json&query=Rock');
$json = json_decode($browser->getResponse()->getContent(), true);
$result = $json['subsonic-response']['searchResult3'];
$t->is(count($result['song']), 1, 'search3 : trouve le morceau par titre');

$browser->get('/rest/search3.view?f=json&query=Sigur');
$json = json_decode($browser->getResponse()->getContent(), true);
$result = $json['subsonic-response']['searchResult3'];
$t->ok(count($result['artist']) >= 1, 'search3 : trouve l artiste');
$t->ok(count($result['song']) >= 1, 'search3 : trouve aussi ses morceaux');

$browser->get('/rest/search3.view?f=json&query=');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->ok(count($json['subsonic-response']['searchResult3']['song']) > 0, 'search3 : requete vide = tout parcourir');

$browser->get('/rest/search3.view?f=json&query=zzzzzzz');
$raw = $browser->getResponse()->getContent();
$t->ok(false === strpos($raw, '"searchResult3":[]'), 'search3 : zero resultat serialise en objet, pas en tableau');

$browser->get('/rest/search3.view?f=json&query=Fantome');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['searchResult3']['song']), 0, 'search3 : aucun post invisible');
```

- [ ] **Step 2: Run to verify the new assertions fail**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 3: Implement**

Add to `restActions`:

```php
  protected function subsonicSearch3(sfWebRequest $request)
  {
    $query = (string) $request->getParameter('query', '');
    $table = Doctrine_Core::getTable('Post');

    $artistCount = $this->boundedSize($request, 'artistCount', 20);
    $songCount   = $this->boundedSize($request, 'songCount', 20);

    $artists = [];
    foreach ($table->getDistinctArtists('' === $query ? null : $query, $artistCount, $this->offset($request, 'artistOffset')) as $row) {
      $artists[] = SubsonicMapper::artist($row);
    }

    $songs = [];
    foreach ($table->searchSongs($query, $songCount, $this->offset($request, 'songOffset')) as $post) {
      $songs[] = SubsonicMapper::song($post);
    }

    // Les albums sont chronologiques : « 2024-06 » n'a rien de pertinent a
    // faire correspondre a une requete textuelle.
    return SubsonicResponse::ok(['searchResult3' => [
      'artist' => $artists,
      'album'  => [],
      'song'   => $songs,
    ]]);
  }

  protected function subsonicSearch2(sfWebRequest $request)
  {
    $body = $this->subsonicSearch3($request);
    $body['searchResult2'] = $body['searchResult3'];
    unset($body['searchResult3']);

    return $body;
  }

  protected function subsonicGetRandomSongs(sfWebRequest $request)
  {
    $size  = $this->boundedSize($request);
    $posts = Doctrine_Core::getTable('Post')
      ->buildOnlinePostsQuery(null, null, PostTable::FIELDS_SUBSONIC)
      ->orderBy('RAND()')
      ->limit($size)
      ->execute();

    $songs = [];
    foreach ($posts as $post) {
      $songs[] = SubsonicMapper::song($post);
    }

    return SubsonicResponse::ok(['randomSongs' => ['song' => $songs]]);
  }

  protected function subsonicGetAlbumList(sfWebRequest $request)
  {
    $body = $this->subsonicGetAlbumList2($request);
    $body['albumList'] = $body['albumList2'];
    unset($body['albumList2']);

    return $body;
  }
```

Delete the earlier `subsonicGetRandomSongs()` stub — this replaces it.

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 5: Commit**

```bash
git add src/apps/frontend/modules/rest src/test/functional/frontend/restActionsTest.php
git commit -m "feat: search3 sur filtres LIKE, alias legacy et getRandomSongs

PostTable::search() n'est pas reutilisable : elle ne renvoie que des
identifiants de posts classes, donc aucun resultat artiste, et declenche une
requete par resultat sans borne."
```

---

## Task 12: Playlists

**Files:**
- Modify: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Modify: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Add the failing assertions**

```php
// --- playlists ---

$browser->get('/rest/getPlaylists.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$playlists = $json['subsonic-response']['playlists']['playlist'];
$t->is(count($playlists), 2, 'getPlaylists : un element par contributeur visible');
$t->ok(isset($playlists[0]['songCount']), 'getPlaylists : songCount present');
$t->ok(isset($playlists[0]['duration']), 'getPlaylists : duration presente');

$browser->get('/rest/getPlaylist.view?f=json&id=pl-alice');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['playlist']['entry']), 2, 'getPlaylist : les morceaux visibles d alice');

$browser->get('/rest/getPlaylist.view?f=json&id=pl-personne');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getPlaylist : contributeur inconnu -> 70');
```

Alice has three posts in the fixtures, one offline and one future-dated — so two visible. Adjust the expected count if you changed the fixtures.

- [ ] **Step 2: Run to verify the new assertions fail**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 3: Implement**

```php
  protected function subsonicGetPlaylists(sfWebRequest $request)
  {
    $playlists = [];

    foreach (Doctrine_Core::getTable('Post')->getContributors() as $contributor) {
      $playlists[] = SubsonicMapper::playlist($contributor);
    }

    return SubsonicResponse::ok(['playlists' => ['playlist' => $playlists]]);
  }

  protected function subsonicGetPlaylist(sfWebRequest $request)
  {
    $id       = $this->requireParameter($request, 'id');
    $username = SubsonicId::parsePlaylist($id);

    if (null === $username) {
      throw new SubsonicException('Playlist not found.', 70);
    }

    $table  = Doctrine_Core::getTable('Post');
    $header = null;

    foreach ($table->getContributors() as $contributor) {
      if ($contributor['username'] === $username) {
        $header = SubsonicMapper::playlist($contributor);
      }
    }

    if (null === $header) {
      throw new SubsonicException('Playlist not found.', 70);
    }

    // FIELDS_SUBSONIC exclut body : cette colonne TEXT n'est jamais serialisee
    // et pese lourd sur la playlist d'un contributeur prolifique.
    $posts = $table->buildOnlinePostsQuery($username, null, PostTable::FIELDS_SUBSONIC)->execute();

    $entries = [];
    foreach ($posts as $post) {
      $entries[] = SubsonicMapper::song($post);
    }

    $header['entry'] = $entries;

    return SubsonicResponse::ok(['playlist' => $header]);
  }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 5: Commit**

```bash
git add src/apps/frontend/modules/rest src/test/functional/frontend/restActionsTest.php
git commit -m "feat: getPlaylists et getPlaylist avec songCount et duration"
```

---

## Task 13: `stream`, `download`, `getCoverArt`

**Files:**
- Modify: `src/apps/frontend/modules/rest/actions/actions.class.php`
- Modify: `src/test/functional/frontend/restActionsTest.php`

- [ ] **Step 1: Add the failing assertions**

```php
// --- stream ---

$browser->get('/rest/stream.view?id=1');
$t->is($browser->getResponse()->getStatusCode(), 302, 'stream : redirection 302');

$location = $browser->getResponse()->getHttpHeader('Location');
$t->like($location, '#^https?://#', 'stream : Location absolue avec schema');
$t->like($location, '#un%20titre\.mp3$#', 'stream : nom de fichier encode en %20');
$t->unlike($location, '#\+#', 'stream : aucun + dans le chemin');

$browser->get('/rest/download.view?id=1');
$t->is($browser->getResponse()->getStatusCode(), 302, 'download : meme comportement');

$browser->get('/rest/stream.view?f=json&id=999999');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'stream : id inconnu -> 70');

// --- getCoverArt ---

$browser->get('/rest/getCoverArt.view?id=co-1');
$t->is($browser->getResponse()->getStatusCode(), 302, 'getCoverArt : redirection');
$t->like($browser->getResponse()->getHttpHeader('Location'), '#logo_500\.png$#', 'getCoverArt : repli sur le logo quand l avatar manque');
```

- [ ] **Step 2: Run to verify the new assertions fail**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 3: Implement**

```php
  // --- Fichiers ---------------------------------------------------------------

  protected function subsonicStream(sfWebRequest $request)
  {
    $post = $this->findPost($this->requireParameter($request, 'id'));

    // maxBitRate et format sont ignores : sans ffmpeg dans l'image, on sert
    // toujours l'original. C'est legal cote protocole.
    return $this->redirectRaw($post->getTrackUrl($request->isSecure() ? 'https' : 'http'));
  }

  protected function subsonicDownload(sfWebRequest $request)
  {
    return $this->subsonicStream($request);
  }

  protected function subsonicGetCoverArt(sfWebRequest $request)
  {
    $cover = SubsonicId::parseCover($this->requireParameter($request, 'id'));

    if (null === $cover) {
      throw new SubsonicException('Cover art not found.', 70);
    }

    if ('album' === $cover['type']) {
      $row    = Doctrine_Core::getTable('Post')->getMonth($cover['value']);
      $postId = $row ? $row['first_post_id'] : null;
    } else {
      $postId = $cover['value'];
    }

    $webDir = sfConfig::get('sf_web_dir');

    if ($postId && is_readable(sprintf('%s/avatars/%s.png', $webDir, $postId))) {
      $path = sprintf('/avatars/%s.png', $postId);
    } else {
      // La generation d'avatars est desactivee dans Post::postSave, donc le
      // repli est le cas courant, pas l'exception.
      $path = sprintf('/theme/%s/images/logo_500.png', sfConfig::get('app_theme'));
    }

    return $this->redirectRaw($request->getUriPrefix().$request->getRelativeUrlRoot().$path);
  }

  /**
   * Redirection 302 sans passer par sfAction::redirect().
   *
   * sfWebController::genUrl() ne laisse passer une chaine telle quelle que si
   * elle correspond a ^[a-z][a-z0-9+.\-]*:// ; une URL relative au protocole
   * (« //host/path ») echoue ce test et repart dans la generation de route,
   * produisant un Location relatif au site.
   *
   * @return null Signale au repartiteur que la reponse est deja emise.
   */
  protected function redirectRaw($url)
  {
    $response = $this->getResponse();
    $response->setStatusCode(302);
    $response->setHttpHeader('Location', $url);
    $response->setHttpHeader('Cache-Control', 'no-store');

    return null;
  }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker-compose exec php php symfony test:functional frontend/restActions
```

- [ ] **Step 5: Verify by hand against a real file**

```bash
curl -sI 'http://localhost:8080/rest/stream.view?id=1' | grep -i '^location'
```

Expected: an absolute `http://…/tracks/…%20….mp3`. Paste that URL into `curl -sI` and confirm it is not a 404 — if the file is absent locally that is expected, but the URL shape must be right.

- [ ] **Step 6: Commit**

```bash
git add src/apps/frontend/modules/rest src/test/functional/frontend/restActionsTest.php
git commit -m "feat: stream, download et getCoverArt en 302 vers une URL absolue"
```

---

## Task 14: Metadata — getid3, `preSave`, `scan-tracks`

**Files:**
- Modify: `src/composer.json`
- Modify: `src/lib/model/doctrine/Post.class.php`
- Create: `src/lib/task/musiqueapproximativeScanTracksTask.class.php`

- [ ] **Step 1: Add the dependency**

```bash
docker-compose exec php composer require james-heinrich/getid3:^1.9
```

Verify it landed:

```bash
grep -n getid3 src/composer.json
```

- [ ] **Step 2: Add the metadata reader to `Post`**

In `src/lib/model/doctrine/Post.class.php`, add after `getTrackUrl()`:

```php
  /**
   * Chemin absolu du fichier audio, qu'il existe ou non.
   *
   * @return string
   */
  public function getTrackPath()
  {
    return sprintf('%s/tracks/%s', sfConfig::get('sf_web_dir'), $this->track_filename);
  }

  /**
   * Renseigne track_duration et track_size si le fichier est lisible.
   *
   * @param bool $force Recalcule meme si les colonnes sont deja remplies.
   * @return bool true si au moins une colonne a ete modifiee
   */
  public function fillTrackMetadata($force = false)
  {
    if (!$force && null !== $this->track_duration && null !== $this->track_size) {
      return false;
    }

    $path = $this->getTrackPath();

    if (!is_readable($path)) {
      return false;
    }

    $this->track_size = filesize($path);

    $getID3 = new getID3();
    $info   = $getID3->analyze($path);

    if (isset($info['playtime_seconds'])) {
      $this->track_duration = (int) round($info['playtime_seconds']);
    }

    return true;
  }

  /**
   * Renseigne les metadonnees avant enregistrement, sans seconde ecriture.
   * Un fichier arrivant apres la creation du post est rattrape par la tache
   * musiqueapproximative:scan-tracks.
   */
  public function preSave($event)
  {
    $this->fillTrackMetadata();
  }
```

- [ ] **Step 3: Write the backfill task**

Create `src/lib/task/musiqueapproximativeScanTracksTask.class.php`:

```php
<?php

/**
 * Renseigne track_duration et track_size pour les posts qui en manquent.
 *
 * Les fichiers audio n'existent que sur l'hote de production
 * (/src/web/tracks est gitignore) : cette tache s'execute la-bas.
 */
class musiqueapproximativeScanTracksTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Nombre maximum de posts a traiter', null),
      new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Recalcule meme les posts deja renseignes'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'scan-tracks';
    $this->briefDescription = 'Calcule la duree et la taille des fichiers audio';
    $this->detailedDescription = <<<EOF
Le [musiqueapproximative:scan-tracks|INFO] renseigne les colonnes
track_duration et track_size a partir des fichiers de web/tracks/.

  [php symfony musiqueapproximative:scan-tracks|INFO]
  [php symfony musiqueapproximative:scan-tracks --limit=100|INFO]
  [php symfony musiqueapproximative:scan-tracks --force|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    new sfDatabaseManager($this->configuration);

    $q = Doctrine_Query::create()
      ->from('Post p')
      ->orderBy('p.publish_on DESC');

    if (!$options['force']) {
      $q->where('p.track_duration IS NULL OR p.track_size IS NULL');
    }

    if ($options['limit']) {
      $q->limit((int) $options['limit']);
    }

    $posts   = $q->execute();
    $total   = count($posts);
    $filled  = 0;
    $missing = 0;

    $this->logSection('scan-tracks', sprintf('%d post(s) a traiter', $total));

    foreach ($posts as $post) {
      if ($post->fillTrackMetadata($options['force'])) {
        $post->save();
        ++$filled;
        continue;
      }

      if (!is_readable($post->getTrackPath())) {
        ++$missing;
        $this->logSection('absent', $post->getTrackFilename(), null, 'ERROR');
      }
    }

    $this->logSection('scan-tracks', sprintf(
      '%d renseigne(s), %d fichier(s) absent(s), %d inchange(s)',
      $filled,
      $missing,
      $total - $filled - $missing
    ));
  }
}
```

- [ ] **Step 4: Verify the task is discovered**

```bash
docker-compose exec php php symfony list musiqueapproximative
```

Expected: `musiqueapproximative:scan-tracks` listed.

- [ ] **Step 5: Test it against a real file**

```bash
mkdir -p src/web/tracks
docker-compose exec php sh -c 'cd /usr/local/src && php -r "
  \$f = fopen(\"web/tracks/test.mp3\", \"w\");
  fwrite(\$f, str_repeat(chr(0), 1024));
  fclose(\$f);
"'
docker-compose exec php php symfony musiqueapproximative:scan-tracks --limit=5 --env=dev --application=frontend
```

Expected: a summary line. Files that getid3 cannot parse leave `track_duration` null and `track_size` set — that is correct behaviour, not a failure.

```bash
rm -f src/web/tracks/test.mp3
```

- [ ] **Step 6: Commit**

```bash
git add src/composer.json src/composer.lock src/lib/model/doctrine/Post.class.php src/lib/task/musiqueapproximativeScanTracksTask.class.php
git commit -m "feat: calcul de la duree et de la taille des morceaux via getid3"
```

---

## Task 15: Full suite and a real client

**Files:** none — this is verification.

- [ ] **Step 1: Run everything**

```bash
docker-compose exec php php symfony test:all
```

Expected: all green. Fix anything that is not before continuing.

- [ ] **Step 2: Lint**

```bash
find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -v "No syntax errors" || echo "lint OK"
```

- [ ] **Step 3: Verify the two caches are really off**

```bash
docker-compose exec php php symfony cache:clear
curl -sI 'http://localhost:8080/rest/ping.view' | grep -i 'cache-control'
curl -s 'http://localhost:8080/rest/getAlbum.view?f=json&id=al-2024-06' | head -c 60; echo
curl -s 'http://localhost:8080/rest/getAlbum.view?f=json&id=al-2024-05' | head -c 60; echo
```

Expected: `Cache-Control: no-store`, and two different bodies.

- [ ] **Step 4: Point a real client at it**

Add `http://localhost:8080` as a server in Symfonium, Feishin or Substreamer, with any username and password. Walk the critical paths from the test plan:

- connection succeeds and the startup calls produce no error dialog
- newest albums list shows the most recent month first
- opening a month lists its tracks with real durations
- playing a track works, and seeking mid-track works
- searching by title and by artist both return results
- a contributor playlist opens and plays

- [ ] **Step 5: Commit anything the client run exposed**

If nothing changed, skip. Otherwise fix, re-run `test:all`, and commit.

---

## Task 16: Deployment and documentation

**Files:**
- Modify: `Makefile`
- Create: `docs/API_SUBSONIC.md`

- [ ] **Step 1: Make `deploy` build the vendor directory**

In `Makefile`, replace the `deploy` target's body with:

```make
deploy: ## Configure et déploie l'application
	PROFILE=$(PROFILE) docker-compose run --rm --entrypoint fixuid php make configure
	PROFILE=$(PROFILE) docker-compose run --rm --entrypoint fixuid php composer install --no-dev --optimize-autoloader
	rsync -avzm $(RSYNC_PARAMETERS) --exclude-from=./etc/$(PROFILE)/rsync/exclude --include-from=./etc/$(PROFILE)/rsync/include -e "ssh -p $$RSYNC_SSH_PORT" "$$RSYNC_LOCAL_PATH" "$$RSYNC_REMOTE_USER@$$RSYNC_REMOTE_HOST:$$RSYNC_REMOTE_PATH"
```

`composer install` is otherwise run only by the docker-compose start command, so getid3 would reach production only if someone happened to have built the vendor directory locally.

- [ ] **Step 2: Write the client documentation**

Create `docs/API_SUBSONIC.md`:

```markdown
# API Subsonic

Musique Approximative expose son archive via l'API Subsonic 1.16.1, en lecture
seule. N'importe quel client Subsonic peut donc parcourir et écouter le
catalogue.

## Configuration d'un client

| Champ | Valeur |
| --- | --- |
| Adresse du serveur | `https://www.musiqueapproximative.net` |
| Nom d'utilisateur | n'importe lequel |
| Mot de passe | n'importe lequel |

L'authentification est ouverte : les fichiers audio sont déjà servis
publiquement, une authentification ne protégerait rien. Les clients exigeant un
mot de passe non vide accepteront n'importe quelle valeur.

Clients vérifiés : Symfonium, Feishin, Substreamer, play:Sub.

## Structure de la bibliothèque

Musique Approximative est un flux quotidien, pas une discothèque. La
bibliothèque est donc organisée chronologiquement :

- **un album = un mois de publication**, nommé `Musique Approximative — 2024-06` ;
- l'artiste de chaque morceau reste le véritable artiste ;
- l'artiste d'un album est `Various Artists`, un mois contenant une trentaine
  d'artistes différents ;
- **une playlist = un contributeur**.

`getAlbumList2?type=newest` affiche donc le mois en cours en tête, ce qui est
l'usage attendu.

## Méthodes supportées

`ping`, `getLicense`, `getMusicFolders`, `getArtists`, `getArtist`, `getAlbum`,
`getAlbumList`, `getAlbumList2`, `getSong`, `getRandomSongs`, `search2`,
`search3`, `getPlaylists`, `getPlaylist`, `getCoverArt`, `stream`, `download`.

Répondent vide, pour ne pas déclencher d'erreur au démarrage des clients :
`getUser`, `getStarred`, `getStarred2`, `getGenres`, `getNowPlaying`,
`getVideos`, `scrobble`.

Renvoient l'erreur 50 (serveur en lecture seule) : `star`, `unstar`,
`createPlaylist`, `updatePlaylist`, `deletePlaylist`.

Non implémentées : `getIndexes` et `getMusicDirectory`, la navigation legacy
par répertoires. Les clients modernes passent tous par la navigation ID3.

## Limites connues

- Pas de transcodage : `maxBitRate` et `format` sont ignorés, le fichier
  d'origine est toujours servi.
- Pas de favoris ni de scrobbling : les écoutes ne sont pas enregistrées.
- Les pochettes retombent sur le logo du site tant que la génération d'avatars
  n'est pas réparée.
- La recherche d'albums est toujours vide : un album chronologique nommé
  `2024-06` n'a rien de pertinent à faire correspondre.
```

- [ ] **Step 3: Add the deploy runbook to the spec**

In `docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md`, the section *Déploiement et exploitation → Ordre des opérations* already lists the four steps. Replace its step 1 with the exact statement from Task 4 so nobody retypes it:

```sql
ALTER TABLE post
  ADD COLUMN track_duration INT NULL COMMENT 'Duree du morceau en secondes',
  ADD COLUMN track_size INT NULL COMMENT 'Taille du fichier en octets',
  ADD INDEX online_publish_idx (is_online, publish_on),
  ADD INDEX track_author_idx (track_author(191));
```

- [ ] **Step 4: Dry-run the deploy**

```bash
make deploy PROFILE=www.musiqueapproximative.net
```

`RSYNC_PARAMETERS` defaults to `--dry-run`, so this is safe. Expected: `composer install` runs, then rsync lists files without transferring. Confirm `src/vendor/james-heinrich` appears in the listing.

- [ ] **Step 5: Commit**

```bash
git add Makefile docs/API_SUBSONIC.md docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md
git commit -m "docs: documentation client Subsonic et composer install au deploiement"
```

---

## Production deployment

Not a task — the order that keeps the site up. `buildOnlinePostsQuery()` uses `select('*')`, which Doctrine 1 expands to the model's declared columns, so shipping the new `BasePost` before the `ALTER` makes **every page** throw `Unknown column 'p.track_duration'`.

1. Run the `ALTER TABLE` from Task 4 Step 4 on the production database.
2. `make deploy RSYNC_PARAMETERS=` (real push, vendor included).
3. `php symfony cache:clear` on the host.
4. `php symfony musiqueapproximative:scan-tracks` on the host — roughly 7 000 files, ten to twenty minutes. Until it finishes, no track has a duration and seeking does not work.
5. Verify: two `getAlbum` requests with different ids return different albums, and `/rest` responses carry `Cache-Control: no-store`.

---

## Self-review notes

**Spec coverage.** Every section of the spec maps to a task: identifiers → 3, song/album/artist fields → 8, method inventory → 7/9/10/11/12/13, pagination → 9/11, `search3` → 11, serialisation → 2, track URLs → 1, streaming → 13, cover art → 13, caches and session → 7, filters → 7, schema and indexes → 4, backfill → 14, errors → 2/7, tests → 5 and throughout, deployment → 16.

**Deliberately not covered.** `getIndexes`/`getMusicDirectory` (out of scope per the spec), and the six `TODOS.md` items, which belong to other branches.

**Naming consistency.** `SubsonicId::forSongCover()` and `forAlbumCover()` are the two cover helpers; `parseCover()` returns `['type' => 'song'|'album', 'value' => …]`. `PostTable::FIELDS_SUBSONIC` and `PostTable::WHERE_ONLINE` are used verbatim in Tasks 6, 9, 11, 12 and 13. `SubsonicMapper::song()` takes `($post, $track = null)` everywhere.

**One thing to watch during implementation.** Task 6 uses raw SQL for the aggregate queries because Doctrine 1 hydrates `GROUP BY` results awkwardly and MySQL 5.7 enables `ONLY_FULL_GROUP_BY`. The visibility rule stays single-sourced through `PostTable::WHERE_ONLINE`, which is valid in both DQL and SQL because the column names match. If you find a clean DQL formulation, switching is fine — keep the constant.
