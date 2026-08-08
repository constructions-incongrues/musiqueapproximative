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
 * Chaque gestionnaire renvoie un tableau PHP (via SubsonicResponse::ok()/
 * ::error()) ou null s'il a deja emis sa propre reponse (stream,
 * getCoverArt — tache ulterieure). Aucun gestionnaire ne touche a la reponse,
 * a la serialisation ou aux en-tetes : c'est le role du dispatcher.
 *
 * @see docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md
 */
class restActions extends sfActions
{
  /** Plafond impose a tout parametre de taille. */
  const MAX_SIZE = 500;

  public function executeIndex(sfWebRequest $request)
  {
    // Le toolbar de debug corrompt les reponses non-HTML en dev.
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
      'musicFolders' => ['musicFolder' => [['id' => '0', 'name' => 'Musique Approximative']]],
    ]);
  }

  // --- Talons -----------------------------------------------------------
  // Les clients les appellent au demarrage ; une erreur y produit des popups
  // inutiles. On repond vide plutot qu'en echec.

  protected function subsonicGetUser(sfWebRequest $request)
  {
    return SubsonicResponse::ok(['user' => [
      'username'           => $request->getParameter('u', 'guest'),
      'scrobblingEnabled'  => false,
      'adminRole'          => false,
      'settingsRole'       => false,
      'downloadRole'       => true,
      'uploadRole'         => false,
      'playlistRole'       => false,
      'coverArtRole'       => true,
      'commentRole'        => false,
      'podcastRole'        => false,
      'streamRole'         => true,
      'jukeboxRole'        => false,
      'shareRole'          => false,
      'videoConversionRole' => false,
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

  // --- Navigation -----------------------------------------------------------

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
        // Pas de RAND() en SQL ici : il faudrait alors appliquer LIMIT/OFFSET
        // apres le tri aleatoire, ce qui rendrait chaque page incoherente
        // d'un appel a l'autre. On charge donc tous les mois (l'agregat
        // reste petit : un par mois de publication) et on tire en PHP.
        $all = $table->getMonths();
        shuffle($all);
        $months = array_slice($all, $offset, $size);
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

  /** Alias legacy de getAlbumList2 : meme contenu, conteneur "albumList". */
  protected function subsonicGetAlbumList(sfWebRequest $request)
  {
    $body = $this->subsonicGetAlbumList2($request);
    $body['albumList'] = $body['albumList2'];
    unset($body['albumList2']);

    return $body;
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

  /**
   * Regroupe les artistes distincts en index par initiale, tries
   * alphabetiquement (le groupe « # » se retrouve avant « A », son code
   * ASCII etant plus petit -- sans consequence, aucun client Subsonic ne
   * suppose un ordre particulier entre les deux).
   */
  protected function subsonicGetArtists(sfWebRequest $request)
  {
    $table   = Doctrine_Core::getTable('Post');
    $indices = [];

    foreach ($table->getDistinctArtists() as $artist) {
      $letter = SubsonicMapper::indexLetter($artist['track_author']);
      $indices[$letter][] = SubsonicMapper::artist($artist);
    }

    ksort($indices);

    $index = [];
    foreach ($indices as $letter => $artists) {
      $index[] = ['name' => $letter, 'artist' => $artists];
    }

    return SubsonicResponse::ok(['artists' => ['ignoredArticles' => '', 'index' => $index]]);
  }

  protected function subsonicGetArtist(sfWebRequest $request)
  {
    $id     = $this->requireParameter($request, 'id');
    $author = SubsonicId::parseArtist($id);

    if (null === $author) {
      throw new SubsonicException('Artist not found.', 70);
    }

    $table  = Doctrine_Core::getTable('Post');
    $months = $table->getMonthsByArtist($author);

    if (!$months) {
      throw new SubsonicException('Artist not found.', 70);
    }

    $artist = SubsonicMapper::artist(['track_author' => $author, 'album_count' => count($months)]);

    $albums = [];
    foreach ($months as $month) {
      $albums[] = SubsonicMapper::album($month);
    }
    $artist['album'] = $albums;

    return SubsonicResponse::ok(['artist' => $artist]);
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

  // --- Fichiers ---------------------------------------------------------------

  /**
   * maxBitRate et format sont ignores : sans ffmpeg dans l'image Docker, le
   * fichier original est toujours servi. Comportement legal cote protocole.
   *
   * Schema choisi via $request->isSecure() plutot que force en 'https' :
   * c'est deja la convention du reste du code (Post::toJson(),
   * postActions::executeShow()) et forcer 'https' casserait cet
   * environnement de dev (http nu sur le port 8081). $request->isSecure()
   * ne suit X-Forwarded-Proto que si trust_proxy est actif -- il ne l'est
   * pas ici -- donc un client https derriere l'edge de prod (une pile
   * differente de celle du dev) pourrait recevoir un Location http. C'est le
   * meme compromis deja accepte partout ailleurs dans ce fichier plutot
   * qu'un risque nouveau introduit par cette tache.
   */
  protected function subsonicStream(sfWebRequest $request)
  {
    $post = $this->findPost($this->requireParameter($request, 'id'));

    return $this->redirectRaw($post->getTrackUrl($request->isSecure() ? 'https' : 'http'));
  }

  /** Meme comportement que stream : rien ne distingue les deux ici. */
  protected function subsonicDownload(sfWebRequest $request)
  {
    return $this->subsonicStream($request);
  }

  /**
   * La pochette d'un morceau est son propre avatar ; celle d'un album
   * mensuel est l'avatar du post cover_post_id du mois (cf.
   * PostTable::SELECT_MONTH_AGGREGATE). Les avatars sont quasi tous absents
   * (generation desactivee dans Post::postSave) : le repli sur le logo du
   * theme est le cas courant, pas l'exception.
   */
  protected function subsonicGetCoverArt(sfWebRequest $request)
  {
    $cover = SubsonicId::parseCover($this->requireParameter($request, 'id'));

    if (null === $cover) {
      throw new SubsonicException('Cover art not found.', 70);
    }

    if (SubsonicId::TYPE_ALBUM === $cover['type']) {
      $month  = Doctrine_Core::getTable('Post')->getMonth($cover['value']);
      $postId = $month ? $month['cover_post_id'] : null;
    } else {
      // Passe par findPost() plutot que d'utiliser l'identifiant brut : la
      // pochette d'un post hors ligne ou programme ne doit pas etre plus
      // accessible que son audio. Sans effet aujourd'hui, la generation
      // d'avatars etant desactivee et le repli public — mais le jour ou elle
      // est reparee, l'identifiant se devine et la regle de visibilite doit
      // deja etre la.
      $postId = $this->findPost($cover['value'])->id;
    }

    $webDir = sfConfig::get('sf_web_dir');

    if ($postId && is_readable(sprintf('%s/avatars/%s.png', $webDir, $postId))) {
      $path = sprintf('/avatars/%s.png', $postId);
    } else {
      $path = sprintf('/theme/%s/images/logo_500.png', sfConfig::get('app_theme'));
    }

    return $this->redirectRaw($request->getUriPrefix().$request->getRelativeUrlRoot().$path);
  }

  /**
   * Redirection 302 sans passer par sfAction::redirect().
   *
   * sfWebController::genUrl() ne laisse passer une chaine telle quelle que si
   * elle correspond a ^[a-z][a-z0-9+.\-]*:// ; app_urls_tracks vaut
   * //${APP_DOMAIN}/tracks (relatif au protocole), qui echoue ce test et
   * repart dans la generation de route -- produisant un Location relatif au
   * site plutot que l'URL de piste voulue.
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

  // --- Recherche et alea ------------------------------------------------

  /**
   * PostTable::search() (Doctrine Searchable) n'est pas reutilisable ici :
   * elle ne renvoie que des ids de posts classes par pertinence -- jamais
   * d'artiste, alors que le protocole l'exige -- et refait une requete par
   * resultat, sans aucune borne. Sur cette surface non authentifiee,
   * interrogee en saisie incrementale, c'est de l'amplification gratuite.
   * getDistinctArtists() et searchSongs() sont bornees et en LIKE, toutes
   * deux construites pour cet usage (cf. PostTable).
   */
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

    return SubsonicResponse::ok(['searchResult3' => [
      'artist' => $artists,
      // Un "album" ici est un mois de publication ("2024-06") : rien de
      // pertinent a faire correspondre a une requete textuelle. Toujours
      // vide, par conception -- pas un oubli.
      'album'  => [],
      'song'   => $songs,
    ]]);
  }

  /** Alias legacy de search3 : meme contenu, conteneur "searchResult2". */
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

  // --- Playlists ----------------------------------------------------------
  // Une playlist Subsonic, ici, c'est un contributeur : les morceaux qu'il a
  // postes. Pas de playlist creee cote client (cf. section "Refusees").

  protected function subsonicGetPlaylists(sfWebRequest $request)
  {
    $playlists = [];

    foreach (Doctrine_Core::getTable('Post')->getContributors() as $contributor) {
      $playlists[] = SubsonicMapper::playlist($contributor);
    }

    return SubsonicResponse::ok(['playlists' => ['playlist' => $playlists]]);
  }

  /**
   * Pas de pagination ici : le protocole n'en prevoit pas pour getPlaylist,
   * d'ou l'importance de FIELDS_SUBSONIC (cf. findContributor()/PostTable) --
   * la playlist d'un contributeur prolifique peut compter plusieurs
   * centaines de morceaux, et charger `body` (TEXT) pour chacun d'eux serait
   * pure perte, cette colonne n'etant jamais serialisee.
   */
  protected function subsonicGetPlaylist(sfWebRequest $request)
  {
    $contributor = $this->findContributor($this->requireParameter($request, 'id'));
    $playlist    = SubsonicMapper::playlist($contributor);

    $entries = [];
    foreach (Doctrine_Core::getTable('Post')->buildOnlinePostsQuery($contributor['username'], null, PostTable::FIELDS_SUBSONIC)->execute() as $post) {
      $entries[] = SubsonicMapper::song($post);
    }
    $playlist['entry'] = $entries;

    return SubsonicResponse::ok(['playlist' => $playlist]);
  }

  /**
   * @throws SubsonicException code 70 si l'id est invalide ou si le
   *                            contributeur n'a aucun post visible
   * @return array Ligne issue de PostTable::getContributors()
   */
  protected function findContributor($id)
  {
    $username = SubsonicId::parsePlaylist($id);

    if (null === $username) {
      throw new SubsonicException('Playlist not found.', 70);
    }

    foreach (Doctrine_Core::getTable('Post')->getContributors() as $contributor) {
      if ($contributor['username'] === $username) {
        return $contributor;
      }
    }

    throw new SubsonicException('Playlist not found.', 70);
  }

  // --- Refusees -----------------------------------------------------------
  // Serveur en lecture seule : toute methode d'ecriture repond l'erreur 50.

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
