<?php

class PostTable extends Doctrine_Table
{
  const FIELDS_BASIC = 'p.body, p.track_title, p.track_author, p.track_filename, p.slug, p.buy_url, u.username';

  /**
   * Returns last online post.
   *
   * @return Post
   */
  public function getLastPost(array $filters = array())
  {
    // Build base query
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on <= date_add(now(), interval 2 hour)')
      ->orderBy('p.publish_on DESC')
      ->limit(1);

    // Add additional filters
    if (isset($filters['c']))
    {
      $q->andWhere('u.username = ?', $filters['c']);
    }

    // Fetch posts
    $post = $q->fetchOne();

    $q->free();

    return $post;
  }

  public function getOnlinePostBySlug($post_slug)
  {
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on <= date_add(now(), interval 2 hour) and p.slug = ?');
    $post = $q->fetchOne(array($post_slug));
    $q->free();

    return $post;
  }

  public function getOnlinePostById($post_id)
  {
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on <= date_add(now(), interval 2 hour) and p.id = ?');
    $post = $q->fetchOne(array($post_id));
    $q->free();

    return $post;
  }

  /**
   * Returns next post.
   *
   * @param  Post $post
   * @return Post
   */
  public function getNextPost(Post $post, array $filters = array())
  {
    // Build base query
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on > ? and p.publish_on <= date_add(now(), interval 2 hour)')
      ->orderBy('p.publish_on ASC')
      ->limit(1);

    // Add additional filters
    if (isset($filters['c']))
    {
      $q->andWhere('u.username = ?', $filters['c']);
    }

    // Fetch posts
    $post = $q->fetchOne(array($post->publish_on));
    $q->free();

    return $post;
  }

  /**
   * Returns previous post.
   *
   * @param  Post $post
   * @return Post
   */
  public function getPreviousPost(Post $post, array $filters = array())
  {
    // Build base query
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on < ?')
      ->orderBy('p.publish_on DESC')
      ->limit(1);

    // Add additional filters
    if (isset($filters['c']))
    {
      $q->andWhere('u.username = ?', $filters['c']);
    }

    // Fetch results
    $post = $q->fetchOne(array($post->publish_on));
    $q->free();

    return $post;
  }

  protected function buildOnlinePostsQuery($contributor = null, $count = null)
  {
    $q = Doctrine_Query::create()
      ->select('*')
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on <= date_add(now(), interval 2 hour)')
      ->orderBy('p.publish_on DESC');

    if ($contributor)
    {
      $q->andWhere('u.username = ?', (string)$contributor);
    }

    if ($count)
    {
      $q->limit($count);
    }

    return $q;
  }

  public function getOnlinePosts($contributor = null, $count = null)
  {
    return $this->buildOnlinePostsQuery($contributor, $count)->execute();
  }

  public function countOnlinePosts($contributor = null, $count = null)
  {
    return $this->buildOnlinePostsQuery($contributor, $count)->count();
  }

  public function search($query)
  {
    $results = parent::search($query);
    $posts = array();
    foreach ($results as $result)
    {
      $post = $this->getOnlinePostById($result['id']);
      if ($post)
      {
        $posts[] = $post;
      }
    }

    return $posts;
  }

  /**
   * Returns random post.
   *
   * @param  Post $post
   * @return Post
   */
  public function getRandomPost(array $filters = array())
  {
    // Build base query
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.publish_on <= date_add(now(), interval 2 hour)')
      ->orderBy('rand()')
      ->limit(1);

    // Add additional filters
    if (isset($filters['c']))
    {
      $q->andWhere('u.username = ?', $filters['c']);
    }

    // Fetch posts
    $post = $q->fetchOne();
    $q->free();

    return $post;
  }
}
