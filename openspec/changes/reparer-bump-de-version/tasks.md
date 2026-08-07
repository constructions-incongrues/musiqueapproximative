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
- [x] 3.4 **À la prochaine publication** : vérifier que la pull request de release-please modifie `src/VERSION`, ce qu'elle n'a jamais fait. C'est le contrôle qui valide l'annotation
      — **la publication a eu lieu le 7 août 2026, et le contrôle passe.** La pull request
      de release #117 portait trois fichiers, `src/VERSION` compris, avec le diff attendu :
      `1.10.0` → `1.10.1`. L'annotation `x-release-please-version` produit donc bien son
      effet. Après fusion, `main` porte `1.10.1` et le tag `v1.10.1` existe.
- [x] 3.5 **Après cette publication** : vérifier que le `?v=` servi en production a suivi. C'est du même coup le premier instrument de mesure du déploiement dont ce dépôt dispose — jusqu'ici, rien ne permettait de savoir ce qui était en ligne
      — **il a suivi.** `https://www.musiqueapproximative.net/posts` sert `?v=1.10.1`,
      quinze occurrences, moins de vingt minutes après la fusion de la release. Le
      déploiement s'est fait sans intervention.
      — L'instrument fonctionne, et il a servi le jour même : c'est ce `?v=` qui a permis
      d'affirmer que le correctif XSPF de `v1.10.1` était bien en ligne avant de le
      mesurer. Le dépôt sait désormais lire ce qu'il sert.
- [x] 3.6 Constater qu'une ressource statique modifiée parvient bien aux visiteurs de retour, et non depuis leur cache. C'est l'objet du dispositif, resté sans effet depuis le 23 janvier
      — **constaté, et le constat est négatif.** Le 7 août 2026, la production servait
      `?v=1.10.2` et, sous cette adresse, un `mangelettre.js` de 2 439 octets — celui de
      `main`, portant une garde ajoutée après la publication. Le même fichier au tag
      `v1.10.2` en fait 2 048 et ne la porte pas.

      | | Taille | Garde |
      |---|---|---|
      | `v1.10.2` (le tag) | 2 048 o | non |
      | `main` | 2 439 o | oui |
      | **servi en production, sous `?v=1.10.2`** | **2 439 o** | **oui** |

      — Le contenu déployé suit `main`, le marqueur suit `src/VERSION`, et `src/VERSION` ne
      bouge qu'à la publication. Toute ressource modifiée entre deux publications arrive
      donc en ligne sous une adresse inchangée. Les en-têtes rendent la chose durable :
      `cache-control: max-age=2678400`, trente et un jours.
      — Le cas de test n'a pas été fabriqué : trois fichiers JavaScript modifiés le soir même
      par `reparer-injection-des-options` ont suffi à démentir l'hypothèse implicite du
      dispositif — que les ressources ne changent qu'aux publications.
      — **Décidé : la limite est actée plutôt que corrigée.** Le dispositif fait ce pour quoi
      il a été mesuré en 3.1 et 3.5 — le marqueur change à chaque publication. Il ne protège
      pas du cas ci-dessus, et rien dans le corpus ne le disait. C'est désormais écrit, sous
      la capacité `ressources-statiques`, scénario « Ressource modifiée hors publication ».
      — Les deux corrections envisagées et écartées : une empreinte du contenu (`?v=<md5>`),
      correcte par construction mais plus coûteuse ; un déploiement par tag plutôt que par
      `main`, qui rendrait le marqueur exact sans toucher au dispositif, mais qui est une
      décision de politique de déploiement et non de cette tâche.
