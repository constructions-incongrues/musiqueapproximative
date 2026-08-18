## Why

Relevé sur la production le 18 août 2026 :

```
curl -s "https://www.musiqueapproximative.net/posts?format=json" -o /tmp/p.json
python3 -c "import json; json.load(open('/tmp/p.json'))"
# json.decoder.JSONDecodeError: Invalid \escape: line 1 column 3705351
```

**Le catalogue JSON complet — 8 098 morceaux, 8,4 Mo — n'est analysable par aucun
consommateur.** Deux morceaux le rendent invalide dans son entier : celui de
`hyacinthe-retour-d-emeute-piege` porte `\ Chaque époque a son petit diable`, où
l'antislash n'est pas échappé.

La chaîne complète, mesurée :

1. le corps Markdown du morceau contient un antislash ;
2. Markdown le rend en HTML sous la forme de l'entité `&#92;` ;
3. `json_encode()` laisse cette entité telle quelle — elle est en ASCII, il n'a rien à
   échapper ;
4. les deux gabarits JSON repassaient le document **déjà encodé** dans
   `html_entity_decode()`, qui ramenait `&#92;` à un `\` nu au milieu d'une chaîne JSON.

Un `\` suivi d'une espace n'est pas une séquence d'échappement JSON valide. Le document
entier tombe.

Reste la question de savoir pourquoi ce décodage était là. Il ne visait pas Markdown : il
compensait l'**échappement de vue**. `showSuccess.json.php` lisait `$post` enveloppé par
le mécanisme d'échappement des variables de gabarit, si bien que la chaîne JSON déjà
encodée lui arrivait avec `&quot;` partout ; le décodage la remettait d'aplomb.
`listSuccess.json.php`, lui, lit déjà la valeur brute : il n'avait aucun échappement à
défaire, seulement le dommage. Le correctif est donc de demander la valeur brute des deux
côtés, et de ne plus rien retoucher après encodage.

Pourquoi la suite ne l'a pas vu : **aucune fixture ne porte d'antislash**, et les tests
JSON lisaient des champs sans jamais affirmer que le document se laissait lire. Le test de
contrat OpenAPI vérifiait le statut, le type de contenu et la présence des clés de premier
niveau — trois propriétés qu'un document invalide satisfait sans peine, puisque
`json_decode()` y rendait `null` et que seule l'absence de clés était rapportée.

La troisième route JSON, `/post/md5/:md5sum`, n'a jamais décodé : elle sert déjà un
document valide. Les trois s'alignent sur elle.

## What Changes

- `showSuccess.json.php` lit le morceau **au brut** et sert le document rendu tel quel :
  plus d'échappement de vue à défaire, donc plus de `html_entity_decode()`.
- `listSuccess.json.php` perd son `html_entity_decode()`, qui n'y compensait rien.
- Le commentaire d'`executeMd5` qui décrivait le comportement des deux gabarits est mis à
  jour : il décrivait un écart qui n'existe plus.
- **Contrat public concerné.** `body.html` porte désormais les entités que Markdown
  produit — `&amp;`, `&lt;`, `&#92;` — là où il livrait `&`, `<` et `\` nus. C'est du HTML
  valide au lieu de HTML cassé, et c'est la seule différence visible : les autres champs
  viennent de la base telle quelle et ne portent pas d'entités.
- Deux garde-fous de test :
  - `jsonEchappementTest.php` pose un morceau dont le corps contient un antislash, une
    esperluette et un guillemet — les trois caractères que Markdown transforme en entités —
    et exige que les trois routes JSON servent un document analysable ;
  - `openapiContractTest.php` exige désormais que **toute** réponse que le contrat annonce
    en JSON s'analyse, avant même de regarder ses clés.

## Hors périmètre

- **Les données.** Aucun corps de morceau n'est réécrit. L'antislash de
  `hyacinthe-retour-d-emeute-piege` est ce que son auteur a écrit ; c'est au site de le
  servir correctement, pas au corpus de s'adapter.
- **La conformité JSON:API.** Les réponses ne sont toujours pas conformes à la spécification
  1.0, et `docs/API_JSON_API_TARGET.md` reste une archive. Ce changement rend le document
  analysable, il ne change pas sa forme.
- **`html_entity_decode()` ailleurs.** Les gabarits `max` et la couche OpenGraph du layout
  en appliquent aussi, sur des formats qui ne sont pas du JSON et où il ne casse rien de
  mesuré. Les toucher serait corriger « tant qu'on y est ».
- **La concaténation `sprintf('{ "posts": [%s] }', …)`.** Elle assemble des documents
  valides et en produit un valide. Elle est laide, elle n'est pas fautive.
- **Le défaut d'environnement** qui oblige chaque test JSON à une requête de chauffe
  (PHP-Markdown émet des `E_DEPRECATED` qui atterrissent dans le corps de la première
  réponse). Il est déjà consigné ; le nouveau test s'en protège comme les autres.
