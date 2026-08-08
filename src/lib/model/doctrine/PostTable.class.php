<?php

class PostTable extends Doctrine_Table
{
  const FIELDS_BASIC = 'p.body, p.track_title, p.track_author, p.track_filename, p.track_md5, p.slug, p.buy_url, u.username';

  /**
   * Regle de visibilite unique. Les noms de colonnes etant identiques en DQL
   * et en SQL, cette expression sert aux deux : au constructeur de requetes
   * Doctrine et aux agregats en SQL brut.
   */
  const WHERE_ONLINE = "p.is_online = 1 AND p.publish_on <= DATE_ADD(NOW(), INTERVAL 2 HOUR) AND p.slug IS NOT NULL AND p.slug != ''";

  /** Champs necessaires a la serialisation Subsonic. Exclut body (TEXT). */
  const FIELDS_SUBSONIC = 'p.id, p.track_title, p.track_author, p.track_filename, p.track_duration, p.track_size, p.publish_on, p.slug, u.username';

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

  public function buildOnlinePostsQuery($contributor = null, $count = null, $fields = '*')
  {
    $q = Doctrine_Query::create()
      ->select($fields)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where(self::WHERE_ONLINE)
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

  public function getByMd5sum($md5sum)
  {
    $q = Doctrine_Query::create()
      ->select(self::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.track_md5 = ?');
    $post = $q->fetchOne(array($md5sum));

    return $post;
  }

  /*
   * ---------------------------------------------------------------------
   * API Subsonic
   *
   * Les mois de publication (« 2024-06 ») font office de pseudo-albums.
   * Ces agregats sont ecrits en SQL brut : Doctrine 1 hydrate mal un GROUP
   * BY sans entite racine, et MySQL 5.7 active ONLY_FULL_GROUP_BY, ce qui
   * interdit l'ORDER BY p.publish_on que pose buildOnlinePostsQuery() sur
   * une requete groupee par mois. Chaque requete interpole WHERE_ONLINE :
   * meme regle de visibilite, deux moteurs d'execution.
   * ---------------------------------------------------------------------
   */

  /**
   * Liste les mois de publication (pseudo-albums), du plus recent au plus
   * ancien par defaut.
   *
   * @param  int|null $limit
   * @param  int      $offset
   * @param  string   $order  'ASC' ou 'DESC'
   * @return array
   */
  public function getMonths($limit = null, $offset = 0, $order = 'DESC')
  {
    $order = ('ASC' === strtoupper($order)) ? 'ASC' : 'DESC';

    $sql = sprintf(
      'SELECT
          DATE_FORMAT(p.publish_on, "%%Y-%%m") AS month,
          COUNT(*) AS song_count,
          SUM(p.track_duration) AS duration,
          MIN(p.id) AS first_post_id,
          MIN(YEAR(p.publish_on)) AS year,
          MIN(p.publish_on) AS created
        FROM post p
        WHERE %s
        GROUP BY month
        ORDER BY month %s',
      self::WHERE_ONLINE,
      $order
    );

    // LIMIT/OFFSET ne sont pas lies via placeholder : sous emulation de
    // requetes preparees (active par defaut pour PDO MySQL), Doctrine cite
    // les valeurs liees comme des chaines, et MySQL refuse une chaine
    // citee a cet endroit ("LIMIT '1'" est une erreur de syntaxe). Les
    // deux valeurs sont converties en entier juste avant, donc sans risque
    // d'injection.
    if ($limit)
    {
      $sql .= sprintf(' LIMIT %d', (int) $limit);

      if ($offset)
      {
        $sql .= sprintf(' OFFSET %d', (int) $offset);
      }
    }

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
  }

  /**
   * Agregat d'un seul mois de publication (pseudo-album).
   *
   * @param  string $month  'AAAA-MM'
   * @return array|null
   */
  public function getMonth($month)
  {
    $sql = sprintf(
      'SELECT
          DATE_FORMAT(p.publish_on, "%%Y-%%m") AS month,
          COUNT(*) AS song_count,
          SUM(p.track_duration) AS duration,
          MIN(p.id) AS first_post_id,
          MIN(YEAR(p.publish_on)) AS year,
          MIN(p.publish_on) AS created
        FROM post p
        WHERE %s AND DATE_FORMAT(p.publish_on, "%%Y-%%m") = ?
        GROUP BY month',
      self::WHERE_ONLINE
    );

    $rows = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql, array($month));

    return $rows ? $rows[0] : null;
  }

  /**
   * Morceaux publies durant un mois donne, dans l'ordre de publication.
   * Passe par FIELDS_SUBSONIC pour eviter le lazy-load champ par champ.
   *
   * @param  string $month  'AAAA-MM'
   * @return Doctrine_Collection
   */
  public function getPostsByMonth($month)
  {
    $q = $this->buildOnlinePostsQuery(null, null, self::FIELDS_SUBSONIC)
      ->andWhere("DATE_FORMAT(p.publish_on, '%Y-%m') = ?", $month)
      ->orderBy('p.publish_on ASC');

    $posts = $q->execute();
    $q->free();

    return $posts;
  }

  /**
   * Artistes distincts, avec le nombre de mois (pseudo-albums) ou chacun
   * apparait.
   *
   * @param  string|null $like    filtre LIKE sur track_author
   * @param  int|null    $limit
   * @param  int         $offset
   * @return array
   */
  public function getDistinctArtists($like = null, $limit = null, $offset = 0)
  {
    $sql = sprintf(
      'SELECT
          p.track_author,
          COUNT(DISTINCT DATE_FORMAT(p.publish_on, "%%Y-%%m")) AS album_count
        FROM post p
        WHERE %s',
      self::WHERE_ONLINE
    );

    $params = array();

    if ($like)
    {
      $sql .= ' AND p.track_author LIKE ?';
      $params[] = '%'.$like.'%';
    }

    $sql .= ' GROUP BY p.track_author ORDER BY p.track_author ASC';

    // Cf. le commentaire dans getMonths() : LIMIT/OFFSET s'interpolent en
    // entiers, pas en placeholders lies.
    if ($limit)
    {
      $sql .= sprintf(' LIMIT %d', (int) $limit);

      if ($offset)
      {
        $sql .= sprintf(' OFFSET %d', (int) $offset);
      }
    }

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql, $params);
  }

