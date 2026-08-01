## 1. Implémentation du gabarit

- [ ] 1.1 Remplacer le contenu de `src/apps/frontend/modules/post/templates/showSuccess.max.php` par la construction de ligne, en reprenant à l'identique celle de `listSuccess.max.php` : rang, artiste, titre, adresse du fichier audio, adresse de la page, contributeur, nombre total, corps du post
- [ ] 1.2 Fixer le rang à `0` et le nombre total de morceaux à `1`
- [ ] 1.3 Reprendre l'assainissement des champs textuels du gabarit de liste : retrait des guillemets et des retours à la ligne, décodage des entités HTML

## 2. Vérification manuelle

- [ ] 2.1 Démarrer l'environnement (`./start-dev.sh`) et vider le cache
- [ ] 2.2 Demander `/post/:slug?format=max` sur un morceau existant et vérifier qu'une ligne complète est servie, et non le mot `TODO`
- [ ] 2.3 Vérifier que le type de contenu de la réponse est bien `application/maxmsp+text`
- [ ] 2.4 Comparer champ à champ la ligne obtenue avec celle que produit `/posts?format=max` pour ce même morceau : seuls le rang et le nombre total doivent différer
- [ ] 2.5 Choisir un morceau dont l'artiste, le titre ou le corps contiennent un guillemet ou un retour à la ligne, et vérifier que la ligne produite reste analysable
- [ ] 2.6 Vérifier que `/posts?format=max` est inchangé
