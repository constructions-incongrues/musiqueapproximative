## 1. Correction

- [x] 1.1 Déplacer la déclaration `desastre` sous `cache` dans `src/apps/frontend/config/filters.yml`, pour que l'injection précède l'écriture de l'entrée de cache
- [x] 1.2 Consigner la raison en commentaire dans le fichier : l'ordre de déclaration est contre-intuitif, la chaîne remontant en sens inverse, et rien ne signalait le piège
- [x] 1.3 Ne pas ajouter de garde dans les scripts de désastre. Elle masquerait le défaut au lieu de le corriger, et laisserait les visiteurs télécharger des ressources inertes

## 2. Correction de l'exigence

- [x] 2.1 Étendre « Requirement: Application d'une recette » : l'enrichissement fait partie de la représentation mise en cache
- [x] 2.2 Compléter le scénario « Options transmises au désastre » — les options valent pour toute réponse portant les ressources, produite ou servie depuis le cache
- [x] 2.3 Ajouter un scénario sur les consultations successives d'une adresse enrichie, qui est le cas que la mesure a pris en défaut

## 3. Vérification manuelle

- [x] 3.1 Sur une instance locale en environnement `prod`, cache vidé, demander quatre fois une adresse portant un désastre et vérifier que `window.DesastreOptions` figure dans les quatre réponses
      — mesuré le 7 août 2026 sur trois morceaux : `1,1,1,1` pour chacun, contre `1,0,0,0`
      avant le déplacement.
- [x] 3.2 Vérifier que les réponses successives restent identiques à l'octet près : le tirage est mis en cache, son effet doit l'être aussi
      — trois demandes de `/post/mannequin-moves-baby-love` : corps identiques.
- [x] 3.3 Vérifier qu'aucun autre format ne régresse — `json`, `xspf`, `max`, le flux
      — `posts`, `posts?format=json` et `posts/feed` répondent `200` avec leur type propre.
      Le `500` observé sur `xspf` vient de l'arbre de travail utilisé pour la mesure, qui
      précède le correctif de `reparer-format-xspf` ; il n'a pas de rapport avec ce
      changement.
- [ ] 3.4 **Après déploiement** : reprendre la mesure de production faite pour ce changement — deux adresses jamais servies, quatre demandes chacune — et vérifier que les options figurent partout. C'est le seul contrôle qui vaille : le défaut a été établi en production, il doit y être levé
- [ ] 3.5 Vérifier qu'un désastre s'applique visuellement à la deuxième consultation d'une page, dans un navigateur. La présence du bloc d'options ne prouve pas que le script s'exécute — c'est la même distinction qui a fait passer ce défaut inaperçu

## 4. Portée laissée ouverte

- [ ] 4.1 Décider si les scripts de désastre doivent malgré tout se garder contre l'absence d'options. Ce changement dit que la garde ne remplace pas le correctif ; il ne dit pas qu'elle est inutile. Un désastre qui échoue devrait le signaler plutôt que mourir sur une `TypeError` silencieuse
- [ ] 4.2 Relire les autres recettes de `src/web/desastres/` : `mangelettres` a servi de cas de mesure, rien ne dit que les treize autres déréférencent leurs options de la même façon, ni qu'elles échouent aussi discrètement
