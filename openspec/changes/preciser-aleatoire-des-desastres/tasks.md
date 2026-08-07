## 1. Correction de l'exigence

- [x] 1.1 Réécrire « Requirement: Part d'aléatoire » pour décrire le tirage tel qu'il a lieu : au moment de la production de la page, puis figé pour la durée du cache
- [x] 1.2 Retirer l'affirmation « deux consultations de la même page ne produisent pas nécessairement le même effet », que la mesure contredit
- [x] 1.3 Ajouter un scénario disant comment observer une règle probabiliste — forcer par le déclencheur, ou faire varier l'adresse. C'est le geste que la campagne de vérification a dû inventer faute de le trouver écrit
- [x] 1.4 Ajouter une exigence « Granularité du tirage » : le hasard porte sur l'adresse et sur le moment, jamais sur le visiteur

## 2. Contrôle du reste du corpus

- [x] 2.1 Vérifier qu'aucune autre exigence ne suppose un aléa par visiteur ou par consultation
      — aucune. Les cinq autres capacités décrivent des réponses déterministes ; seule
      `desastres` introduit du hasard.
- [ ] 2.2 Relire les quatre scénarios de « Déclenchement conditionnel » à la lumière du cache — ils portent sur l'évaluation d'une règle, non sur sa fréquence, et devraient rester valides. À confirmer plutôt qu'à supposer

## 3. Vérification manuelle

> Ce changement ne modifie aucun comportement : il n'y a rien à vérifier sur le site. Les
> mesures qui fondent l'exigence ont été faites **avant** sa rédaction, et sont reproduites
> ci-dessous pour qu'un lecteur puisse les refaire.

- [x] 3.1 Mesurer une règle probabiliste sur une URL nue
      — `/post/eloi-soleil-mort`, règle `postillons_mort` à `probability: 0.7` :
      **0 déclenchement sur 20**. À 0,7, probabilité de 3 × 10⁻¹¹.
- [x] 3.2 Mesurer la même règle en faisant varier l'adresse
      — même page avec un paramètre inerte, `?cb=1` à `?cb=20` : **11 sur 20**, conforme à
      0,7. C'est le tirage qui est figé, pas la règle qui est cassée.
- [x] 3.3 Écarter le navigateur et l'intermédiaire de diffusion comme causes
      — en-têtes de la réponse : `cache-control: no-store, no-cache, must-revalidate`,
      `expires` daté de 1981, `cf-cache-status: DYNAMIC`. Rien n'est mis en cache en aval :
      c'est bien le cache de vues de l'application.
- [x] 3.4 Relever la configuration du cache
      — `src/apps/frontend/config/cache.yml` : `enabled: true`, `with_layout: true`,
      `lifetime: 86400`. Toutes les actions, avec habillage, pendant vingt-quatre heures.
- [x] 3.5 Établir l'ordre exact entre l'écriture du cache et le filtre `sfDesastreFilter`, qui injecte après le rendu. Le résultat mesuré ne dépend pas de cette réponse, mais elle conditionne toute tentative future de rétablir un tirage par visiteur — et elle ne s'obtient pas de l'extérieur
      — **établi le 7 août 2026**, et il était bien celui que la tâche redoutait : le filtre
      injectait **après** l'écriture du cache. La chaîne de filtres de symfony 1 remonte en
      ordre inverse de la déclaration, et `desastre` était déclaré au-dessus de `cache`.
      — La conséquence dépassait la question posée : la représentation mise en cache portait
      les ressources du désastre mais pas son bloc d'options, et un désastre ne s'appliquait
      donc qu'au premier visiteur. Corrigé par `reparer-injection-des-options`, en déplaçant
      la déclaration sous `cache`.
      — L'ordre est désormais l'inverse : injection puis écriture. Une tentative future de
      rétablir un tirage par visiteur devra donc porter sur le cache lui-même, comme 3.6 le
      prévoit, et non sur la position du filtre.

- [x] 3.7 **Distinguer le tirage des règles de celui des recettes.** Relevé le 7 août 2026
      dans un navigateur, sur une page de production servie depuis le cache : deux
      chargements de la même adresse annoncent « Will remove 10 characters » puis « 6 ».
      — Dix-neuf recettes sont déclarées, treize portent du JavaScript, et **six de
      celles-là** appellent `Math.random()` à l'exécution — `light`, `mamie`, `mangelettres`,
      `musique`, `postillons`, `tts`. Dans `mangelettres` c'est `Math.random() < rate` par
      caractère.
      — Le tirage figé par le cache est donc celui des **règles**, c'est-à-dire des recettes
      appliquées. Le rendu de ces recettes, lui, se retire à chaque chargement. « Deux
      visiteurs voient le même effet » était vrai de la recette et faux de son rendu ; les
      scénarios sont corrigés en conséquence.
- [x] 3.6 Décider si le comportement doit changer. Ce changement ne le tranche pas : il rend la question posable sur une base exacte. Toute correction passerait par le cache — exclusion d'actions, cache par fragment, ou injection côté client — et devra peser le coût sur la charge d'un site dont `post/show` et `post/list` font l'essentiel du trafic
      — **décidé le 7 août 2026 : le comportement ne change pas.**
      — Le coût, enfin pesé plutôt que supposé. Mesuré sur instance locale, cache vidé :

      | Route | Sans cache | Avec cache | Facteur |
      |---|---|---|---|
      | `/post/:slug` | 40–310 ms | 11–46 ms | 3 à 7× |
      | `/posts` | 2 600 ms | 175 ms | 15× |

      La tâche traitait `post/show` et `post/list` ensemble ; ce sont deux problèmes
      distincts. La liste construit six mille morceaux, la page de morceau non. Une
      exclusion du cache limitée à `post/show` était donc envisageable, contrairement à ce
      que la formulation laissait croire — elle n'est pas retenue, mais elle existait.
      — **Ce qui a emporté la décision est ailleurs** : la tâche 3.7 montre que six recettes
      tirent au sort dans le navigateur. Le cache fige *quel* désastre s'applique, pas
      *comment il se joue*. Un visiteur qui revient revoit `mangelettres`, mais pas les
      mêmes lettres. La variation existe déjà.
      — Ce qui restait à corriger était un défaut, pas un choix : un désastre ne s'appliquait
      qu'au premier visiteur. `reparer-injection-des-options` l'a réglé. Le reste est une
      question de goût, et rien ne justifie de payer du temps de rendu pour la trancher.
      — Écartées, et pourquoi : raccourcir la durée de vie paierait sur les pages chaudes,
      les plus coûteuses, pour un gain borné ; le cache par fragment n'a pas de fragment à
      isoler, le désastre touchant `<head>` et contenu ; le tirage côté client demanderait
      de charger les ressources après coup et d'exporter au navigateur des règles qui
      s'évaluent sur du contexte serveur.
