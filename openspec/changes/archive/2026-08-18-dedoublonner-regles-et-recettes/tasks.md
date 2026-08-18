## 1. Dédoublonnage des règles au chargement

- [x] 1.1 Dans `processImports()`, ne conserver qu'une occurrence d'une règle dont la
      condition, la probabilité et la liste de recettes sont identiques à une règle déjà
      chargée. Exclure `trigger` de la comparaison, conformément au design
- [x] 1.2 Conserver la première occurrence à son rang d'origine, sans décaler les règles
      qui la précèdent

## 2. Dédoublonnage des recettes à la sélection

- [x] 2.1 Dans `findRecettes()`, ne retourner qu'une occurrence d'une recette, quel que
      soit le nombre de règles satisfaites qui la désignent
- [x] 2.2 Conserver le rang de la première règle qui la désigne

## 3. Tests

- [x] 3.1 Dans `src/test/unit/plugins/DesastreConfigTest.php`, remplacer le `todo()` de
      l'unicité des règles par une assertion franche : la configuration chargée depuis la
      fixture `regle-dupliquee` ne porte qu'une règle
- [x] 3.2 Remplacer le `todo()` du dédoublonnage des recettes par une assertion franche :
      `findRecettes()` ne retourne `premiere` qu'une fois
- [x] 3.3 Ajouter une fixture où deux règles **différentes** désignent la même recette, et
      vérifier qu'elle n'est retenue qu'une fois. C'est le cas nominal, distinct de la
      règle dupliquée
- [x] 3.4 Vérifier le rang : la recette dédoublonnée occupe la position de la première
      règle qui la désigne

## 4. Vérification manuelle

- [x] 4.1 Relire `src/apps/frontend/config/desastres/recettes/*.yml` et confirmer qu'aucune
      recette livrée ne dépend d'être appliquée deux fois
- [x] 4.2 Lancer `docker compose run --rm --no-deps php php symfony test:unit` et constater
      que `plugins/DesastreConfigTest` passe sans `todo()` restant
- [x] 4.3 Constater qu'aucun test existant ne régresse : 11 scripts, les 3 échecs connus du
      garde-fou de fixtures, et pas un de plus
- [x] 4.4 Contrôle de mutation : retirer le dédoublonnage des règles, relancer, vérifier que
      la tâche 3.1 devient rouge. Rétablir
- [x] 4.5 Sur une page portant un désastre forcé par son déclencheur, vérifier dans le HTML
      servi qu'aucune feuille de style ni aucun script du désastre n'apparaît deux fois
- [x] 4.6 Si une vérification n'a pas pu être menée, laisser sa case décochée et le dire en
      clair ici. Une case cochée signifie vérifiée

## Ce que l'implémentation a corrigé dans la planification

**La signature d'identité d'une règle inclut `trigger`.** Le design décidait l'inverse. Le
test l'a démenti : deux règles ne différant que par leur déclencheur étaient fusionnées, et
la survivante était celle **sans** déclencheur. Le dédoublonnage supprimait donc un
déclencheur, en violation de l'exigence « Couverture des déclencheurs ». Décision inversée,
motif consigné dans `design.md`, et le cas est désormais un scénario de la spec.

**La proposition surestimait l'impact.** Elle annonçait une réponse portant deux fois les
mêmes ressources. Mesuré sur la configuration de production, c'est faux : `sfWebResponse`
indexe par chemin, et les options sont accumulées par nom de désastre avant un unique
appel d'injection. La duplication n'a aucun effet observable sur la réponse servie. Le
« Why » a été réécrit sur ce qui reste vrai — le bug de probabilité, le piège latent dans
`findRecettes()`, et le balayage disque redondant.

**Un scénario de spec ne pouvait pas échouer.** « Règle dupliquée, effet unique » passait
avant comme après, puisque symfony dédoublonnait déjà. Il a été retiré et remplacé par
« Règles distinguées par leur déclencheur », qui encode le piège que l'implémentation a
réellement rencontré.

## Résultats

- Suite unitaire : **11 scripts, 199 assertions**, 3 échecs préexistants inchangés — le
  garde-fou du bootstrap, qui refuse de charger des fixtures contre une base qui n'est pas
  une base de test.
- `plugins/DesastreConfigTest` : 26 assertions, **aucun `todo()` restant**.
- Contrôle de mutation : retirer le dédoublonnage des règles fait rougir l'assertion qui le
  garde ; le rétablir la remet au vert.
- Configuration de production : 19 règles chargées, aucune perdue. Forcer les deux règles
  qui désignent `quickos` retourne la recette une fois, contre deux avant.

## Constat de configuration, hors périmètre

`regles/redirects.yml` déclare deux règles dont les conditions se recouvrent :
`month == '12'` d'une part, `month == '12' && (day == '24' || day == '25')` d'autre part.
Toutes deux désignent `quickos`. Les 24 et 25 décembre, la seconde ne fait donc que
répéter la première, avec une probabilité de 0,5 qui n'ajoute rien puisque la première se
déclenche à coup sûr. Ce changement rend la répétition inoffensive ; il ne corrige pas la
configuration, qui exprime vraisemblablement une intention perdue.
