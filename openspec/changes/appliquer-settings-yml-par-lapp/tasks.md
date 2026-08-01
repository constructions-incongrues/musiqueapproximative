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

> **La prémisse de ce changement est fausse.** Constat établi après vérification des
> réglages du dépôt : il n'existait plus **ni ruleset, ni règle de protection classique**
> sur `main`. La branche était sans aucune protection.
>
> `settings.yml` déclare pourtant une section `branches` complète, l'app est installée, et
> plusieurs push sur la branche par défaut ont eu lieu depuis. Aucune règle n'a été créée.
> **L'app n'applique donc pas le fichier.** Deux lectures, non départagées : elle échoue
> silencieusement — permissions, ou section refusée par l'API — ou bien elle a supprimé la
> protection existante sans la remplacer.
>
> Dans les deux cas, faire de ce fichier la source de vérité de la protection ne marche
> pas. Il reste légitime pour les métadonnées et les libellés.
>
> Une protection a été restaurée à la main. La prochaine fusion sur `main` dira si l'app
> la supprime à nouveau : c'est le test qui désigne le coupable.

- [x] 3.1 Installer l'app GitHub Settings — fait. La protection de `main` a par ailleurs été corrigée à la main, indépendamment de l'app.
- [x] 3.2 Établir lequel des deux systèmes gouverne `main`
      — **aucun des deux.** Ni ruleset, ni règle classique. La branche était ouverte.
- [x] 3.2bis Décider du sort de la section `branches` de `settings.yml`
      — la question ne se pose plus dans les termes prévus : ce n'est pas que le fichier
      décrive le mauvais système, c'est que l'app ne l'applique pas du tout. La décision
      dépend du test ci-dessous.
- [x] 3.2ter Restaurer une protection sur `main` à la main — fait.
- [ ] 3.2quater **Test décisif** : après la prochaine fusion sur `main`, vérifier si la protection restaurée est toujours là. Si elle a disparu, l'app en est la cause et doit être désinstallée. Si elle tient, l'app est simplement inerte, et sa section `branches` est à retirer du fichier
- [ ] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés de façon inattendue
- [ ] 3.4 Ouvrir une pull request de contrôle et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
      — première tentative sur la PR #98 : **non concluante**. Les dix checks étaient
      verts, les quatre contextes requis compris, et la pull request est restée
      `blocked` plusieurs minutes avant d'être fusionnée sans qu'on puisse dire si
      l'automatisme a joué. Reste inexpliqué : sans aucune protection, GitHub aurait dû
      rapporter `clean`. Une règle au niveau de l'organisation reste à écarter.
- [ ] 3.5 Modifier une valeur du fichier dans une pull request et vérifier qu'elle est appliquée après fusion : c'est ce qui distingue une source de vérité d'un document décoratif
