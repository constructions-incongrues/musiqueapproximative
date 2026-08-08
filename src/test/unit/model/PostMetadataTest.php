<?php

/**
 * Post::getTrackPath() / Post::fillTrackMetadata() / Post::preSave().
 *
 * web/tracks/ est gitignore et vide dans ce conteneur (l'audio ne vit que
 * sur l'hote de production) : impossible de tester contre de vrais
 * fichiers. On fabrique donc de vrais MP3 minimalistes a la volee (quelques
 * frames MPEG1 Layer III CBR, entete valide, corps a zero) et on pointe
 * sf_web_dir vers un dossier temporaire le temps du test.
 *
 * new Post() a besoin d'une connexion Doctrine ouverte (l'introspection de
 * table la reclame des le constructeur), d'ou le bootstrap "database" —
 * mais aucun de ces tests n'ecrit en base : tout reste en memoire, aucun
 * ->save() n'est appele.
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(15);

/**
 * Construit un flux MP3 (MPEG1 Layer III, 128 kbps, 44100 Hz, mono, sans
 * CRC) valide de $frames frames, chacune remplie de zeros. getID3 lit les
 * en-tetes de frame pour calculer bitrate/duree : le contenu au-dela de
 * l'en-tete n'a pas besoin d'etre un vrai signal decodable.
 */
function post_metadata_build_mp3($frames, $bitrateKbps = 128, $sampleRate = 44100)
{
  $header = "\xFF\xFB\x90\xC0";
  $frameSize = (int) floor(144 * $bitrateKbps * 1000 / $sampleRate);

  return str_repeat($header.str_repeat("\x00", $frameSize - 4), $frames);
}

// Dossier temporaire servant de sf_web_dir : getTrackPath() y ajoute
// toujours /tracks/, donc les fixtures vont dans $webDir/tracks/.
$webDir = sys_get_temp_dir().'/musiqueapproximative_post_metadata_'.uniqid();
$tracksDir = $webDir.'/tracks';
mkdir($tracksDir, 0777, true);

$originalWebDir = sfConfig::get('sf_web_dir');
sfConfig::set('sf_web_dir', $webDir);

// 115 frames -> 115 * 1152 / 44100 = 3.0027s, arrondi a 3.
$validMp3Frames = 115;
$validMp3Bytes = post_metadata_build_mp3($validMp3Frames);
$validMp3Path = $tracksDir.'/valid.mp3';
file_put_contents($validMp3Path, $validMp3Bytes);
$validMp3Size = filesize($validMp3Path);

// Contenu illisible par getID3 comme piste audio, mais bien un fichier
// lisible et non vide : sert a verifier la degradation (taille oui, duree
// non) plutot qu'un cas d'erreur.
$junkPath = $tracksDir.'/junk.mp3';
file_put_contents($junkPath, str_repeat('ceci n\'est pas un mp3, juste du texte de remplissage. ', 20));
$junkSize = filesize($junkPath);

try {
  $t->diag('fillTrackMetadata() sur un fichier lisible');

  $post = new Post();
  $post->track_filename = 'valid.mp3';

  $t->is($post->getTrackPath(), $tracksDir.'/valid.mp3', '->getTrackPath() renvoie sf_web_dir/tracks/<filename>');

  $changed = $post->fillTrackMetadata();

  $t->ok($changed, '->fillTrackMetadata() signale un changement sur un fichier lisible jamais analyse');
  $t->is($post->track_size, $validMp3Size, '->fillTrackMetadata() renseigne track_size via filesize()');
  $t->is($post->track_duration, 3, '->fillTrackMetadata() renseigne track_duration via getID3, arrondi a la seconde');

  $t->diag('fillTrackMetadata() sur un fichier introuvable');

  $missingPost = new Post();
  $missingPost->track_filename = 'ne-existe-pas.mp3';

  $changed = $missingPost->fillTrackMetadata();

  $t->ok(!$changed, '->fillTrackMetadata() ne signale aucun changement quand le fichier est introuvable');
  $t->is($missingPost->track_size, null, '->fillTrackMetadata() laisse track_size a NULL quand le fichier est introuvable');
  $t->is($missingPost->track_duration, null, '->fillTrackMetadata() laisse track_duration a NULL quand le fichier est introuvable');

  $t->diag('fillTrackMetadata() respecte les colonnes deja renseignees, sauf $force');

  $sentinelPost = new Post();
  $sentinelPost->track_filename = 'valid.mp3';
  $sentinelPost->track_size = 999999;
  $sentinelPost->track_duration = 999999;

  $changed = $sentinelPost->fillTrackMetadata();

  $t->ok(!$changed, '->fillTrackMetadata() ne touche a rien quand track_size et track_duration sont deja renseignes');
  $t->is($sentinelPost->track_size, 999999, '->fillTrackMetadata() laisse track_size intact sans $force');
  $t->is($sentinelPost->track_duration, 999999, '->fillTrackMetadata() laisse track_duration intact sans $force');

  $changed = $sentinelPost->fillTrackMetadata(true);

  $t->ok($changed, '->fillTrackMetadata(true) recalcule et signale un changement malgre des colonnes deja renseignees');
  $t->is($sentinelPost->track_size, $validMp3Size, '->fillTrackMetadata(true) recalcule track_size a la vraie valeur');
  $t->is($sentinelPost->track_duration, 3, '->fillTrackMetadata(true) recalcule track_duration a la vraie valeur');

  $t->diag('fillTrackMetadata() degrade proprement quand getID3 ne reconnait pas le flux audio');

  $junkPost = new Post();
  $junkPost->track_filename = 'junk.mp3';

  $changed = $junkPost->fillTrackMetadata();

  $t->is($junkPost->track_size, $junkSize, '->fillTrackMetadata() renseigne track_size meme si getID3 ne reconnait pas le flux');
  $t->is($junkPost->track_duration, null, '->fillTrackMetadata() laisse track_duration a NULL : degradation correcte, pas un echec');
} finally {
  sfConfig::set('sf_web_dir', $originalWebDir);
  unlink($validMp3Path);
  unlink($junkPath);
  rmdir($tracksDir);
  rmdir($webDir);
}
