<?php

/**
 * Script de routage pour le serveur integre de PHP (`php -S`), en developpement
 * uniquement.
 *
 * Sans lui, `php -S` considere tout chemin contenant un point comme une demande
 * de fichier statique et repond 404 sans jamais atteindre Symfony. C'est
 * precisement la forme canonique de l'API Subsonic — /rest/ping.view — qui
 * devient alors injoignable en local, alors qu'elle fonctionne en production.
 *
 * Reproduit la regle de web/.htaccess : si la cible est un fichier reel, on la
 * sert telle quelle ; sinon on passe la main au controleur frontal.
 */

// Ce fichier n'a de sens que comme script de routage de `php -S`. En
// production il est servi par Apache comme n'importe quel fichier reel — la
// regle de reecriture ne s'applique qu'aux chemins qui n'existent pas — et
// offrirait donc un second point d'entree non voulu vers le controleur
// frontal. On refuse tout ce qui ne vient pas du serveur integre.
if ('cli-server' !== PHP_SAPI) {
  http_response_code(404);

  return;
}

// Le chemin arrive percent-encode : « Fete avec espace.mp3 » devient
// /tracks/Fete%20avec%20espace.mp3, qu'aucun is_file() ne reconnait. Tous les
// morceaux du catalogue ont une espace dans leur nom — sans ce decodage, aucun
// n'est ecoutable en local alors que la production les sert.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__.$path;

if ('/' !== $path && is_file($file)) {
  return false;
}

// Quand un script de routage prend la main, php -S met le chemin demande dans
// SCRIPT_NAME. Symfony 1 en deduit la racine de l'application et croirait donc
// que /rest/ping.view en est la base, ce qui fait echouer tout le routage.
//
// Le controleur frontal n'est index.php que par defaut : /admin_prod.php/post
// designe le controleur de l'admin suivi d'un PATH_INFO. Sans ce decoupage,
// tout partait dans index.php et l'admin repondait 404 en local alors qu'elle
// fonctionne en production.
$script = '/index.php';

if (preg_match('#^(/[^/]+\.php)(/.*)?$#', $path, $matches) && is_file(__DIR__.$matches[1])) {
  $script              = $matches[1];
  $_SERVER['PATH_INFO'] = isset($matches[2]) ? $matches[2] : '';
}

$_SERVER['SCRIPT_NAME']     = $script;
$_SERVER['SCRIPT_FILENAME'] = __DIR__.$script;
$_SERVER['PHP_SELF']        = $script;

require __DIR__.$script;
