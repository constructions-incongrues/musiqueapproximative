<?php

use AssetGatherer\AssetGatherer;
use Nyholm\Psr7\ServerRequest;

/**
 * post actions.
 *
 * @package    musique-approximative
 * @subpackage post
 * @author     Tristan Rivoallan <tristan@rivoallan.net>
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class postActions extends sfActions
{
  /**
   * Dimensions du lecteur embarquable servi par /post/:slug?embed.
   * Partagées par les métadonnées OpenGraph et la réponse oEmbed, qui doivent
   * annoncer les mêmes valeurs.
   */
  const EMBED_WIDTH = 510;
  const EMBED_HEIGHT = 220;

  private function getDisaster(sfWebRequest $request, sfWebResponse $response, array $query = [])
  {
    $this->getContext()->getConfiguration()->loadHelpers('Desastre');
    apply_desastre($request, $response, $query);
  }

  /**
   * Force une URL absolue en HTTPS.
   */
  private function toSecureUrl($url)
  {
    return preg_replace('#^http://#i', 'https://', $url);
  }

  /**
   * Type MIME d'un fichier de piste, déduit de son extension.
   */
  private function getTrackMimeType($filename)
  {
    $types = array(
      'mp3'  => 'audio/mpeg',
      'ogg'  => 'audio/ogg',
      'oga'  => 'audio/ogg',
      'm4a'  => 'audio/mp4',
      'flac' => 'audio/flac',
      'wav'  => 'audio/wav',
    );
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    return isset($types[$extension]) ? $types[$extension] : 'audio/mpeg';
  }

  /**
   * Displays a post. if no id explicitely set, last post is shown.
   *
   * @param sfRequest $request A request object
   */
  /**
   * Formats machine servis par ce module, et leur type de contenu.
   *
   * Sert a decider si une erreur doit revenir dans un format machine plutot que
   * dans la page HTML habituelle.
   */
  protected function getMachineFormat(sfWebRequest $request)
  {
    $formats = array(
      'json' => 'application/json',
      'xspf' => 'application/xspf+xml',
      'max'  => 'application/maxmsp+text',
    );

    $format = $request->getParameter('format');

    return isset($formats[$format]) ? array($format, $formats[$format]) : null;
  }

  /**
   * Sert une erreur dans le format machine demande.
   *
   * Renvoie false lorsque la demande n'est pas faite dans un format machine :
   * l'appelant laisse alors le socle rendre sa page d'erreur habituelle.
   *
   * Le corps est construit par ApiErrorResponse, ecrite et testee de longue date
   * et dont c'est le premier appelant.
   */
  protected function renderApiError(sfWebRequest $request, $status, $title, $detail = '', $forceJson = false)
  {
    $machine = $this->getMachineFormat($request);

    if (null === $machine && !$forceJson) {
      return false;
    }

    // Les routes qui ne servent que du JSON n'ont pas de parametre `format` :
    // elles forcent le rendu.
    $contentType = null === $machine ? 'application/json' : $machine[1];

    $this->getResponse()->setStatusCode($status);
    $this->getResponse()->setContentType($contentType);

    $corps = ApiErrorResponse::format($status, $title, $detail);

    sfConfig::set('sf_web_debug', false);
    $this->renderText(json_encode($corps));

    return true;
  }

  public function executeShow(sfWebRequest $request)
  {
    // Retrieve appropriate post from database
    $post = Doctrine_Core::getTable('Post')->getOnlinePostBySlug($request->getParameter('slug'));

    if (!$post) {
      // Dans un format machine, l'erreur revient dans ce format ; sinon on laisse
      // le socle rendre la page d'erreur habituelle, qui ne change pas.
      if ($this->renderApiError($request, 404, 'Morceau introuvable',
        sprintf('Aucun morceau publiable ne porte l\'identifiant « %s ».', $request->getParameter('slug')))) {
        return sfView::NONE;
      }

      $this->forward404();
    }

    $this->getDisaster($request, $this->getResponse(), [
      "artist" => $post->track_author, 
      "title" => $post->track_title, 
      "contributor" => strtolower($post->getContributorDisplayName())
    ]);

    // Set specific page title
    $title = sprintf('%s - %s', $post->track_author, $post->track_title);
    if ($request->getParameter('c'))
    {
      $title .= sprintf(' | Playlist de %s', $post->getContributorDisplayName());
    }
    $title = sprintf('%s | %s', $title, sfConfig::get('app_title'));
    $this->getResponse()->setTitle($title);

    // Get number of online posts
    $posts_count = Doctrine_Core::getTable('Post')->countOnlinePosts();

    // Define opengraph metadata (see http://ogp.me/)
    // Post::getTrackUrl() est la seule construction d'URL de morceau du projet.
    $urlTrack = $post->getTrackUrl('https');
    $urlEmbed = $this->toSecureUrl(sprintf('%s?embed', $this->getController()->genUrl('@post_show?slug='.$post->slug, true)));
    $this->getContext()->getConfiguration()->loadHelpers('Markdown');
    $this->getResponse()->addMeta('og:title', $title);
    $this->getResponse()->addMeta('og:description', trim(strip_tags(Markdown($post->body))));

    // Glitch logo ?
    $this->is_glitch_active = (rand(1, sfConfig::get('app_glitch_divisor', 10)) == 1);

    if (sfConfig::get('app_theme') == 'musiqueapproximative' && $this->is_glitch_active) {
      $urlImg = sprintf('https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=%s/images/logo_500.png', $post->id, rand(0, 100), $request->getUriPrefix());
    } else {
      $urlImg = sprintf('%s/theme/%s/images/logo_500.png', $request->getUriPrefix(), sfConfig::get('app_theme'));
    }
    $this->getResponse()->addMeta('og:image', $urlImg);
    $this->getResponse()->addMeta('og:image:type', 'image/png');
    $this->getResponse()->addMeta('og:image:height', '476');
    $this->getResponse()->addMeta('og:image:width', '476');
    $this->getResponse()->addMeta('og:type', 'music.song');
    $this->getResponse()->addMeta('og:video', $urlEmbed);
    $this->getResponse()->addMeta('og:video:secure_url', $urlEmbed);
    $this->getResponse()->addMeta('og:video:type', 'text/html');
    $this->getResponse()->addMeta('og:video:height', (string) self::EMBED_HEIGHT);
    $this->getResponse()->addMeta('og:video:width', (string) self::EMBED_WIDTH);
    $this->getResponse()->addMeta('og:audio', $urlTrack);
    $this->getResponse()->addMeta('og:audio:secure_url', $urlTrack);
    $this->getResponse()->addMeta('og:audio:type', $this->getTrackMimeType($post->track_filename));
    $this->getResponse()->addMeta('og:url', $this->getController()->genUrl('@post_show?slug='.$post->slug, true));

    // Gather common query parameters
    $common_parameters = array(
      'random' => $request->getParameter('random', 0),
    );
    if ($request->getParameter('c')) {
      $common_parameters['c'] = $request->getParameter('c');
    }
    $common_query_string = '';
    foreach ($common_parameters as $name => $value) {
      $common_query_string .= sprintf('%s=%s&', $name, $value);
    }
    $common_query_string = trim($common_query_string, '&');

    // Formats specifics
    $formats = $this->setFormats($request);
    $formatsLimited = array();
    $formatsLimited['json'] = $formats['json'];

    // Pass data to view
    $this->formats = $formatsLimited;
    $this->post = $post;
    $this->posts_count = $posts_count;
    $this->post_next = Doctrine_Core::getTable('Post')->getNextPost($post, $request->getParameterHolder()->getAll());
    $this->post_previous = Doctrine_Core::getTable('Post')->getPreviousPost($post, $request->getParameterHolder()->getAll());
    $this->common_query_string = $common_query_string;
    $this->contributor = $post->getSfGuardUser();

    // Select template
    if ($request->hasParameter('embed')) {
      $templateName = 'Embed'.ucfirst($request->getParameter('embed'));
    } else {
      $templateName = sfView::SUCCESS;
    }

    return $templateName;
  }

  /**
   * Verification d'exploitation : l'encodage de la connexion a la base.
   *
   * Ce que cette route repond, et ce qu'elle ne repond pas.
   *
   * La base a ete migree en utf8mb4 le 2026-08-18, et la connexion aussi. Mais
   * `databases.yml` n'expose aucune valeur observable de l'exterieur : si la
   * ligne `encoding` disparaissait — un `make configure` malheureux sur le
   * serveur suffit, c'est arrive le meme jour — la connexion se remettrait a
   * convertir les caracteres, et personne ne le saurait avant le prochain titre
   * detruit.
   *
   * Le verdict porte donc sur la CONNEXION, et sur rien d'autre. Il ne dit pas
   * qu'un titre cyrillique survivra : les tables ayant deja ete converties, le
   * jeu de caracteres de la connexion est la derniere variable connue entre un
   * titre saisi et un titre stocke, mais « derniere variable connue » n'est pas
   * « preuve ».
   *
   * D'ou les deux chiffres qui accompagnent le verdict, et sans lesquels un
   * « conforme » vaudrait pour ce qu'il ne vaut pas :
   *
   *   - le nombre de caracteres hors cp1252 REELLEMENT STOCKES, et la date du
   *     dernier. Zero, sur dix-huit ans, au 2026-08-19. Tant que ce champ est
   *     vide, le vert signifie « configuration correcte, preuve jamais faite ».
   *   - le nombre de titres deja alteres encore en base. Soixante et un au
   *     2026-08-19. Un « conforme » affiche a cote de ce nombre ne se lit plus
   *     de la meme facon.
   *
   * Les deux sont CALCULES et non ecrits en dur : le jour ou un titre cyrillique
   * survivra, ce champ se remplira tout seul, et le voyant cessera enfin de
   * n'etre qu'un voyant.
   *
   * En lecture seule. C'est ce qui permet a cette route d'etre publique.
   */
  public function executeEncodage(sfWebRequest $request)
  {
    $this->getResponse()->setContentType('application/json');
    // Une verification en cache rapporte l'etat de la veille. `cache.yml` la
    // sort du cache de page ; ces en-tetes en sortent les intermediaires.
    $this->getResponse()->setHttpHeader('Cache-Control', 'no-store, max-age=0');

    $attendu = 'utf8mb4';
    $reponse = array(
      'verdict'          => 'indetermine',
      'portee'           => 'connexion',
      'ne_prouve_pas'    => 'qu un titre hors cp1252 survit a une ecriture ; les tables sont converties, mais la production ne l a jamais demontre',
      'encodage_attendu' => $attendu,
    );

    try
    {
      $connexion = Doctrine_Manager::getInstance()->getCurrentConnection();

      $variables = $connexion->fetchAssoc('SHOW VARIABLES LIKE ?', array('character_set_connection'));
      $constate = isset($variables[0]['Value']) ? $variables[0]['Value'] : null;

      $reponse['encodage_constate'] = $constate;
      $reponse['verdict'] = ($constate === $attendu) ? 'conforme' : 'non-conforme';

      // Un caractere hors cp1252 ne survit pas a un aller-retour latin1. La
      // comparaison est binaire : une collation qui traite deux chaines comme
      // equivalentes masquerait justement la difference qu'on cherche.
      $horsJeu = 'CONVERT(CONVERT(%1$s USING latin1) USING utf8mb4) COLLATE utf8mb4_bin <> %1$s COLLATE utf8mb4_bin';
      $altere = "%s LIKE '%%??%%'";

      $mesure = $connexion->fetchAssoc(sprintf(
        'SELECT
           SUM(CASE WHEN (%s OR %s) THEN 1 ELSE 0 END) AS hors_cp1252,
           MAX(CASE WHEN (%s OR %s) THEN publish_on END) AS dernier_hors_cp1252,
           SUM(CASE WHEN (%s OR %s) THEN 1 ELSE 0 END) AS alteres
         FROM post WHERE %s',
        sprintf($horsJeu, 'track_title'), sprintf($horsJeu, 'track_author'),
        sprintf($horsJeu, 'track_title'), sprintf($horsJeu, 'track_author'),
        sprintf($altere, 'track_title'), sprintf($altere, 'track_author'),
        str_replace('p.', '', PostTable::WHERE_ONLINE)
      ));

      $reponse['caracteres_hors_cp1252_stockes'] = (int) $mesure[0]['hors_cp1252'];
      $reponse['dernier_stocke_hors_cp1252']     = $mesure[0]['dernier_hors_cp1252'];
      $reponse['titres_alteres_en_base']         = (int) $mesure[0]['alteres'];
    }
    catch (Exception $exception)
    {
      // Une base injoignable n'est pas un encodage fautif. Les confondre
      // produirait le bruit qui fait desactiver une alerte.
      $reponse['verdict'] = 'indetermine';
      $reponse['motif']   = 'base injoignable';
      $this->getResponse()->setStatusCode(503);
    }

    echo json_encode($reponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    sfConfig::set('sf_web_debug', false);

    return sfView::NONE;
  }

  public function executeMd5(sfWebRequest $request)
  {
    $post = Doctrine_Core::getTable('Post')->getByMd5Sum($request->getParameter('md5sum'));

    if (!$post) {
      $this->renderApiError($request, 404, 'Morceau introuvable',
        sprintf('Aucun morceau publiable ne porte l\'empreinte « %s ».', $request->getParameter('md5sum')),
        true);

      return sfView::NONE;
    }

    $this->getResponse()->setContentType('application/json');

    // Meme enveloppe que les autres routes qui rendent un morceau en JSON :
    // « Even single ressources are displayed as lists », comme le disent
    // showSuccess.json.php et listSuccess.json.php. Sans elle, cette route
    // servait l'objet nu et imposait un second analyseur au consommateur.
    //
    // Aucun html_entity_decode : cette route n'en a jamais applique, les deux
    // gabarits JSON si — et le faire sur un document deja encode rendait
    // /posts?format=json inanalysable. Ils s'alignent desormais sur elle ; voir
    // showSuccess.json.php pour le detail du defaut.
    echo sprintf('{ "posts": [%s] }', $post->toJson($request, $this->getContext()));
    sfConfig::set('sf_web_debug', false);
    return sfView::NONE;
  }

  public function executeHome(sfWebRequest $request)
  {
    $filters = $request->getParameterHolder()->getAll();
    $this->forward404Unless($post = Doctrine_Core::getTable('Post')->getLastPost($filters));
    $routeRedirect = '@post_show?slug='.$post->slug;
    if (isset($filters['c']))
    {
      $routeRedirect .= '&c='.$filters['c'];
    }
    $this->redirect($routeRedirect);
  }

  public function executeList(sfWebRequest $request)
  {
    $list_title = null;
    if ($this->getRequestParameter('q'))
    {
      $posts = Doctrine_Core::getTable('Post')->search($request->getParameter('q'));
      $list_title = sprintf('%d résultat(s) pour la recherche "%s" | %s', count($posts), $request->getParameter('q'), sfConfig::get('app_title'));
      $this->getResponse()->setTitle($list_title);
    }
    else
    {
      $posts = Doctrine_Core::getTable('Post')->getOnlinePosts($request->getParameter('c'));
      if ($request->hasParameter('c'))
      {
        $list_title = sprintf('%s a posté %d morceau(x) à ce jour', $request->getParameter('c'), count($posts));
        $this->getResponse()->setTitle(sprintf('La playlist de %s | %s', $request->getParameter('c'), sfConfig::get('app_title')));
      }
    }

    $this->getDisaster($request, $this->getResponse(), [
      "query" => $this->getRequestParameter('q'), 
      "contributor" => strtolower($request->getParameter('c'))
    ]);

    // Formats specifics
    $formats = $this->setFormats($request);

    // Pass data to view
    $this->formats = $formats;
    $this->posts = $posts;
    $this->list_title = $list_title;
  }

  public function executeFeed(sfWebRequest $request)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers('Markdown');

    // Use app_url_root config or fall back to request URI prefix
    $feedLink = sfConfig::get('app_url_root') ?: $request->getUriPrefix();

    $feed = sfFeedPeer::newInstance('rss201');
    $feed->initialize(array(
      'title'         => sfConfig::get('app_title'),
      'link'          => $feedLink,
      'authorEmail'   => 'bertier@musiqueapproximative.net',
      'description'   => "C'est l'exutoire anarchique d'une bande de mélomanes fêlé⋅e⋅s. C’est une playlist infernale alimentée chaque jour par les obsessions et les découvertes de chacun⋅e. L’arbitraire y est roi et on s’y amuse bien : c’est Musique Approximative.",
      'language'      => 'fr'
    ));

    // Feed logo
    $feedImage = new sfFeedImage();
    $feedImage->setFavicon(sprintf('%s/favicon.ico', $request->getUriPrefix()));
    $feedImage->setImage(sprintf('%s/images/glitched_logo.png', $request->getUriPrefix()));
    $feedImage->setTitle(sfConfig::get('app_title'));
    $feedImage->setLink($feedLink);
    $feed->setImage($feedImage);

    $posts = Doctrine_Core::getTable('Post')->getOnlinePosts($request->getParameter('contributor'), $request->getParameter('count', 50));
    foreach ($posts as $post)
    {
      $strf = strptime($post->publish_on, '%Y-%m-%d %H:%M:%S');
      $publish_timestamp = mktime($strf['tm_hour'], $strf['tm_min'], $strf['tm_sec'], $strf['tm_mon'] + 1, $strf['tm_mday'], $strf['tm_year'] + 1900);

      // Canonical URL to post's associated file
      $track_file_url = htmlspecialchars($post->getTrackUrl($request->isSecure() ? 'https' : 'http'));

      // Make sure no errors are generated when files do not exist (useful in dev mode)
      $track_file_path = $post->getTrackPath();
      if (!is_readable($track_file_path))
      {
        $file_size = 0;
      }
      else
      {
        // Taille demandée au système de fichiers : lire le fichier entier en mémoire
        // pour n'en mesurer que la longueur coûtait une lecture complète par item et
        // par requête sur le flux.
        $file_size = filesize($track_file_path);
      }

      // Glitch logo ?
      $is_glitch_active = (rand(1, sfConfig::get('app_glitch_divisor', 10)) == 1);
      if (sfConfig::get('app_theme') == 'musiqueapproximative' && $is_glitch_active) {
        $urlImg = sprintf('https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=%s/images/logo_500.png', $post->id, rand(0, 100), $request->getUriPrefix());
      } else {
        $urlImg = sprintf('%s/theme/%s/images/logo_500.png', $request->getUriPrefix(), sfConfig::get('app_theme'));
      }

      $item = new sfFeedItem();
      $item->initialize(array(
        'title'       => sprintf('%s - %s', $post->track_author, $post->track_title),
        'link'        => '@post_show?slug='.$post->slug,
        'authorName'  => $post->getContributorDisplayName(),
        'pubDate'     => $publish_timestamp,
        'uniqueId'    => $post->slug,
        'description' => sprintf('<p><a href="%s"><img src="%s" border="0" /></a></p>%s<p><small>Contribué par %s</small></p>', $this->getController()->genUrl('@post_show?slug='.$post->slug, true), $urlImg, Markdown($post->body), $post->getContributorDisplayName())
      ));
      $enclosure = new sfFeedEnclosure();
      $enclosure->initialize(array(
        'url'       => $track_file_url,
        'length'    => $file_size,
        'mimeType'  => 'audio/mpeg'
      ));
      $item->setEnclosure($enclosure);
      $feed->addItem($item);
    }
    $this->feed = $feed;
  }

  public function executeRandom(sfWebRequest $request)
  {
    $post = Doctrine_Core::getTable('Post')->getRandomPost($request->getParameterHolder()->getAll());
    return $this->renderJsonPost($post);
  }

  /**
   * Returns next post.
   *
   * @param  sfWebRequest $request
   * @return string
   */
  public function executeNext(sfWebRequest $request)
  {
    $courant = $request->getParameter('current');

    if (null === $courant || '' === $courant) {
      // Parametre declare obligatoire au contrat : son absence est une demande
      // mal formee, non une ressource absente.
      $this->renderApiError($request, 400, 'Morceau courant non precise',
        'Le parametre « current » est obligatoire : il designe le morceau depuis lequel naviguer.', true);

      return sfView::NONE;
    }

    $depuis = Doctrine_Core::getTable('Post')->find($courant);

    if (!$depuis) {
      // Sans cette verification, `false` etait passe a getNextPost(Post $post, ...)
      // et l'action levait une TypeError, donc un 500.
      $this->renderApiError($request, 404, 'Morceau courant introuvable',
        sprintf('Aucun morceau ne porte l\'identifiant « %s ».', $courant), true);

      return sfView::NONE;
    }

    $post = Doctrine_Core::getTable('Post')->getNextPost($depuis, $request->getParameterHolder()->getAll());

    if (!$post || !$post->slug) {
      $this->renderApiError($request, 404, 'Aucun morceau suivant',
        'Ce morceau n\'a pas de suivant dans la selection demandee.', true);

      return sfView::NONE;
    }

    return $this->renderJsonPost($post);
  }

  public function executePrev(sfWebRequest $request)
  {
    $courant = $request->getParameter('current');

    if (null === $courant || '' === $courant) {
      // Parametre declare obligatoire au contrat : son absence est une demande
      // mal formee, non une ressource absente.
      $this->renderApiError($request, 400, 'Morceau courant non precise',
        'Le parametre « current » est obligatoire : il designe le morceau depuis lequel naviguer.', true);

      return sfView::NONE;
    }

    $depuis = Doctrine_Core::getTable('Post')->find($courant);

    if (!$depuis) {
      // Sans cette verification, `false` etait passe a getPreviousPost(Post $post, ...)
      // et l'action levait une TypeError, donc un 500.
      $this->renderApiError($request, 404, 'Morceau courant introuvable',
        sprintf('Aucun morceau ne porte l\'identifiant « %s ».', $courant), true);

      return sfView::NONE;
    }

    $post = Doctrine_Core::getTable('Post')->getPreviousPost($depuis, $request->getParameterHolder()->getAll());

    if (!$post || !$post->slug) {
      $this->renderApiError($request, 404, 'Aucun morceau precedent',
        'Ce morceau n\'a pas de precedent dans la selection demandee.', true);

      return sfView::NONE;
    }

    return $this->renderJsonPost($post);
  }

  protected function renderJsonPost($post)
  {
    $this->getResponse()->setContentType('application/json');
    $data = array(
      'url'   => $this->getController()->genUrl('@post_show?slug='.$post->slug),
      'title' => sprintf('%s - %s', $post->track_author, $post->track_title)
    );
    sfConfig::set('sf_web_debug', false);
    return $this->renderText(json_encode($data));
  }

  public function executeOembed(sfWebRequest $request)
  {
    // Retrieve appropriate post from database
    $post = Doctrine_Core::getTable('Post')->getOnlinePostBySlug(basename($request->getParameter('url')));

    if (!$post) {
      // Dans un format machine, l'erreur revient dans ce format ; sinon on laisse
      // le socle rendre la page d'erreur habituelle, qui ne change pas.
      if ($this->renderApiError($request, 404, 'Morceau introuvable',
        sprintf('Aucun morceau publiable ne porte l\'identifiant « %s ».', $request->getParameter('slug')))) {
        return sfView::NONE;
      }

      $this->forward404();
    }

    // Build data array
    $this->getContext()->getConfiguration()->loadHelpers('Markdown');
    $data = array(
      'version'       => 1,
      'type'          => 'rich',
      'provider_name' => 'MusiqueApproximative',
      'provider_url'  => sfConfig::get('app_url_root'),
      'height'        => self::EMBED_HEIGHT,
      'width'         => self::EMBED_WIDTH,
      'title'         => sprintf('%s - %s', $post->track_author, $post->track_title),
      'description'   => strip_tags(Markdown($post->body)),
      'html'          => sprintf('<iframe width="%d" height="%d" scrolling="no" frameborder="no" src="%s?embed"></iframe>', self::EMBED_WIDTH, self::EMBED_HEIGHT, $this->getController()->genUrl('@post_show?slug='.$post->slug, true))
    );

    // Encode data depending on requested format
    $format = strtolower(trim($request->getParameter('format', 'json')));
    if ($format == 'json') {
      $dataEncoded = json_encode($data);
      $this->getResponse()->setContentType('application/json+oembed');
    } else if ($format == 'xml') {
      $xml = new SimpleXMLElement('<oembed/>');
      foreach ($data as $key => $value) {
        $xml->addChild($key, htmlentities($value));
      }
      $dataEncoded = $xml->asXml();
      $this->getResponse()->setContentType('text/xml+oembed');
    } else {
      // La spécification oEmbed demande 501 quand le format demandé n'est pas
      // pris en charge. Auparavant la réponse partait vide, en 200.
      $this->getResponse()->setStatusCode(501);
      sfConfig::set('sf_web_debug', false);

      return sfView::NONE;
    }

    // Pass data to view
    $this->data = $dataEncoded;

    // Select template
    return sfView::SUCCESS;
  }

  protected function setFormats(sfWebRequest $request)
  {
    $formats = array(
      'json' => array(
        'layout'      => false,
        'contentType' => 'application/json',
        'about'       => 'http://jsonapi.org/format/#url-based-json-api',
        'display'     => true
      ),
      'max' => array(
        'layout'      => false,
        'contentType' => 'application/maxmsp+text',
        'about'       => null,
        'display'     => false
      ),
      'xspf' => array(
        'layout'      => false,
        'contentType' => 'application/xspf+xml',
        'about'       => 'http://xspf.org/',
        'display'     => true
      )
    );
    if (in_array($request->getParameter('format'), array_keys($formats))) {
      $request->setParameter('sf_format', $request->getParameter('format'));
      $this->setLayout($formats[$request->getParameter('format')]['layout']);
      $this->getResponse()->setContentType($formats[$request->getParameter('format')]['contentType']);
    }

    return $formats;
  }
}
