# Conception

## Contexte

Trois représentations machine d'une même liste doivent devenir paginables. Elles n'ont pas
les mêmes capacités : l'une est un objet JSON qu'on peut étendre, l'autre un format XML
normalisé qui prévoit ses propres points d'extension, la troisième du texte brut destiné à
un patch Max/MSP.

La question de conception n'est donc pas *comment borner* — `buildOnlinePostsQuery` accepte
déjà un compte — mais **qu'est-ce qui porte l'information de pagination, dans chaque
format, sans casser ce qui le lit.**

## Ce que chaque format peut porter

| format | corps extensible ? | ce qu'il permet |
| --- | --- | --- |
| `json` | oui | l'enveloppe `{ "posts": […] }` accepte des clés voisines |
| `xspf` | oui, de façon normalisée | XSPF 1.0 prévoit `<link rel="…" href="…"/>` au niveau playlist |
| `max` | **non** | texte brut lu par un patch ; toute ligne ajoutée peut casser son analyseur |

Le `max` décide de la solution : **aucune information de pagination ne peut être ajoutée à
son corps sans risque.** Ce que ce format ne peut pas porter dans son corps, aucun des trois
ne peut le porter *uniquement* dans son corps sans devenir incohérent d'un format à l'autre.

## Décision : un en-tête HTTP, complété dans le corps quand le format s'y prête

**L'en-tête `Link` (RFC 8288) porte la navigation pour les trois formats.** Il est
format-agnostique, standard, lisible par un client HTTP quelconque, et ne touche à aucun
corps — donc le `max` devient paginable sans être modifié d'un octet.

Les relations servies sont `next`, `prev`, `first` et `last`, chacune omise quand elle n'a
pas de sens : pas de `next` sur la dernière portion, pas de `prev` sur la première.

**Le JSON porte en plus les nombres dans son corps.** Un consommateur JSON qui doit lire un
en-tête pour savoir combien de morceaux existent travaille contre son format. L'enveloppe
gagne donc le total, la portion demandée et le rang de départ, à côté de `posts`.

**Le XSPF porte en plus ses liens en format.** XSPF 1.0 prévoit `<link>` au niveau de la
playlist ; s'en servir est plus juste que d'obliger un lecteur de playlist à inspecter des
en-têtes HTTP qu'il ne regarde pas.

Le `max` ne reçoit rien dans son corps. C'est délibéré, et c'est ce que cette conception
protège.

## Contrainte du socle, à documenter plutôt qu'à subir

Connaître le total impose **une requête de comptage en plus** de la requête de liste.
Doctrine 1 n'offre pas de compte gratuit sur une requête bornée : `count()` sur un
`Doctrine_Query` exécute son propre `SELECT COUNT`.

C'est le prix de la pagination, et il est faible devant ce qu'elle économise — un
`COUNT(*)` sur `post` avec l'index `online_publish` contre les 8,4 Mo qu'on cesse de
sérialiser. Mais c'est une requête de plus par demande de liste, et il vaut mieux l'écrire
que la découvrir.

## Ce que cette conception écarte

**Une enveloppe conforme JSON:API**, avec ses `meta` et ses `links` normalisés. Le contrat
déclare explicitement que les formes ne sont pas conformes et que la migration a été
écartée ; adopter la convention pour ce seul champ donnerait à croire l'inverse.

**Une pagination par curseur.** Elle corrigerait le décalage de fenêtre quand un morceau est
publié pendant qu'on pagine, mais demande un autre modèle. Le site publie une fois par
jour : le décalage est d'un morceau. Le noter suffit ; le corriger serait disproportionné.

**Un plafond sur `count`.** Le laisser ouvert préserve l'usage de qui dépend aujourd'hui de
la liste entière — il lui reste un moyen explicite de l'obtenir. Le borner est une décision
à prendre sur des mesures d'usage qu'on n'a pas.


## Ce que la revue d'ingénierie a changé — 2026-08-18

**La conception ci-dessus est suspendue.** La revue a établi que la prémisse du change n'est
pas vérifiée : `max` sert moins d'octets que `xspf` et met cinq fois plus de temps. Le coût
n'est probablement pas le volume mais une hydratation manquante — `UserProfile` n'est joint
nulle part, alors que `toJson()` le lit. Diagnostic d'abord, décision ensuite.

Trois points de cette conception sont par ailleurs corrigés par la revue :

- **Un plafond sur `count` était déclaré hors périmètre. Il entre au périmètre.** Le cache
  est actif, dure 24 h et varie sur la query string ; `count` et `offset` libres produisent
  un espace de cache non borné. Et c'est **`offset`** le vrai multiplicateur, pas `count` :
  le plafonner seul serait décoratif.
- **« Ne rien toucher au `max` » devient « ne rien AJOUTER au `max` ».** Son corps porte déjà
  deux valeurs que le bornage ferait mentir : le champ total, et le rang qui repart de zéro à
  chaque portion.
- **Le modèle de coût ne vaut que pour une branche.** « Connaître le total impose une requête
  de comptage » est faux sur le chemin `?q=`, où `search()` rend un tableau déjà en mémoire.
