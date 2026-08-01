## 1. Calcul de la taille du fichier joint

- [ ] 1.1 Dans `executeFeed()` (`src/apps/frontend/modules/post/actions/actions.class.php`), remplacer la mesure par lecture du fichier par une interrogation du système de fichiers
- [ ] 1.2 Conserver la vérification de lisibilité et le repli à zéro qu'elle commande, pour ne pas produire d'avertissement sur un fichier absent en développement

## 2. Corpus de specs

- [ ] 2.1 Retirer la note de défaut qui accompagne l'exigence « Contenu d'un item » dans `openspec/specs/flux-syndication/spec.md` : elle n'a plus d'objet une fois le calcul corrigé

## 3. Vérification manuelle

- [ ] 3.1 Démarrer l'environnement (`./start-dev.sh`) et vider le cache
- [ ] 3.2 Enregistrer la sortie de `/posts/feed` avant modification, puis après, et vérifier que les deux documents sont identiques — les tailles déclarées doivent être inchangées, item par item
- [ ] 3.3 Vérifier qu'un morceau dont le fichier audio est absent du disque produit toujours une taille déclarée de zéro, sans avertissement PHP
- [ ] 3.4 Vérifier que `/posts/feed?contributor=` et `/posts/feed?count=` restent inchangés
