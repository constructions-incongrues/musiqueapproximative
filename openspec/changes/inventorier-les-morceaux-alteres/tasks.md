# Tâches

Pas de `specs/` : `skip_specs` est déclaré. Ce change ne modifie aucun comportement
observable du site — il produit un document et pose une question. Pas de `design.md` non
plus, pour la même raison.

## 1. Mesurer, sur la production et non en local

- [x] 1.1 Prendre les données du **catalogue public**, non d'un accès à la base : le relevé
  doit être refaisable par quiconque, et la règle du plan veut que toute mesure vienne du
  site en ligne.
- [x] 1.2 Retenir un morceau lorsqu'un point d'interrogation est **collé à une lettre** ou
  **répété**. Un « ? » isolé en fin de phrase est une ponctuation légitime.
- [x] 1.3 Relire la liste entière plutôt que faire confiance à l'expression régulière.
  Résultat : la détection est juste, à deux ou trois entrées près qui peuvent être de la
  ponctuation volontaire. **Consigné comme incertitude dans la page**, pas masqué.

## 2. Ce que la mesure a donné

- [x] 2.1 **85 morceaux sur 8 098** (1,05 %), **37 contributeurs**, du 2008-06-19 au
  2026-07-27.
- [x] 2.2 Séparer les deux populations : **23 depuis 2022** chez 9 contributeurs, **62
  avant**. Le plan demandait cette distinction ; elle change ce qu'on peut espérer.
- [x] 2.3 Vérifier la question ouverte de la story — une sauvegarde aide-t-elle ? **Non**,
  et la page le dit en encadré : la destruction a lieu à l'écriture, toute sauvegarde porte
  les mêmes points d'interrogation.

## 3. Deux vérifications qui accompagnent le relevé

- [x] 3.1 **Aucun morceau du catalogue ne porte de caractère hors cp1252.** Zéro sur 8 098,
  sur dix-huit ans. Rien n'a jamais survécu — ce qui confirme que le dommage était total et
  non partiel.
- [x] 3.2 Conséquence à ne pas taire : **la production n'a pas encore démontré que la
  migration fonctionne**, aucun morceau posté depuis n'ayant demandé un tel caractère. La
  preuve est dans la suite de tests, pas en ligne. Écrit dans la page.

## 4. La page

- [x] 4.1 Écrire `docs/modules/ROOT/pages/morceaux-alteres.adoc`.
- [x] 4.2 Y porter la méthode, avec la commande, pour que le relevé soit refaisable.
- [x] 4.3 Y porter les **trois questions** posées aux contributeurs, dont la troisième :
  « je ne sais plus » est une réponse utile, elle ferme la question au lieu de la laisser
  ouverte.
- [x] 4.4 Y écrire ce qui **n'est pas fait** : le marquage des morceaux comme altérés.
- [x] 4.5 Inscrire la page à `nav.adoc`. Contrôle de navigation : **17 entrées, aucune
  orpheline.**

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **Écrire aux 37 contributeurs.** La page porte la demande et l'adresse ; l'envoi
  appartient au collectif.
- [ ] 5.2 **Marquer les morceaux altérés** dans l'interface, si la conversation ne rend
  rien. À ne pas faire avant, sinon on marque comme perdu ce qui allait être retrouvé.

### Vérification manuelle — après la mise en ligne

- [ ] 5.3 Ouvrir la page publiée et vérifier que les deux tableaux se rendent — 23 lignes
  puis 62. Les titres contiennent des caractères qui ont déjà fait tomber des rendus.
- [ ] 5.4 Vérifier qu'elle est atteignable depuis la navigation, sous « Déployer et
  exploiter ».
