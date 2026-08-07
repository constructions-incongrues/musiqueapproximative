<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(7);

sfConfig::set('app_urls_tracks', '//example.net/tracks');

$t->diag('Post::buildTrackUrl()');

$t->is(
  Post::buildTrackUrl('simple.mp3'),
  '//example.net/tracks/simple.mp3',
  '->buildTrackUrl() garde la base telle quelle par defaut'
);

$t->is(
  Post::buildTrackUrl('un titre.mp3'),
  '//example.net/tracks/un%20titre.mp3',
  '->buildTrackUrl() encode un espace en %20 et non en +'
);

$t->is(
  Post::buildTrackUrl('café.mp3'),
  '//example.net/tracks/caf%C3%A9.mp3',
  '->buildTrackUrl() encode les accents'
);

$t->is(
  Post::buildTrackUrl('rock & roll.mp3'),
  '//example.net/tracks/rock%20%26%20roll.mp3',
  '->buildTrackUrl() encode une esperluette'
);

$t->is(
  Post::buildTrackUrl('simple.mp3', 'https'),
  'https://example.net/tracks/simple.mp3',
  '->buildTrackUrl() qualifie le schema quand on le demande'
);

$t->is(
  Post::buildTrackUrl('simple.mp3', 'http'),
  'http://example.net/tracks/simple.mp3',
  '->buildTrackUrl() accepte http'
);

sfConfig::set('app_urls_tracks', 'https://cdn.example.net/tracks/');
$t->is(
  Post::buildTrackUrl('simple.mp3', 'https'),
  'https://cdn.example.net/tracks/simple.mp3',
  '->buildTrackUrl() ne double pas le schema ni la barre finale'
);
