<?php

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
   * Displays a post. if no id explicitely set, last post is shown.
   *
   * @param sfRequest $request A request object
   */
  public function executeShow(sfWebRequest $request)
  {
    // Retrieve appropriate post from database
    $post = Doctrine::getTable('Post')->getOnlinePostBySlug($request->getParameter('slug'));

    // Throw a 404 error if no post is found
    $this->forward404Unless($post);

    // Set specific page title
    $title = sprintf('%s - %s', $post->track_author, $post->track_title);
    if ($request->getParameter('c'))
    {
      $title .= sprintf(' | Playlist de %s', $post->getContributorDisplayName());
    }
    $title = sprintf('%s | Musique Approximative', $title);
    $this->getResponse()->setTitle($title);

    // Get number of online posts
    $posts_count = Doctrine::getTable('Post')->countOnlinePosts();

    // Define opengraph metadata (see http://ogp.me/)
    $urlTrack = sprintf('%s/%s', sfConfig::get('app_urls_tracks'), $post->track_filename);
    $this->getContext()->getConfiguration()->loadHelpers('Markdown');
    // TODO : find out how to get fb_app_id value
    // $this->getResponse()->addMeta('fb:app_id', 'musiqueapproximative');
    $this->getResponse()->addMeta('og:title', $title);
    $this->getResponse()->addMeta('og:description', trim(strip_tags(Markdown($post->body))));
    $this->getResponse()->addMeta('og:image', 'http://www.musiqueapproximative.net/images/logo.png');
    $this->getResponse()->addMeta('og:type', 'video');
    $this->getResponse()->addMeta(
      'og:video', 
      sprintf('http://www.musiqueapproximative.net/swf/mediaplayer-5.9/player.swf?autostart=true&file=%s', urlencode($urlTrack))
    );
    $this->getResponse()->addMeta('og:video:secure_url', sprintf('%s?autostart=true&file=%s', sfConfig::get('app_urls_secure_player'), urlencode($urlTrack)));
    $this->getResponse()->addMeta('og:video:type', 'application/x-shockwave-flash');
    $this->getResponse()->addMeta('og:video:height', '100');
    $this->getResponse()->addMeta('og:video:width', '500');
    $this->getResponse()->addMeta('og:url', $this->getController()->genUrl('@post_show?slug='.$post->slug, true));
    
    // Gather common query parameters
    $common_parameters = array(
      'random' => $request->getParameter('random', 0),
    );
    if ($request->getParameter('c'))
    {
      $common_parameters['c'] = $request->getParameter('c');
    }
    $common_query_string = '';
    foreach ($common_parameters as $name => $value)
    {
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
    $this->post_next = Doctrine::getTable('Post')->getNextPost($post, $request->getParameterHolder()->getAll());
    $this->post_previous = Doctrine::getTable('Post')->getPreviousPost($post, $request->getParameterHolder()->getAll());
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

  public function executeHome(sfWebRequest $request)
  {
    $filters = $request->getParameterHolder()->getAll();
    $this->forward404Unless($post = Doctrine::getTable('Post')->getLastPost($filters));
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
      $posts = Doctrine::getTable('Post')->search($request->getParameter('q'));
      $list_title = sprintf('%d résultat(s) pour la recherche "%s"', count($posts), $request->getParameter('q'));
    }
    else
    {
      $posts = Doctrine::getTable('Post')->getOnlinePosts($request->getParameter('c'));
      if ($request->hasParameter('c'))
      {
        $list_title = sprintf('%s a posté %d morceau(x) à ce jour', $request->getParameter('c'), count($posts));
      }
    }

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
    $feed = sfFeedPeer::newInstance('rss201');
    $feed->initialize(array(
      'title'         => 'Musique Approximative',
      'link'          => 'http://www.musiqueapproximative.net',
      'authorEmail'   => 'bertier@musiqueapproximative.net',
    ));

    $posts = Doctrine::getTable('Post')->getOnlinePosts($request->getParameter('contributor'), $request->getParameter('count', 50));
    foreach ($posts as $post)
    {
      $strf = strptime($post->publish_on, '%Y-%m-%d %H:%M:%S');
      $publish_timestamp = mktime($strf['tm_hour'], $strf['tm_min'], $strf['tm_sec'], $strf['tm_mon'] + 1, $strf['tm_mday'], $strf['tm_year'] + 1900);

      // Canonical URL to post's associated file
      $track_file_url = htmlspecialchars(sprintf('http://www.musiqueapproximative.net/tracks/%s', rawurlencode($post->track_filename)));

      // Make sure no errors are generated when files do not exist (useful in dev mode)
      if (!is_readable(sfConfig::get('sf_web_dir').'/tracks/'.$post->track_filename))
      {
        $file_size = 0;
      }
      else
      {
        $file_size = strlen(file_get_contents(sfConfig::get('sf_web_dir').'/tracks/'.$post->track_filename));
      }

      $item = new sfFeedItem();
      $item->initialize(array(
        'title'       => sprintf('%s - %s', $post->track_author, $post->track_title),
        'link'        => '@post_show?slug='.$post->slug,
        'authorName'  => $post->getSfGuardUser()->username,
        'pubDate'     => $publish_timestamp,
        'uniqueId'    => $post->slug,
        'description' => Markdown($post->body)
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
  	$post = Doctrine::getTable('Post')->getRandomPost($request->getParameterHolder()->getAll());

  	sfConfig::set('sf_web_debug', false);

  	// Pass data to view
  	$this->post = $post;
  }

  /**
   * Returns next post.
   *
   * @param  sfWebRequest $request
   * @return string
   */
  public function executeNext(sfWebRequest $request)
  {
  	$post = Doctrine::getTable('Post')->getNextPost(Doctrine::getTable('Post')->find($request->getParameter('current')), $request->getParameterHolder()->getAll());

  	sfConfig::set('sf_web_debug', false);

  	// Pass data to view
  	$this->post = $post;
  }

  public function executePrev(sfWebRequest $request)
  {
  	$post = Doctrine::getTable('Post')->getPreviousPost(Doctrine::getTable('Post')->find($request->getParameter('current')), $request->getParameterHolder()->getAll());

  	sfConfig::set('sf_web_debug', false);

  	// Pass data to view
  	$this->post = $post;
  }

  public function executeOembed(sfWebRequest $request)
  {
    // Retrieve appropriate post from database
    $post = Doctrine::getTable('Post')->getOnlinePostBySlug(basename($request->getParameter('url')));

    // Throw a 404 error if no post is found
    $this->forward404Unless($post);

    // Build data array
    $this->getContext()->getConfiguration()->loadHelpers('Markdown');
    $data = array(
      'version'       => 1,
      'type'          => 'rich',
      'provider_name' => 'MusiqueApproximative',
      'provider_url'  => 'http://www.musiqueapproximative.net',
      'height'        => 220,
      'width'         => 510,
      'title'         => sprintf('%s - %s', $post->track_author, $post->track_title),
      'description'   => strip_tags(Markdown($post->body)),
      'html'          => sprintf('<iframe width="510" height="220" scrolling="no" frameborder="no" src="%s?embed"></iframe>', $this->getController()->genUrl('@post_show?slug='.$post->slug, true))
    );

    // Encode data depending on requested format
    if ($request->getParameter('format', 'json') == 'json') {
      $dataEncoded = json_encode($data);  
      // $this->getResponse()->setContentType('application/json+oembed');
      $this->getResponse()->setContentType('application/json');
    } else if ($request->getParameter('format', 'json') == 'xml') {
      $xml = new SimpleXMLElement('<oembed/>');
      foreach ($data as $key => $value) {
        $xml->addChild($key, htmlentities($value));
      }
      $dataEncoded = $xml->asXml();
      $this->getResponse()->setContentType('text/xml+oembed');
    }

    // Pass data to view
    $this->data = $dataEncoded;
    
    // Select template
    return sfView::SUCCESS;
  }

  protected function setFormats(sfWebRequest $request)
  {
    $formats = array(
      'csv'  => array('layout' => false, 'contentType' => 'text/csv'),
      'json' => array('layout' => false, 'contentType' => 'application/json'),
      'max'  => array('layout' => false, 'contentType' => 'application/maxmsp+text'),
      'xspf' => array('layout' => false, 'contentType' => 'application/xspf+xml')
    );
    if (in_array($request->getParameter('format'), array_keys($formats))) {
      $request->setParameter('sf_format', $request->getParameter('format'));
      $this->setLayout($formats[$request->getParameter('format')]['layout']);
      $this->getResponse()->setContentType($formats[$request->getParameter('format')]['contentType']);
    }

    return $formats;

  }
}