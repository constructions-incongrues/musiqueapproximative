-- Conversion de la base en utf8mb4.
--
-- POURQUOI
--
-- La base est en latin1. Tout caractere hors cp1252 saisi par un contributeur y
-- est remplace par « ? » a l'ecriture, definitivement : 81 morceaux ont deja un
-- titre ou un auteur detruit, 37 contributeurs sont concernes, cinq degats
-- datent de 2026. Le site publie quotidiennement.
--
-- CE SCRIPT NE S'EXECUTE PAS TOUT SEUL
--
-- Le deploiement ne lance aucune migration : Plesk tire `main`, un point c'est
-- tout. Ce fichier doit etre lance a la main, par le detenteur des acces.
--
--   mysql -u<user> -p <base> < src/data/migration/utf8mb4.sql
--
-- Voir docs/modules/ROOT/pages/migration-utf8mb4.adoc pour la marche a suivre
-- complete : dump prealable, verifications, et la SECONDE livraison qui pose
-- `encoding: utf8mb4` — a ne poser QU'APRES cette conversion.
--
-- ORDRE DES GESTES
--
-- Convertir les tables d'abord, changer l'encodage de connexion ensuite.
-- L'inverse enverrait de l'utf8mb4 vers des colonnes latin1, c'est-a-dire le
-- mecanisme qui detruit aujourd'hui, en pire.
--
-- REPRISE
--
-- Le script saute les tables deja converties. Une execution interrompue se
-- relance sans dommage.

-- ---------------------------------------------------------------- Controle

DROP PROCEDURE IF EXISTS ma_controle_prealable;

DELIMITER $$

CREATE PROCEDURE ma_controle_prealable()
BEGIN
  DECLARE n INT;
  DECLARE base VARCHAR(64);

  SET base = DATABASE();

  -- 1. Y a-t-il quelque chose a convertir ?
  SELECT COUNT(*) INTO n
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = base
     AND CHARACTER_SET_NAME IS NOT NULL
     AND CHARACTER_SET_NAME <> 'utf8mb4'
     AND TABLE_NAME NOT LIKE 'directus%';

  IF n = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'ARRET : aucune colonne a convertir. La base est deja en utf8mb4, ou ce n est pas la bonne base.';
  END IF;

  -- 2. La table `post` existe-t-elle ? On ne convertit pas une base inconnue.
  SELECT COUNT(*) INTO n
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = base AND TABLE_NAME = 'post';

  IF n = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'ARRET : pas de table `post`. Ce n est pas la base de Musique Approximative.';
  END IF;

  -- 3. LE CONTROLE QUI DECIDE.
  --
  -- `CONVERT TO CHARACTER SET` reinterprete les octets comme du latin1
  -- authentique. Si le corpus portait de l'UTF-8 range dans des colonnes latin1
  -- — un double encodage — la conversion produirait du mojibake DEFINITIF.
  --
  -- Les motifs cherches sont les formes latin1 des sequences UTF-8 les plus
  -- courantes en francais : Ã© pour « é », Ã¨ pour « è », Ã  pour « à ».
  SELECT COUNT(*) INTO n
    FROM post
   WHERE body         LIKE '%Ã©%' OR body         LIKE '%Ã¨%' OR body         LIKE '%Ã %'
      OR track_title  LIKE '%Ã©%' OR track_title  LIKE '%Ã¨%' OR track_title  LIKE '%Ã %'
      OR track_author LIKE '%Ã©%' OR track_author LIKE '%Ã¨%' OR track_author LIKE '%Ã %';

  IF n > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'ARRET : double encodage detecte. Convertir maintenant produirait du mojibake definitif. Ne pas forcer.';
  END IF;

  SELECT 'Controle prealable : passe.' AS etat;
END$$

DELIMITER ;

CALL ma_controle_prealable();

-- ------------------------------------------------------------- Conversion

DROP PROCEDURE IF EXISTS ma_convertir_utf8mb4;

DELIMITER $$

CREATE PROCEDURE ma_convertir_utf8mb4()
BEGIN
  DECLARE fini INT DEFAULT 0;
  DECLARE nom VARCHAR(64);
  DECLARE faites INT DEFAULT 0;

  -- Seules les tables qui ne sont pas deja en utf8mb4 : c'est ce qui rend une
  -- execution interrompue relancable.
  DECLARE curseur CURSOR FOR
    SELECT DISTINCT t.TABLE_NAME
      FROM information_schema.TABLES t
      JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY c
        ON c.COLLATION_NAME = t.TABLE_COLLATION
     WHERE t.TABLE_SCHEMA = DATABASE()
       AND t.TABLE_TYPE = 'BASE TABLE'
       AND t.TABLE_NAME NOT LIKE 'directus%'
       AND c.CHARACTER_SET_NAME <> 'utf8mb4'
     ORDER BY t.TABLE_NAME;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET fini = 1;

  OPEN curseur;

  boucle: LOOP
    FETCH curseur INTO nom;
    IF fini = 1 THEN
      LEAVE boucle;
    END IF;

    SET @sql = CONCAT('ALTER TABLE `', nom, '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET faites = faites + 1;
  END LOOP;

  CLOSE curseur;

  SELECT CONCAT(faites, ' table(s) converties.') AS etat;
END$$

DELIMITER ;

CALL ma_convertir_utf8mb4();

-- La base elle-meme n'est PAS convertie ici : `ALTER DATABASE` n'est pas
-- supporte par le protocole des requetes preparees (erreur 1295), et le nom de
-- la base n'est pas connu du script. La commande est donc affichee a la fin, a
-- lancer separement. Elle ne touche aucune donnee : elle fixe le jeu par defaut
-- des tables qui seraient creees ensuite.

DROP PROCEDURE ma_controle_prealable;
DROP PROCEDURE ma_convertir_utf8mb4;

-- ------------------------------------------------------------ Verification

SELECT
  CONCAT(TABLE_NAME, ' : ', GROUP_CONCAT(DISTINCT CHARACTER_SET_NAME)) AS 'jeu de caracteres par table'
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND CHARACTER_SET_NAME IS NOT NULL
   AND TABLE_NAME NOT LIKE 'directus%'
 GROUP BY TABLE_NAME
 ORDER BY TABLE_NAME;

SELECT CONCAT(
  'ALTER DATABASE `', DATABASE(), '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
) AS 'a lancer separement — ALTER DATABASE ne passe pas en requete preparee';

SELECT 'Reconstruire ensuite l index de recherche : sa collation a change.' AS 'a ne pas oublier';
