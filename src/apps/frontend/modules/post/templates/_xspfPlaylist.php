<?php
/**
 * Produit un document XSPF à partir d'une liste de morceaux.
 *
 * Partagé par `listSuccess.xspf.php` et `showSuccess.xspf.php`, un morceau isolé
 * étant servi comme une playlist d'un seul élément — à l'image de ce que font
 * déjà les formats json et max.
 *
 * Aucune dépendance : ni PEAR `File_XSPF`, absente de l'image et dont le `require`
 * échouait fatalement, ni `DOMDocument`, qui l'a remplacée un temps et qu'aucun
 * autre fichier du dépôt n'utilise — `composer.json` ne déclare aucune extension.
 * Un document XSPF tient en quelques éléments ; l'écrire directement supprime la
 * question.
 *
 * Un partiel symfony 1 est hermétique : son porteur d'attributs ne contient que
 * les variables qu'on lui passe. Ni `$sf_request`, ni `$sf_data`, ni `$sf_context`
 * n'y sont disponibles — voir `get_partial()`, qui n'appelle que `setPartialVars()`.
 * D'où `$baseUrl`, calculé par l'appelant.
 *
 * @var array  $posts   Morceaux bruts, hors décorateur d'échappement
 * @var string $title   Titre de la playlist
 * @var string $baseUrl Préfixe absolu du site, sans barre oblique finale
 */

/**
 * Échappe une valeur pour un nœud texte XML.
 *
 * ENT_XML1 échappe les cinq entités du XML sans introduire d'entités HTML, que
 * le XSPF ne connaît pas.
 */
$xspfEscape = function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
};

$xspfTitle = '';
$xspfTracks = '';
$xspfFailure = null;

// Un format déclaré doit aboutir : il ne peut ni échouer, ni servir un corps vide.
// Le document est donc assemblé après coup, de sorte qu'une défaillance en cours
// de route produise une playlist bien formée et non un 500 muet — c'est ce dernier
// qui a caché deux pannes successives de ce format.
try {
  $xspfTitle = $xspfEscape($title);

  foreach ($posts as $post) {
    $fields = array(
      'location'   => sprintf('%s/tracks/%s', $baseUrl, rawurlencode($post->track_filename)),
      'creator'    => $post->track_author,
      'title'      => $post->track_title,
      'annotation' => $post->body,
      'info'       => url_for('@post_show?slug=' . $post->slug, true),
    );

    $xspfTracks .= '    <track>' . "\n";
    foreach ($fields as $name => $value) {
      $xspfTracks .= '      <' . $name . '>' . $xspfEscape($value) . '</' . $name . '>' . "\n";
    }
    $xspfTracks .= '    </track>' . "\n";
  }
} catch (Throwable $exception) {
  $xspfFailure = sprintf('%s: %s', get_class($exception), $exception->getMessage());
  error_log('[xspf] ' . $xspfFailure);
}

// XSPF attend une date xsd:dateTime, et non un horodatage Unix.
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<playlist version="1" xmlns="http://xspf.org/ns/0/">' . "\n";
echo '  <title>' . $xspfTitle . '</title>' . "\n";
echo '  <date>' . date('c') . '</date>' . "\n";
echo '  <trackList>' . "\n";
echo $xspfTracks;
echo '  </trackList>' . "\n";

if ($xspfFailure !== null) {
  echo '  <!-- playlist incomplète : ' . $xspfEscape($xspfFailure) . ' -->' . "\n";
}

echo '</playlist>' . "\n";
