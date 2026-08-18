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
 * D'où `$trackScheme`, calculé par l'appelant : le partiel ne peut pas savoir seul
 * si la requête est sécurisée.
 *
 * Les variables d'un partiel arrivent en revanche échappées : `$posts` sous forme
 * de `sfOutputEscaperArrayDecorator`, `$title` en chaîne déjà passée par
 * `htmlspecialchars`. Les réécrire ici en HTML donnait un double échappement —
 * `&quot;` ressortait en `&amp;quot;` — d'où le retour aux valeurs brutes avant de
 * les échapper une fois, pour le XML cette fois.
 *
 * @var array  $posts   Morceaux, sous décorateur d'échappement
 * @var string $title   Titre de la playlist, échappé pour le HTML
 * @var string $trackScheme Schéma de la requête, « http » ou « https »
 */

$posts = sfOutputEscaper::unescape($posts);
$title = sfOutputEscaper::unescape($title);

/**
 * Échappe une valeur pour un nœud texte XML.
 *
 * ENT_XML1 échappe les cinq entités du XML sans introduire d'entités HTML, que
 * le XSPF ne connaît pas.
 *
 * Les caractères de contrôle C0 sont retirés avant : XML 1.0 les interdit dans un
 * document, y compris sous forme d'entité numérique. Certains corps de morceaux en
 * contiennent — reliquats d'un import binaire — et un seul suffit à rendre toute la
 * playlist illisible. Le retrait porte sur des octets isolés, jamais sur une amorce
 * de séquence UTF-8, d'où l'absence de modificateur `u`.
 */
$xspfEscape = function ($value) {
  $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $value);

  return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
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
      // L'adresse est construite par le modèle, comme dans toutes les autres
      // représentations : elle designe l'emplacement configure pour les fichiers,
      // qui peut differer de l'hote du site, et encode le nom de fichier.
      'location'   => $post->getTrackUrl($trackScheme),
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
