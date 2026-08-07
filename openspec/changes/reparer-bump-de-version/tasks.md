## 1. Rendre le fichier écrivable par release-please

- [x] 1.1 Annoter la ligne de version de `src/VERSION` avec le marqueur `x-release-please-version` que l'updater `generic` recherche
- [x] 1.2 Porter `src/VERSION` à `1.10.0`, la version que `.release-please-manifest.json` déclare déjà et que le dépôt a réellement publiée

## 2. Rendre le lecteur tolérant

- [x] 2.1 Dans `VersionFilter`, remplacer le `trim(file_get_contents())` par l'extraction du premier jeton non blanc
- [x] 2.2 Conserver le repli sur `dev` lorsque le fichier est absent, et l'étendre au cas où il ne contiendrait aucun jeton exploitable
- [x] 2.3 Vérifier qu'un fichier réduit à `1.10.0`, sans annotation, continue de fonctionner — le changement doit rester réversible
      — six cas exercés hors Symfony sur l'extraction seule, tous verts : fichier annoté,
      fichier nu, absence de saut de ligne final, espaces parasites, fichier vide et
      contenu vide — les deux derniers retombant sur `dev`. `php -l` passe.

## 3. Vérification manuelle

> La vérification décisive dépend d'une publication : c'est release-please qui doit écrire
> le fichier, et lui seul prouve que la déclaration `extra-files` produit enfin un effet.
> Les tâches 3.4 et 3.5 restent donc ouvertes jusqu'à la prochaine version.

- [x] 3.1 Demander une page du site et relever le `?v=` des ressources statiques — il doit valoir `1.10.0`
      — sur instance locale : `?v=1.10.0`, quinze occurrences par page, conforme à
      `src/VERSION`.
- [x] 3.2 Vérifier que la valeur relevée provient bien du fichier et non d'une page en cache, en faisant varier l'URL demandée. Le gabarit est mis en cache avec habillage pendant vingt-quatre heures : lire la page nue ne prouve rien
      — quatre URL distinctes — liste, morceau, recherche, contributeur — servent la même
      valeur. Le filtre s'exécute donc bien à chaque requête.
- [x] 3.3 Contrôler que les autres pages — morceau, liste, flux — restent servies normalement, le filtre s'exécutant sur chaque requête
      — morceau, liste, flux, `json` et `xspf` répondent tous `200` avec leur type propre.
- [ ] 3.4 **À la prochaine publication** : vérifier que la pull request de release-please modifie `src/VERSION`, ce qu'elle n'a jamais fait. C'est le contrôle qui valide l'annotation
- [ ] 3.5 **Après cette publication** : vérifier que le `?v=` servi en production a suivi. C'est du même coup le premier instrument de mesure du déploiement dont ce dépôt dispose — jusqu'ici, rien ne permettait de savoir ce qui était en ligne
- [ ] 3.6 Constater qu'une ressource statique modifiée parvient bien aux visiteurs de retour, et non depuis leur cache. C'est l'objet du dispositif, resté sans effet depuis le 23 janvier
