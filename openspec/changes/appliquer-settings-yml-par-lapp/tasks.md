## 1. Mise en exactitude du fichier

- [x] 1.1 Réintroduire `Trivy Scan` dans les contextes de vérification requis de `.github/settings.yml`
- [x] 1.2 Réécrire les commentaires qui décrivaient un état transitoire — workflow désactivé, contexte à réintroduire — pour qu'ils décrivent la configuration voulue
- [x] 1.3 Passer `enforce_admins` de `true` à `false`, et documenter la raison en commentaire : sans issue de secours, un contexte requis qui cesse de remonter verrouillerait le dépôt

## 2. Avant l'installation

> Ces vérifications sont à faire **avant** de déclencher l'installation, pas après.
> L'app applique l'intégralité du fichier : tout écart entre ce qu'il déclare et les
> réglages actuels sera résolu en faveur du fichier.

- [ ] 2.1 Comparer la section `repository` aux réglages réels : description, page d'accueil, sujets, options de fusion, suppression de branche après fusion
- [ ] 2.2 Comparer les seize libellés déclarés à ceux qui existent, en repérant ceux qui seraient créés, renommés ou recolorés
- [ ] 2.3 Comparer la section `branches` à la protection réellement appliquée sur `main`, et relever les écarts
- [ ] 2.4 Décider, pour chaque écart relevé, si c'est le fichier ou le réglage qui a raison — et corriger le fichier le cas échéant

## 3. Installation et vérification

- [ ] 3.1 Installer l'app GitHub Settings depuis `github.com/apps/settings`, en la limitant au dépôt `musiqueapproximative`
- [ ] 3.2 Vérifier que la protection de `main` reflète désormais le fichier : quatre contextes requis, zéro approbation exigée, historique linéaire
- [ ] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés de façon inattendue
- [ ] 3.4 Ouvrir une pull request de contrôle, sans y toucher, et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
- [ ] 3.5 Modifier une valeur du fichier dans une pull request et vérifier qu'elle est appliquée après fusion : c'est ce qui distingue une source de vérité d'un document décoratif
