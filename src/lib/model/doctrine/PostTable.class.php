<?php

class PostTable extends Doctrine_Table
{
  const FIELDS_BASIC = 'p.body, p.track_title, p.track_author, p.track_filename, p.track_md5, p.slug, p.buy_url, u.username';

  /**
   * Regle de visibilite unique. Les noms de colonnes etant identiques en DQL
   * et en SQL, cette expression sert aux deux : au constructeur de requetes
   * Doctrine et aux agregats en SQL brut. Parenthesee : Doctrine parenthese
   * lui-meme ce qu'on lui passe via andWhere(), mais rien ne protege le cote
   * SQL brut si une requete future lui accole un "OR" — deux caracteres
   * suffisent a garder la priorite des operateurs correcte dans les deux cas.
   */
  const WHERE_ONLINE = "(p.is_online = 1 AND p.publish_on <= DATE_ADD(NOW(), INTERVAL 2 HOUR) AND p.slug IS NOT NULL AND p.slug != '')";

  /** Champs necessaires a la serialisation Subsonic. Exclut body (TEXT). */
  const FIELDS_SUBSONIC = 'p.id, p.track_title, p.track_author, p.track_filename, p.track_duration, p.track_size, p.publish_on, p.slug, u.username';

  /**
   * Projection commune aux agregats "mois de publication" (getMonths,
   * getMonth, getMonthsByArtist) : un seul endroit ou la definition d'un
   * pseudo-album peut changer, plutot que trois copies qui divergent en
   * silence. cover_post_id est le plus petit id du mois, utilise pour
   * choisir la pochette ; ce n'est pas forcement le premier post publie
   * chronologiquement (l'id d'insertion et publish_on peuvent diverger).
   */
  const SELECT_MONTH_AGGREGATE = "
      DATE_FORMAT(p.publish_on, '%Y-%m') AS month,
      COUNT(*) AS song_count,
      SUM(p.track_duration) AS duration,
      MIN(p.id) AS cover_post_id,
      MIN(YEAR(p.publish_on)) AS year,
      MIN(p.publish_on) AS created
  ";

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

