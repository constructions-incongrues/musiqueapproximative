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
- [ ] 2.3 Vérifier qu'aucune issue ni pull request n'a perdu un libellé du fait d'un renommage
- [ ] 2.4 Pour chaque écart constaté, décider si c'est le fichier ou l'ancien réglage qui avait raison — et corriger le fichier le cas échéant, puisque c'est lui qui fait foi désormais

## 3. Installation et vérification

- [x] 3.1 Installer l'app GitHub Settings — fait. La protection de `main` a par ailleurs été corrigée à la main, indépendamment de l'app.
- [ ] 3.2 Vérifier que la protection de `main` reflète désormais le fichier : quatre contextes requis, zéro approbation exigée, historique linéaire
- [ ] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés de façon inattendue
- [ ] 3.4 Ouvrir une pull request de contrôle et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
- [ ] 3.5 Modifier une valeur du fichier dans une pull request et vérifier qu'elle est appliquée après fusion : c'est ce qui distingue une source de vérité d'un document décoratif
