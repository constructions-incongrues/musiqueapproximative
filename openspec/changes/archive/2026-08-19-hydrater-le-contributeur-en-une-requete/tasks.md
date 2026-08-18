# Tâches

## 1. La jointure, conditionnée à la projection

- [x] 1.1 Dans `buildOnlinePostsQuery`, joindre `UserProfile` et projeter explicitement
  **uniquement quand `$fields` vaut son défaut**. Un appelant qui a restreint sa projection
  a déjà dit ce qu'il voulait : sa requête ne doit pas changer d'un octet.
- [x] 1.2 `leftJoin` et non `innerJoin` : un morceau dont le contributeur n'a pas de profil
  doit rester servi. Le schéma déclare la relation `one`-to-`one`, donc aucune ligne ne peut
  être dupliquée — ce qui protège `countOnlinePosts()`, qui appelle `->count()` sur cette
  même requête.
- [x] 1.3 Vérifier les **cinq appels Subsonic** de `src/apps/frontend/modules/rest/` et de
  `PostTable` : ils passent `FIELDS_SUBSONIC`, ne lisent jamais `UserProfile`, et ne doivent
  ni ralentir ni recevoir de champs supplémentaires.

## 2. Le test qui empêche le retour du N+1

- [x] 2.1 `src/test/unit/model/PostTableHydratationTest.php`, 9 assertions.
  **Vérifié qu'il mord** : jointure désactivée, 5 assertions tombent et le message nomme le
  coupable — « 3 requetes pour 1 morceaux, 8 pour 4 ».
- [x] 2.2 **Vider l'identity map avant chaque mesure** (`$conn->clear()`). Sans ça le N+1 est
  invisible : c'est l'erreur commise pendant le diagnostic de ce change, où une première
  mesure a conclu « ce n'est pas 8 100 requêtes » et se trompait.
- [x] 2.3 **Comparer deux tailles plutôt que viser un nombre absolu.** Un coût constant se
  démontre en montrant qu'il ne bouge pas quand la liste double. Un test qui assère « exactement
  une requête » casserait au premier ajout légitime, et serait désactivé plutôt que compris.
- [x] 2.4 Couvrir les trois sites de lecture : `getContributorDisplayName()`,
  `getSfGuardUser()->username`, `UserProfile->website_url`.
- [x] 2.5 Projection restreinte couverte, **avec contre-épreuve** : lire le corps du morceau
  doit déclencher un lazy-load. Sans elle, l'assertion passerait aussi si la projection avait
  été silencieusement élargie — un coût d'une requête ne prouve rien si tout est chargé.

## 3. Vérification

- [x] 3.1 Mesuré : **8 271 requêtes / 7,17 s → 1 requête / 1,03 s.** Conforme au diagnostic.
- [x] 3.2 **Trois contributeurs n'ont pas de profil** dans les données réelles, et les 8 098
  morceaux sont tous servis. Le choix `leftJoin` plutôt qu'`innerJoin` n'était pas théorique.
- [x] 3.3 `countOnlinePosts()` rend **8 098**, inchangé. La relation `one`-to-`one` tient sa
  promesse : aucune ligne dupliquée.
- [x] 3.4 **`json` et `max` : empreintes identiques au bit près.** Le `xspf` différait — et
  c'était son `<date>`, qui change à la seconde. Hors cette ligne, il est identique lui aussi.
  Vérifié plutôt que supposé : deux appels consécutifs donnaient déjà deux empreintes
  différentes du même code.
- [x] 3.5 `test:all` : **23 fichiers, 654 tests, verts.**

### Vérification manuelle — après la mise en ligne

- [x] 3.6 **Remesuré en production, à cache froid. Le témoin a répondu.**

  | format | avant | après | |
  | --- | --- | --- | --- |
  | `json` | 17,5 s | **5,49 s** | 3,2× |
  | `max` | 15,6 s | **4,36 s** | 3,6× |
  | `html` | 14,9 s | **3,23 s** | 4,6× |
  | `xspf` | 2,9 s | **3,47 s** (médiane de 5) | **n'a pas gagné** |

  Les trois formats qui lisent le contributeur accélèrent d'un facteur 3 à 4,6 ; celui qui ne
  le lit pas, non. **Le diagnostic est confirmé par son témoin.**

- [x] 3.6bis **Coût introduit, mesuré et assumé : le `xspf` a ralenti d'environ 0,5 s.**
  Cinq mesures — 3,23 / 3,48 / 4,11 / 3,31 / 3,47 s — contre 2,9 s avant. Ce n'est pas du
  bruit. Il passe par la même requête avec `$fields` au défaut, reçoit donc la jointure et
  hydrate 8 098 `UserProfile` **qu'il ne lit jamais**.
  Accepté en l'état : +0,5 s sur le format le plus rapide contre −12 s sur le `json` et
  −11,7 s sur la page HTML. Le corriger demanderait une projection propre au `xspf`, et il
  lit malgré tout le contributeur pour composer son titre quand la liste est filtrée par `?c=`.
- [x] 3.7 Vérifié en production : `name`, `slug` et `href_website` sont tous les trois
  servis — « Deehowyou », `deehowyou`, `http://myspace.com/deehowyou`. Une jointure fautive
  les aurait vidés en silence.

## 4. Ce que ce change ne ferme pas

- [ ] 4.1 **`PostTable::search()`** porte son propre N+1 — une requête par résultat — et rend
  un tableau PHP au lieu d'une requête. Le chemin `?q=` ne bénéficiera pas de ce change.
- [ ] 4.2 **Le poids ne baisse pas.** 8,4 Mo de JSON restent 8,4 Mo : ce change corrige
  l'attente, pas la bande passante. C'est la story 3, gelée, qui traitait le poids — et son
  argument d'urgence vient précisément de tomber.
