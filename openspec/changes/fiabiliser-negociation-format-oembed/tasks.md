## 1. Négociation du format

- [x] 1.1 Dans `executeOembed()` (`src/apps/frontend/modules/post/actions/actions.class.php`), normaliser le paramètre `format` avant comparaison : minuscules et espaces de bordure retirés
- [x] 1.2 Ajouter le cas de refus pour tout format autre que `json` ou `xml` : répondre `501` sans corps de données
- [x] 1.3 Déclarer le type de contenu `application/json+oembed` pour la réponse JSON, en retirant la ligne mise en commentaire qui le prévoyait déjà

## 2. Vérification manuelle

- [x] 2.1 Démarrer l'environnement (`./start-dev.sh`) et vider le cache
- [x] 2.2 Demander `/oembed?url=<morceau>` sans paramètre `format` et vérifier que le corps est inchangé et le type de contenu `application/json+oembed`
- [x] 2.3 Demander `format=xml` et vérifier que le document et son type de contenu sont inchangés
- [x] 2.4 Demander `format=yaml` et vérifier le code `501`, ainsi que l'absence de donnée de morceau dans le corps
- [x] 2.5 Demander `format=JSON` puis `format=%20xml%20` et vérifier que les deux sont reconnus
- [x] 2.6 Vérifier qu'une URL désignant un morceau inexistant renvoie toujours `404`, y compris avec un `format` inconnu — le morceau est résolu avant la négociation
- [x] 2.7 Contrôler avec un consommateur oEmbed réel, ou à défaut un validateur en ligne, que le changement de type de contenu ne casse pas la restitution
