# Tâches

## 1. La jointure, conditionnée à la projection

- [ ] 1.1 Dans `buildOnlinePostsQuery`, joindre `UserProfile` et projeter explicitement
  **uniquement quand `$fields` vaut son défaut**. Un appelant qui a restreint sa projection
  a déjà dit ce qu'il voulait : sa requête ne doit pas changer d'un octet.
- [ ] 1.2 `leftJoin` et non `innerJoin` : un morceau dont le contributeur n'a pas de profil
  doit rester servi. Le schéma déclare la relation `one`-to-`one`, donc aucune ligne ne peut
  être dupliquée — ce qui protège `countOnlinePosts()`, qui appelle `->count()` sur cette
  même requête.
- [ ] 1.3 Vérifier les **cinq appels Subsonic** de `src/apps/frontend/modules/rest/` et de
  `PostTable` : ils passent `FIELDS_SUBSONIC`, ne lisent jamais `UserProfile`, et ne doivent
  ni ralentir ni recevoir de champs supplémentaires.

## 2. Le test qui empêche le retour du N+1

- [ ] 2.1 Écrire un test qui **compte les requêtes** émises pour servir une liste.
- [ ] 2.2 **Vider l'identity map avant chaque mesure** (`$conn->clear()`). Sans ça le N+1 est
  invisible : c'est l'erreur commise pendant le diagnostic de ce change, où une première
  mesure a conclu « ce n'est pas 8 100 requêtes » et se trompait.
- [ ] 2.3 **Comparer deux tailles plutôt que viser un nombre absolu.** Un coût constant se
  démontre en montrant qu'il ne bouge pas quand la liste double. Un test qui assère « exactement
  une requête » casserait au premier ajout légitime, et serait désactivé plutôt que compris.
- [ ] 2.4 Couvrir les trois sites de lecture : `getContributorDisplayName()`,
  `getSfGuardUser()->username`, `UserProfile->website_url`.
- [ ] 2.5 Couvrir le cas de la projection restreinte : son coût ne doit pas augmenter.

## 3. Vérification

- [ ] 3.1 Mesurer avant / après sur le catalogue complet. Attendu, d'après le diagnostic :
  **8 271 requêtes / 7,17 s → 1 requête / 1,08 s**.
- [ ] 3.2 Vérifier que le contributeur d'un morceau sans profil ne fait pas disparaître ce
  morceau de la liste.
- [ ] 3.3 Vérifier que `countOnlinePosts()` rend le même total qu'avant — c'est le point que
  la jointure aurait pu casser.
- [ ] 3.4 Vérifier que les représentations servent **exactement le même document** qu'avant.
  Ce change ne doit rien changer d'observable : comparer les empreintes des trois formats
  machine avant et après, sur les mêmes morceaux.
- [ ] 3.5 `test:all` vert.

### Vérification manuelle — après la mise en ligne

- [ ] 3.6 Remesurer les quatre représentations sur la production, **à cache froid**, et
  comparer aux chiffres qui ont motivé la story 3 : json 8,4 Mo / 17,5 s, max 2,6 Mo / 15,6 s,
  html 3,7 Mo / 14,9 s, xspf 3,5 Mo / 2,9 s.
  Le xspf est le témoin : il ne lit pas le contributeur, **il ne doit pas bouger**. Si tous
  les quatre s'améliorent d'autant, la cause n'était pas celle qu'on croit.
- [ ] 3.7 Vérifier qu'une page de morceau affiche toujours le bon nom de contributeur et son
  lien de site — c'est ce que `UserProfile` porte, et c'est ce qu'une jointure fautive
  ferait disparaître en silence.

## 4. Ce que ce change ne ferme pas

- [ ] 4.1 **`PostTable::search()`** porte son propre N+1 — une requête par résultat — et rend
  un tableau PHP au lieu d'une requête. Le chemin `?q=` ne bénéficiera pas de ce change.
- [ ] 4.2 **Le poids ne baisse pas.** 8,4 Mo de JSON restent 8,4 Mo : ce change corrige
  l'attente, pas la bande passante. C'est la story 3, gelée, qui traitait le poids — et son
  argument d'urgence vient précisément de tomber.
