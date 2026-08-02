<?php
/**
 * Produit un document XSPF à partir d'une liste de morceaux.
 *
 * Partagé par `listSuccess.xspf.php` et `showSuccess.xspf.php`, un morceau isolé
 * étant servi comme une playlist d'un seul élément — à l'image de ce que font
 * déjà les formats json et max.
 *
 * Remplace la bibliothèque PEAR `File_XSPF`, absente de l'image Docker : son
 * `require` échouait fatalement, et le format répondait 500 avec un corps vide.
 * `DOMDocument` assure l'échappement que la bibliothèque assurait.
 *
 * @var array  $posts Morceaux bruts, hors décorateur d'échappement
 * @var string $title Titre de la playlist
 */

$document = new DOMDocument('1.0', 'utf-8');
$document->formatOutput = true;

$playlist = $document->createElementNS('http://xspf.org/ns/0/', 'playlist');
$playlist->setAttribute('version', '1');
$document->appendChild($playlist);

$playlist->appendChild($document->createElement('title'))
  ->appendChild($document->createTextNode($title));

// XSPF attend une date xsd:dateTime, et non un horodatage Unix.
$playlist->appendChild($document->createElement('date'))
  ->appendChild($document->createTextNode(date('c')));

$trackList = $document->createElement('trackList');
$playlist->appendChild($trackList);

foreach ($posts as $post) {
  $track = $document->createElement('track');
  $trackList->appendChild($track);

  $location = sprintf(
    '%s%s/tracks/%s',
    $sf_request->getUriPrefix(),
    $sf_request->getRelativeUrlRoot(),
    rawurlencode($post->track_filename)
  );

  // createTextNode échappe les valeurs : guillemets, esperluettes et chevrons
  // d'un titre ou d'un corps de post ne peuvent pas casser le document.
  $fields = array(
    'location'   => $location,
    'creator'    => $post->track_author,
    'title'      => $post->track_title,
    'annotation' => $post->body,
    'info'       => url_for('@post_show?slug=' . $post->slug, true),
  );

  foreach ($fields as $name => $value) {
    $track->appendChild($document->createElement($name))
      ->appendChild($document->createTextNode((string) $value));
  }
}

echo $document->saveXML();
