# Tâches

`design.md` **est** produit, contrairement au premier jet de ce change. Il ne l'était pas
tant qu'il s'agissait seulement de borner ; rendre les listes *navigables* pose une question
qui ne se tranche pas en écrivant du code — **qu'est-ce qui porte la pagination dans un
format dont le corps ne peut rien porter de plus**. C'est la définition même de l'artefact
que la règle du dépôt réserve aux changements structurants.


## 0. Bloquant — diagnostiquer avant d'implémenter (décision D8)

- [x] 0.1 **Compter les requêtes** produites par `/posts?format=json` et `?format=max`.
  Hypothèse à confirmer ou infirmer : une requête par morceau, ~8 100, faute d'hydratation
  de `UserProfile`.
- [x] 0.2 Essayer une jointure élargie sur `buildOnlinePostsQuery`, remesurer les trois
  formats. **Si le catalogue entier repasse sous quelques secondes, ce change n'a plus
  d'urgence** — la pagination garde sa valeur propre, pas son argument de latence.
  **Résultat : 8 271 requêtes / 7,17 s → 1 requête / 1,08 s.** L'hypothèse est confirmée,
  la correction tient en un `leftJoin` et un `select` explicite, et elle ne rompt rien.
- [x] 0.3 Tranché : **la jointure est corrigée dans un change à part, ce change est gelé.**
  Son argument d'urgence est mesuré faux ; il repartira si un consommateur réclame la
  pagination, pas sur une latence.

## 1. Reprendre la convention plutôt qu'en inventer une

- [ ] 1.1 Constater que `/posts/feed` accepte déjà `count`, avec **50 par défaut**
  (`executeFeed`, `$request->getParameter('count', 50)`). Reprendre ce nom et ce chiffre.
  **Ne pas introduire `limit`** : ce serait une seconde convention pour le même besoin.
- [ ] 1.2 Ajouter `offset`, qui n'a pas de précédent — le flux n'en avait pas besoin.
- [ ] 1.2bis **Plafonner `count` (D3).** Sans plafond, `count` × `offset` produit un espace
  de cache non borné : le cache est actif, dure 24 h et varie sur la query string — vérifié
  en production. Un `count` au-delà du plafond se sert **au plafond**, jamais en erreur.
  Borner aussi `offset` par le total : c'est lui le vrai multiplicateur d'entrées de cache.
- [ ] 1.3 `buildOnlinePostsQuery` accepte déjà un `$count` et se garde d'un compte négatif.
  Étendre ce garde-fou à `offset` plutôt que d'en écrire un second ailleurs.

## 2. Borner les trois formats, et eux seuls

- [ ] 2.1 Dans `executeList`, lire `count` et `offset`, et **ne les appliquer que pour les
  formats machine**. La page HTML reste entière — c'est une décision de l'auteur, pas un
  effet de bord à préserver par hasard.
- [ ] 2.2 Vérifier que le bornage porte sur **la liste demandée** et non sur le catalogue :
  `?c=<contributeur>` et `?q=<recherche>` doivent filtrer d'abord, borner ensuite.
- [ ] 2.3 Une portion qui commence au-delà du dernier morceau se sert **vide, en 200**. Une
  pagination qui se termine n'est pas une erreur.

## 3. Rendre les listes navigables, et pas seulement bornées

- [ ] 3.1 Compter le total. **Une requête de comptage en plus** : Doctrine 1 n'offre pas de
  compte gratuit sur une requête bornée. C'est le prix de la pagination — écrit dans
  `design.md` plutôt que découvert à l'exécution.
- [ ] 3.2 Poser un en-tête `Link` (RFC 8288) avec `next`, `prev`, `first`, `last`, sur **les
  trois formats**. C'est ce qui rend le `max` paginable sans toucher à son corps.
- [ ] 3.3 Omettre `prev` sur la première portion et `next` sur la dernière. Un lien qui ne
  mène nulle part est pire que pas de lien.
- [ ] 3.4 **JSON** : porter aussi le total, la portion et le rang dans l'enveloppe, à côté
  de `posts`. Un consommateur JSON qui doit lire un en-tête pour connaître le total travaille
  contre son format. Le gabarit porte déjà un `// TODO : previous and next post` — l'intention
  est antérieure à cette story.
- [ ] 3.5 **XSPF** : porter les liens en format, par `<link rel="…" href="…"/>` au niveau
  playlist, que XSPF 1.0 prévoit. Un lecteur de playlist ne regarde pas les en-têtes HTTP.