  /**
   * Mois de publication (pseudo-albums) d'un artiste donne, du plus recent
   * au plus ancien.
   *
   * @param  string $author
   * @return array
   */
  public function getMonthsByArtist($author)
  {
    $sql = sprintf(
      'SELECT
          DATE_FORMAT(p.publish_on, "%%Y-%%m") AS month,
          COUNT(*) AS song_count,
          SUM(p.track_duration) AS duration,
          MIN(p.id) AS first_post_id,
          MIN(YEAR(p.publish_on)) AS year,
          MIN(p.publish_on) AS created
        FROM post p
        WHERE %s AND p.track_author = ?
        GROUP BY month
        ORDER BY month DESC',
      self::WHERE_ONLINE
    );

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql, array($author));
  }

  /**
   * Morceaux d'un artiste donne, dans l'ordre de publication. Passe par
   * FIELDS_SUBSONIC pour eviter le lazy-load champ par champ.
   *
   * @param  string   $author
   * @param  int|null $limit
   * @param  int      $offset
   * @return Doctrine_Collection
   */
  public function getPostsByArtist($author, $limit = null, $offset = 0)
  {
    $q = $this->buildOnlinePostsQuery(null, $limit, self::FIELDS_SUBSONIC)
      ->andWhere('p.track_author = ?', $author)
      ->orderBy('p.publish_on ASC');

    if ($offset)
    {
      $q->offset($offset);
    }

    $posts = $q->execute();
    $q->free();

    return $posts;
  }

  /**
   * Contributeurs, avec nombre et duree cumulee de leurs morceaux visibles.
   *
   * @return array
   */
  public function getContributors()
  {
    $sql = sprintf(
      'SELECT
          u.username,
          up.display_name,
          COUNT(*) AS song_count,
          SUM(p.track_duration) AS duration,
          MIN(p.publish_on) AS created
        FROM post p
        INNER JOIN sf_guard_user u ON u.id = p.contributor_id
        LEFT JOIN user_profile up ON up.user_id = u.id
        WHERE %s
        GROUP BY u.id, u.username, up.display_name
        ORDER BY u.username ASC',
      self::WHERE_ONLINE
    );

    return Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
  }

  /**
   * Recherche de morceaux par titre ou artiste (LIKE). N'utilise pas
   * PostTable::search() (Doctrine Searchable) : celle-ci ne renvoie que des
   * ids classes par pertinence, peut ne produire aucun resultat sur un nom
   * d'artiste, et refait une requete par resultat sans aucune borne — sur
   * une surface publique non authentifiee interrogee en saisie incrementale,
   * c'est de l'amplification gratuite.
   *
   * Une requete vide renvoie l'ensemble (pagine) des morceaux visibles.
   *
   * @param  string   $query
   * @param  int      $limit
   * @param  int      $offset
   * @return Doctrine_Collection
   */
  public function searchSongs($query, $limit = 20, $offset = 0)
  {
    $q = $this->buildOnlinePostsQuery(null, $limit, self::FIELDS_SUBSONIC)
      ->orderBy('p.publish_on ASC');

    $query = trim((string) $query);

    if ('' !== $query)
    {
      $like = '%'.addcslashes($query, '%_\\').'%';
      $q->andWhere('(p.track_title LIKE ? OR p.track_author LIKE ?)', array($like, $like));
    }

    if ($offset)
    {
      $q->offset($offset);
    }

    $posts = $q->execute();
    $q->free();

    return $posts;
  }
}