  /**
   * @param  string|null $contributor
   * @param  int|null    $count
   * @param  string      $fields  fragment DQL brut (ex. self::FIELDS_BASIC,
   *                              self::FIELDS_SUBSONIC). La methode est
   *                              publique : ne jamais y passer de donnee
   *                              venant de la requete cliente, seulement une
   *                              constante de classe.
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

    if ($contributor)
    {
      $q->andWhere('u.username = ?', (string)$contributor);
    }

    // max(0, ...) : un compte negatif ne doit pas se traduire par un
    // ->limit() invalide, juste par l'absence de limite.
    $count = max(0, (int) $count);

    if ($count > 0)
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

    // max(0, ...) : une valeur negative ne doit pas produire un LIMIT
    // invalide (MySQL renverrait une erreur de syntaxe, donc un 500 sur une
    // surface publique) ; elle degrade proprement vers "pas de limite".
    $limit = max(0, (int) $limit);
    $offset = max(0, (int) $offset);

    $sql = 'SELECT '.self::SELECT_MONTH_AGGREGATE.'
      FROM post p
      WHERE '.self::WHERE_ONLINE.'
      GROUP BY month
      ORDER BY month '.$order;

    // Un offset sans limite reste un besoin valide (page suivante d'une
    // liste non bornee) : MySQL n'accepte pas OFFSET sans LIMIT, donc on
    // pose une limite tres large plutot que de perdre l'offset silencieusement.
    if ($limit > 0 || $offset > 0)
    {
      $sql .= sprintf(' LIMIT %d', $limit > 0 ? $limit : PHP_INT_MAX);

      if ($offset > 0)
      {
        $sql .= sprintf(' OFFSET %d', $offset);
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
    $sql = 'SELECT '.self::SELECT_MONTH_AGGREGATE."
      FROM post p
      WHERE ".self::WHERE_ONLINE." AND DATE_FORMAT(p.publish_on, '%Y-%m') = ?
      GROUP BY month";

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
    $sql = "SELECT
        p.track_author,
        COUNT(DISTINCT DATE_FORMAT(p.publish_on, '%Y-%m')) AS album_count
      FROM post p
      WHERE ".self::WHERE_ONLINE."
        AND p.track_author IS NOT NULL AND TRIM(p.track_author) <> ''";

    // Un auteur vide ne rend pas le post invisible — le morceau reste
    // parfaitement ecoutable — mais il ne constitue pas un artiste
    // adressable : SubsonicId::forArtist('') produit l'identifiant « ar- »,
    // que parseArtist() refuse a juste titre. Sans ce filtre, getArtists
    // exposait une entree fantome, sans nom, qui repondait 70 des qu'un
    // client l'ouvrait. La production compte 12 posts dans ce cas.
    // Le filtre est donc ici, et non dans WHERE_ONLINE.

    $params = array();

    // null !== $like, et pas juste "if ($like)" : '0' est une entree
    // client parfaitement valide et pourtant falsy en PHP. Avec un simple
    // if(), getDistinctArtists('0') ignorait le filtre et rendait tous les
    // artistes.
    if (null !== $like)
    {
      $sql .= ' AND p.track_author LIKE ?';
      // Echappe les metacaracteres LIKE de l'entree elle-meme (comme dans
      // searchSongs()) : sans ca, un "_" ou un "%" saisi par un client de
      // cette API publique non authentifiee se comporte comme un
      // joker, et transforme une recherche precise en scan complet.
      $params[] = '%'.addcslashes($like, '%_\\').'%';
    }

    $sql .= ' GROUP BY p.track_author ORDER BY p.track_author ASC';

    // Cf. le commentaire dans getMonths() : LIMIT/OFFSET s'interpolent en
    // entiers deja bornes a 0, pas en placeholders lies.
    $limit = max(0, (int) $limit);
    $offset = max(0, (int) $offset);

    if ($limit > 0 || $offset > 0)
    {
      $sql .= sprintf(' LIMIT %d', $limit > 0 ? $limit : PHP_INT_MAX);

      if ($offset > 0)
      {
        $sql .= sprintf(' OFFSET %d', $offset);
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
    $sql = 'SELECT '.self::SELECT_MONTH_AGGREGATE.'
      FROM post p
      WHERE '.self::WHERE_ONLINE.' AND p.track_author = ?
      GROUP BY month
      ORDER BY month DESC';

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
    // COALESCE : un contributeur sans ligne user_profile (aucun profil
    // rempli) ne doit pas se retrouver avec un display_name NULL cote
    // Subsonic (playlist sans nom) — on retombe sur son username.
    $sql = 'SELECT
        u.username,
        COALESCE(up.display_name, u.username) AS display_name,
        COUNT(*) AS song_count,
        SUM(p.track_duration) AS duration,
        MIN(p.publish_on) AS created
      FROM post p
      INNER JOIN sf_guard_user u ON u.id = p.contributor_id
      LEFT JOIN user_profile up ON up.user_id = u.id
      WHERE '.self::WHERE_ONLINE.'
      GROUP BY u.id, u.username, up.display_name
      ORDER BY u.username ASC';

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
   * Une requete vide renvoie l'ensemble (pagine) des morceaux visibles, du
   * plus recent au plus ancien (ordre herite de buildOnlinePostsQuery,
   * volontairement conserve : c'est l'ordre attendu d'une recherche, a la
   * difference de getPostsByMonth/getPostsByArtist qui rendent un ordre de
   * lecture d'album).
   *
   * @param  string   $query
   * @param  int      $limit
   * @param  int      $offset
   * @return Doctrine_Collection
   */
  public function searchSongs($query, $limit = 20, $offset = 0)
  {
    $q = $this->buildOnlinePostsQuery(null, $limit, self::FIELDS_SUBSONIC);

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