- [ ] 3.5bis **Le champ total du `max` reste le total (D4).** `listSuccess.max.php:3` émet
  déjà `count($posts)` sur chaque ligne. Borné, il vaudrait 50 : **rupture silencieuse**, le
  corps garde sa forme et seul le sens du champ change. Lui passer le total calculé.
- [ ] 3.5ter **Le rang du `max` doit rester global.** Le gabarit émet `$i` depuis 0 ; avec
  `offset=50`, la deuxième portion recommence à 0 et un patch qui concatène deux pages
  obtient des rangs dupliqués, en silence. Le rang est déjà dans le corps et deviendrait
  faux — le corriger n'est pas « ajouter au corps ».
- [ ] 3.6 **`max` : ne rien ajouter à son corps.** C'est du texte brut lu par un patch
  Max/MSP ; toute ligne ajoutée peut casser son analyseur. C'est ce que la conception
  protège, et c'est le point à ne pas « améliorer » en passant.

## 4. Le titre de la playlist doit rester vrai

- [ ] 4.0 **La composition du titre reste chez l'appelant (D2).** `_xspfPlaylist.xspf.php`
  est partagé par `listSuccess.xspf.php` **et** `showSuccess.xspf.php` — un morceau isolé est
  servi comme une playlist d'un élément. Le partiel continue de recevoir `$title` tout fait ;
  le chemin `show` ne doit jamais parler de portion.
- [ ] 4.0bis **Factoriser la description de liste (D5).** Le branchement `c` / `q` / rien
  existe en double — `executeList` pour le titre HTML, `listSuccess.xspf.php` pour celui de
  la playlist. Une méthode répond « de quelle liste s'agit-il », chaque sortie la formule.
  Le précédent bug de ce gabarit — il lisait `contributor` au lieu de `c` — venait de là.
- [ ] 4.0ter **Le titre HTML aussi.** `executeList` compose `'%d résultat(s)'` avec
  `count($posts)` : borné, la page annoncerait « 50 résultat(s) ».
- [ ] 4.1 `formats-de-sortie` spécifie le titre du document XSPF. Quand la liste est
  tronquée, il doit le dire — un titre qui annonce l'ensemble alors que le document n'en
  porte qu'une partie **induit en erreur sans que rien ne le signale**.
- [ ] 4.2 Le cas non tronqué garde son titre actuel, sans mention de portion.

## 5. Amender le contrat

- [ ] 5.1 `/posts` déclare aujourd'hui « **Aucun bornage.** La liste complète est servie […]
  Ni `limit` ni `offset` n'existent à ce jour. » Remplacer par la description exacte du
  bornage, avec le défaut.
- [ ] 5.2 Déclarer `count` et `offset` en paramètres, réutilisables — les mêmes routes de
  liste les acceptent toutes.
- [ ] 5.3 **C'est le seul canal de dépréciation que ce projet possède**, et le contrat le
  dit lui-même. Le diff de ce document est ce qui préviendra un consommateur.

## 6. Vérification

- [ ] 6.1 Étendre `catalogueEtNavigationTest` ou `representationsAlternativesTest` : les
  trois formats bornés par défaut, la portion demandée respectée, l'ordre inchangé.
- [ ] 6.2 Couvrir le cas du filtre : une portion sur `?c=<contributeur>` est prise dans la
  playlist de ce contributeur.
- [ ] 6.3 Couvrir la portion au-delà de la fin : **200 et liste vide**, pas une erreur.
- [ ] 6.3bis **Matrice complète d'entrées (D6).** `count` à -1, 0, `abc`, 1, 50, plafond,
  plafond+1 ; `offset` à -1, 0, total-1, total, total+1. Motif : `(int) 'abc'` vaut 0 et
  `max(0, 0)` vaut 0, ce qui signifie **pas de limite** dans `buildOnlinePostsQuery` — donc
  `?count=abc` servirait les 8 098 morceaux, par une porte que personne n'aurait refermée.
  Ce garde-fou existe déjà et n'est couvert par aucun test ; la tâche 1.3 l'étend.
- [ ] 6.3ter **RÉGRESSIONS — ajoutées d'office, sans arbitrage.** Trois comportements
  existants changent et rien ne les couvre : le champ total du `max` reste le total ; le
  XSPF d'un morceau isolé ne parle jamais de portion ; la page HTML reste entière.
