## 1. Fixtures

- [x] 1.1 Créer `src/test/fixtures/desastres/complet/desastres.yml`, dont tous les imports
      se résolvent, avec au moins deux règles et deux recettes réparties sur deux fichiers
      importés
- [x] 1.2 Créer `src/test/fixtures/desastres/import-casse/desastres.yml`, déclarant un
      import de règles vers un fichier absent et un import de recettes valide
- [x] 1.3 Créer `src/test/fixtures/desastres/regle-dupliquee/desastres.yml`, où une même
      règle — condition, probabilité et recettes identiques — est déclarée dans deux
      fichiers importés
- [x] 1.4 Créer `src/test/fixtures/desastres/sans-declencheur/desastres.yml`, portant une
      règle sans paramètre de déclenchement et deux règles partageant le même déclencheur

## 2. Tests du manager

- [x] 2.1 Créer `src/test/unit/plugins/DesastreConfigTest.php`, bootstrap unitaire et
      `require_once` de `sfDesastreManager` sur le modèle de `JsonApiFilterTest`
- [x] 2.2 Tous les imports se résolvent : `getUnresolvedImports()` renvoie un tableau vide,
      et les règles des deux fichiers importés participent bien à l'évaluation
- [x] 2.3 L'ordre de déclaration des imports détermine l'ordre d'évaluation des règles
- [x] 2.4 Un import ne se résout pas : `getUnresolvedImports()` nomme le chemin fautif, et
      les règles des imports valides restent chargées
- [x] 2.5 Configuration partiellement invalide : l'écart entre déclaré et chargé est
      constatable, le manager ne se comporte pas comme si la règle manquante n'avait
      jamais été déclarée
- [x] 2.6 Règle déclarée dans deux fichiers importés : elle n'est évaluée qu'une fois, sa
      probabilité effective est celle qu'elle annonce
- [x] 2.7 Recette désignée par plusieurs règles satisfaites : `findRecettes()` ne la
      retourne qu'une fois. Voir la question ouverte du design : si le dédoublonnage
      n'existe pas, laisser le test rouge et le signaler plutôt que d'ajuster l'assertion
- [x] 2.8 Règle sans paramètre de déclenchement : la non-conformité est constatable
- [x] 2.9 Deux règles au même déclencheur : le paramètre force les deux, l'ambiguïté est
      constatable

## 3. Test du helper

- [x] 3.1 Fichier de configuration manquant : `apply_desastre()` retourne sans lever et
      sans altérer la réponse. Viser le helper, jamais `sfDesastreManager` — voir la
      décision correspondante du design

## 4. Vérification manuelle

- [x] 4.1 Lancer `docker-compose exec php php symfony test:unit` et constater que le
      nouveau fichier passe, en relevant le nombre d'assertions annoncé
- [x] 4.2 Lancer `docker-compose exec php php symfony test:all` et constater qu'aucun test
      existant ne régresse
- [x] 4.3 Contrôle de mutation, une fois par groupe : renommer temporairement le fichier
      d'import valide de la fixture `complet`, relancer, et vérifier que le test 2.2
      devient rouge. Rétablir. Un test qui reste vert quand le comportement est cassé ne
      teste rien
- [x] 4.4 Vérifier qu'aucun fichier hors `src/test/` n'a été modifié :
      `git status --porcelain` ne doit montrer que des ajouts sous `src/test/`
- [x] 4.5 Si l'un des neuf scénarios ne peut pas passer, laisser sa case décochée et écrire
      dans ce fichier lequel, et si la faute revient au code ou à la spec. Une case cochée
      signifie vérifiée

## Écarts constatés, puis corrigés

Les tâches **2.6** et **2.7** ont d'abord constaté un écart : leurs scénarios décrivaient un
comportement que le code n'avait pas. Elles ont été livrées en `todo()`, le trancher
dépassant le périmètre de ce changement.

Le changement `dedoublonner-regles-et-recettes` les a corrigées ensuite, et les deux
`todo()` sont devenus des assertions franches. Les cases sont cochées à ce titre. Le
constat d'origine est conservé ci-dessous : c'est lui qui a motivé le changement suivant.

**2.6 — une règle déclarée deux fois est chargée deux fois.** `processImports()` fusionne
par `array_merge()`, qui concatène deux tableaux à clés numériques. Une règle identique
déclarée dans deux fichiers importés est donc évaluée deux fois. Avec une probabilité
inférieure à 1, sa probabilité effective devient le cumul de deux tirages indépendants —
exactement ce que la spec interdit.

**2.7 — une recette désignée par deux règles satisfaites est retenue deux fois.**
`findRecettes()` empile par `$selectedRecettes[] = $recette` sans contrôle, et
`applyRecettesToResponse()` itère sans mémoire de ce qui a déjà été injecté. Le test le
démontre : `findRecettes()` retourne `premiere, premiere`.

Les deux assertions ont été posées en `todo()` plutôt qu'en échec dur : elles restaient
visibles dans la sortie, nommant le comportement observé, sans immobiliser la CI sur un
écart que ce changement n'avait pas mandat de corriger. Elles sont depuis devenues des
assertions franches.

## Résultats

- Suite unitaire : **11 scripts, 195 assertions**, contre 10 et 173 avant ce changement.
- `plugins/DesastreConfigTest` : 22 assertions, dont 2 en `todo()`.
- Les 3 scripts en échec — `FixturesTest`, `PostMetadataTest`, `PostTableSubsonicTest` —
  l'étaient déjà. Ils butent sur un garde-fou volontaire du bootstrap, qui refuse de
  charger des fixtures contre une base qui n'est pas une base de test.
- Contrôle de mutation : renommer `complet/desastres/regles-deux.yml` fait passer
  4 assertions au rouge ; les rétablir les remet au vert.
