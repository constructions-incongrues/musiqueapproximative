-- Jeu de donnees minimal pour les tests de l'API Subsonic.
--
-- Concu pour rendre ecrivable l'assertion la plus importante de la branche :
-- un contenu non publiquement visible n'est jamais atteignable par l'API.
-- D'ou les trois posts invisibles ci-dessous, chacun pour une raison
-- differente (hors ligne, date dans le futur, slug vide).
--
-- En SQL et non en YAML Doctrine : `doctrine:data-load` est inoperant sur ce
-- projet. Il parse tout le repertoire data/fixtures/, ou les deux dumps SQL de
-- production le font echouer (« _hasNaturalNestedSetFormat() must be of the
-- type array, string given ») ; isole dans son propre repertoire il annonce
-- « Data was successfully loaded » et n'ecrit rien. Le SQL, lui, est
-- deterministe et verifiable.
--
-- Chargement :
--   docker-compose exec -T db mysql -uroot -proot musiqueapproximative_test \
--     < src/data/fixtures/subsonic.sql
--
-- Ou, depuis un test Lime, via src/test/bootstrap/fixtures.php.

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE post_index;
TRUNCATE TABLE post;
TRUNCATE TABLE user_profile;
TRUNCATE TABLE sf_guard_user;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO sf_guard_user (id, username, algorithm, salt, password, is_active, is_super_admin, created_at, updated_at) VALUES
  (1, 'alice', 'sha1', 'sel', 'motdepasse', 1, 0, '2024-01-01 00:00:00', '2024-01-01 00:00:00'),
  (2, 'bob',   'sha1', 'sel', 'motdepasse', 1, 0, '2024-01-01 00:00:00', '2024-01-01 00:00:00');

INSERT INTO user_profile (id, user_id, display_name, email, website_url) VALUES
  (1, 1, 'Alice', 'alice@example.net', 'https://alice.example.net'),
  (2, 2, 'Bob',   'bob@example.net',   'https://bob.example.net');

-- --- 2024-06 : deux morceaux visibles, durees renseignees -------------------
-- Somme des durees du mois = 245 + 180 = 425, assertable telle quelle.
INSERT INTO post (id, body, track_title, track_author, track_filename, track_md5, track_duration, track_size, publish_on, is_online, contributor_id, slug, created_at, updated_at) VALUES
  (1, 'Premier morceau de juin', 'Rock & Roll', 'Sigur Rós', 'un titre.mp3', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 245, 5900000, '2024-06-01 12:00:00', 1, 1, 'sigur-ros-rock-roll', '2024-06-01 12:00:00', '2024-06-01 12:00:00'),
  (2, 'Deuxieme morceau de juin', 'A < B', 'AC/DC', 'café & the beat.mp3', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 180, 4300000, '2024-06-15 12:00:00', 1, 2, 'acdc-a-b', '2024-06-15 12:00:00', '2024-06-15 12:00:00');

-- --- 2024-05 : un morceau visible, sans duree -------------------------------
-- Sigur Rós apparait donc dans deux mois : albumCount doit valoir 2.
-- Duree et taille nulles : c'est le cas ou l'attribut doit etre omis.
INSERT INTO post (id, body, track_title, track_author, track_filename, track_md5, track_duration, track_size, publish_on, is_online, contributor_id, slug, created_at, updated_at) VALUES
  (3, 'Un morceau de mai', 'Ancien', 'Sigur Rós', 'ancien.mp3', 'cccccccccccccccccccccccccccccccc', NULL, NULL, '2024-05-10 12:00:00', 1, 1, 'sigur-ros-ancien', '2024-05-10 12:00:00', '2024-05-10 12:00:00');

-- --- Les trois invisibles ---------------------------------------------------
-- Aucun ne doit apparaitre dans une reponse Subsonic, quelle qu'elle soit.
INSERT INTO post (id, body, track_title, track_author, track_filename, track_md5, track_duration, track_size, publish_on, is_online, contributor_id, slug, created_at, updated_at) VALUES
  (4, 'Ne doit jamais sortir', 'Retiré', 'Fantôme', 'retire.mp3', 'dddddddddddddddddddddddddddddddd', 100, 1000000, '2024-06-02 12:00:00', 0, 1, 'fantome-retire', '2024-06-02 12:00:00', '2024-06-02 12:00:00'),
  (5, 'Programme, pas encore publie', 'Demain', 'Fantôme', 'demain.mp3', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 100, 1000000, '2099-01-01 12:00:00', 1, 1, 'fantome-demain', '2024-06-01 12:00:00', '2024-06-01 12:00:00'),
  (6, 'Slug vide', 'Sans slug', 'Fantôme', 'sans-slug.mp3', 'ffffffffffffffffffffffffffffffff', 100, 1000000, '2024-06-03 12:00:00', 1, 1, '', '2024-06-03 12:00:00', '2024-06-03 12:00:00');