- [ ] 6.3quater **Les tests existants CASSERONT, il faut les amender et non seulement en
  ajouter.** `representationsAlternativesTest.php:50` assère `$nbPistes == $nbEnLigne`, `:92`
  `count($lignes) == $nbEnLigne`, `:97` le champ total contre `$nbEnLigne` — tous contre le
  compte complet en base. Le plan ne prévoyait que des ajouts.
- [ ] 6.4 Couvrir la page HTML : elle reste entière. C'est le garde-fou de la décision de
  l'auteur, et sans test rien n'empêchera un futur bornage de l'emporter au passage.
- [ ] 6.5 Couvrir la navigation : l'en-tête `Link` porte `next` sur la première portion et
  pas `prev` ; l'inverse sur la dernière. Et le **`max` reçoit son en-tête sans que son
  corps change** — c'est la promesse de la conception, elle se vérifie.
- [ ] 6.6 Le test de contrat passe : il itère sur les `paths` du document amendé.
- [ ] 6.7 `test:all` vert.

## 7. Ce que la revue laisse ouvert, sans le trancher

Relevé par la voix extérieure et vérifié, mais **non appliqué** : ces points appellent une
décision qui n'a pas été prise, et les taire reviendrait à les perdre.

- [ ] 7.1 **Le delta de spec est en `ADDED` seul.** Trois exigences publiées deviennent
  fausses et ne sont pas amendées : `formats-de-sortie` « un élément par morceau » (XSPF) et
  « chaque morceau produit une ligne » (Max/MSP), et `catalogue-morceaux` « tous les morceaux
  publiables sont listés » — cette dernière capacité n'est même pas au périmètre du change.
  Il manque des sections `## MODIFIED Requirements`.
- [ ] 7.2 **`<link rel="next">` en XSPF n'est probablement pas conforme.** XSPF 1.0 définit
  `rel` comme l'URI d'un type de ressource, pas un jeton IANA. Soit des URI complètes, soit
  reconnaître que ce canal n'apporte rien qu'un lecteur de playlist sache exploiter — ce qui
  retirerait sa justification à la tâche 3.5.
- [ ] 7.3 **`EnveloppeMorceaux` est partagée** par `/posts`, `/post/{slug}?format=json` et
  `/post/md5/{md5sum}`, et le contrat affirme qu'« un seul analyseur suffit à lire les deux ».
  Y ajouter total/portion/rang force soit un schéma dédié, soit la pollution de l'enveloppe
  d'un morceau isolé. **Les clés ne sont d'ailleurs jamais nommées** nulle part dans le plan.
- [ ] 7.4 **Le contenu des URL de `Link` n'est pas défini.** Quels paramètres sont reportés —
  `format`, `c`, `q` ? Si `format` tombe, `next` renvoie vers la page HTML.
- [ ] 7.5 **Les en-têtes sont mis en cache 24 h.** `Link` et le total JSON seraient figés :
  après la publication quotidienne, `last` et le total servis sont faux pendant une journée.
- [ ] 7.6 **La tâche 6.8 ne dit pas si la remesure se fait à cache froid.** Sans cette
  précision elle ne prouvera rien — les 17,5 s sont payées une fois par 24 h et par query
  string, pas à chaque clic.
- [ ] 7.7 **Le chemin `?q=` n'a aucune tâche.** La tâche 1.3 ne s'y applique pas :
  `search()` rend un tableau PHP, il faudra un `array_slice`, mécanisme distinct que ni les
  tâches ni le design ne mentionnent. Et « connaître le total impose une requête de
  comptage » est **faux** sur ce chemin, où le total est déjà en mémoire.

### Vérification manuelle — après la mise en ligne

- [ ] 6.8 Mesurer à nouveau les trois formats sur la production et **comparer aux chiffres
  qui ont motivé la story** — 8,4 Mo / 17,5 s en `json`, 2,6 Mo / 15,6 s en `max`,
  3,5 Mo / 2,9 s en `xspf`. Sans cette reprise, on ne saura pas si le change a servi.
- [ ] 6.9 Cliquer le lien XSPF visible depuis une page de liste et vérifier que le fichier
  se charge dans un lecteur, et que son titre dit ce qu'il contient.
- [ ] 6.10 Vérifier que `/posts` en HTML sert toujours les 8 098 morceaux — le Ctrl+F de
  l'auteur est le cas d'usage qui a fait geler la story 2.
