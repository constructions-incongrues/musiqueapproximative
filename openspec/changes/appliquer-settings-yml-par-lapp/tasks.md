## 1. Mise en exactitude du fichier

- [x] 1.1 Réintroduire `Trivy Scan` dans les contextes de vérification requis de `.github/settings.yml`
- [x] 1.2 Réécrire les commentaires qui décrivaient un état transitoire — workflow désactivé, contexte à réintroduire — pour qu'ils décrivent la configuration voulue
- [x] 1.3 Passer `enforce_admins` de `true` à `false`, et documenter la raison en commentaire : sans issue de secours, un contexte requis qui cesse de remonter verrouillerait le dépôt

## 2. Contrôle de ce que l'application a modifié

> Ces vérifications étaient prévues **avant** l'installation. Elle a eu lieu sans qu'elles
> soient faites : l'app a donc appliqué l'intégralité du fichier — métadonnées du dépôt,
> seize libellés, protection de branche — en résolvant en sa faveur tout écart avec les
> réglages existants.
>
> Elles restent dues, en vérification a posteriori. L'ordre inverse coûte plus cher : au
> lieu de choisir ce qu'on aligne, il faut constater ce qui a bougé. Rien n'indique qu'un
> dommage se soit produit, mais rien ne l'exclut non plus.

- [ ] 2.1 Comparer la section `repository` aux réglages actuels : description, page d'accueil, sujets, options de fusion, suppression de branche après fusion. Repérer ce que l'application a modifié
- [ ] 2.2 Comparer les seize libellés déclarés à ceux qui existent : en repérer d'éventuels créés, renommés ou recolorés, et vérifier qu'aucun libellé en usage n'a été altéré
- [x] 2.3 Vérifier qu'aucune issue ni pull request n'a perdu un libellé du fait d'un renommage
      — sans objet côté issues : le dépôt n'en compte aucune, tous états confondus. Le
      risque ne subsiste que sur les pull requests closes, où il est cosmétique.
- [ ] 2.4 Pour chaque écart constaté, décider si c'est le fichier ou l'ancien réglage qui avait raison — et corriger le fichier le cas échéant, puisque c'est lui qui fait foi désormais

## 3. Installation et vérification

> **Un ruleset a disparu.** Constat rapporté après l'installation, cause non établie.
>
> GitHub protège une branche par deux systèmes distincts : les *branch protection rules*
> classiques, et les *rulesets*, plus récents, dotés d'une API entièrement séparée. À
> notre connaissance l'app Probot Settings ne pilote que le premier : sa section
> `branches[].protection` écrit dans l'API classique et ignore les rulesets.
>
> Si la protection de ce dépôt était un ruleset, alors `settings.yml` ne la décrivait pas
> et ne pouvait pas la décrire — ce qui expliquerait que les contextes requis qu'il
> déclarait ne correspondaient à rien d'observable. La prémisse de ce changement serait
> alors fausse : le fichier ne peut pas être la source de vérité de la protection, même
> s'il reste légitime pour les métadonnées et les libellés.
>
> À trancher avant toute autre chose. Tant que ce n'est pas établi, `main` est peut-être
> sans protection.

- [x] 3.1 Installer l'app GitHub Settings — fait. La protection de `main` a par ailleurs été corrigée à la main, indépendamment de l'app.
- [ ] 3.2 Établir lequel des deux systèmes gouverne `main` : consulter Réglages → Rules → Rulesets, puis Réglages → Branches. Si un ruleset existait et a disparu, déterminer si l'app en est la cause
- [ ] 3.2bis Si le dépôt fonctionne aux rulesets, acter que la section `branches` de `settings.yml` est inopérante et décider de son sort — la retirer, ou renoncer aux rulesets au profit de la protection classique
- [ ] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés de façon inattendue
- [ ] 3.4 Ouvrir une pull request de contrôle et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
      — première tentative sur la PR #98 : **non concluante**. Les dix checks étaient
      verts, les quatre contextes requis compris, et la pull request est restée
      `blocked` plusieurs minutes avant d'être fusionnée sans qu'on puisse dire si
      l'automatisme a joué. Une exigence non satisfaite subsistait, qui n'était aucun
      des contextes.
- [ ] 3.5 Modifier une valeur du fichier dans une pull request et vérifier qu'elle est appliquée après fusion : c'est ce qui distingue une source de vérité d'un document décoratif
