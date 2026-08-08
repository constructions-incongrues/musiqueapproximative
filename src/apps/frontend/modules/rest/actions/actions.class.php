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
