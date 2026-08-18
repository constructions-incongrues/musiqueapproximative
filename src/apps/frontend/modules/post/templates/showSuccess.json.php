<?php
// @see http://jsonapi.org/format/#url-based-json-api
//
// `$post` est demande AU BRUT, et le document rendu par toJson() est servi tel
// quel, sans retouche.
//
// Ce gabarit lisait `$post` echappe — Symfony enveloppe les variables de vue
// dans sfOutputEscaper, et l'echappement s'appliquait a la chaine JSON deja
// encodee, qui arrivait avec « &quot; » partout. `html_entity_decode()`
// rattrapait cela apres coup.
//
// Le rattrapage etait le defaut. Applique a un document deja encode, il ne
// distingue pas l'entite ajoutee par l'echappement de celle que Markdown a
// produite : il decode les deux. Markdown rend un antislash du corps en
// « &#92; », qui redevenait un « \ » nu au milieu d'une chaine JSON —
// echappement invalide. Deux morceaux sur 8 098 rendaient ainsi
// /posts?format=json inanalysable dans son entier. Un « &quot; » aurait coupe
// la chaine de la meme facon.
//
// Prendre la valeur brute supprime la cause : il n'y a plus rien a defaire.
// listSuccess.json.php lisait deja `posts` au brut et n'avait donc que le
// dommage ; /post/md5/:md5sum n'a jamais decode. Les trois routes JSON servent
// maintenant la meme chose.
$json = $sf_data->getRaw('post')->toJson(
  $sf_data->getRaw('sf_request'),
  $sf_data->getRaw('sf_context'),
  $sf_data->getRaw('post_previous'),
  $sf_data->getRaw('post_next')
);

// Even single ressources are displayed as lists
echo sprintf('{ "posts": [%s] }', $json);
