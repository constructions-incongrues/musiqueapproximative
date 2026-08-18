> **L'ordre est le fond de ce change.** Les groupes 1 et 2 valent d'être menés quoi qu'il
> arrive ; le groupe 3 se discute. Réécrire l'historique sans avoir invalidé les mots de
> passe ne fait que compliquer l'accès à des données déjà copiées.

## 1. Invalider — le seul geste qui change l'exposition

- [ ] 1.1 Décider comment : forcer le renouvellement à la prochaine connexion, ou
  réinitialiser d'office. Le second protège les comptes dormants, qui sont les plus exposés
  puisque personne ne remarquera leur usage.
- [ ] 1.2 Traiter les **207 empreintes** comme connues. `sf_guard_user.algorithm` vaut
  `sha1` et le sel figure dans le même dump : ce sont des mots de passe à considérer comme
  lisibles, non comme protégés.
- [ ] 1.3 Vérifier qu'aucune de ces empreintes ne subsiste en production après l'opération.
- [ ] 1.4 Vérifier qu'aucun autre système n'accepte ces identifiants — un compte
  d'administration réemployé ailleurs annulerait le bénéfice.

## 2. Prévenir — ce qu'un dépôt ne peut pas faire à leur place

- [ ] 2.1 Écrire aux **171 personnes** dont l'adresse et l'empreinte ont été publiées. Dire
  ce qui a été exposé, depuis quand, et ce qu'elles devraient faire.
- [ ] 2.2 Recommander explicitement de changer ce mot de passe **partout où il a été
  réemployé**. C'est là que la fuite compte le plus, et le site ne peut rien y faire.
- [ ] 2.3 Ne pas minimiser dans la formulation. « Des empreintes, pas des mots de passe » est
  vrai et trompeur : du SHA1 salé sans étirement se casse.

## 3. Réécrire — ce qui arrête la propagation, et rien d'autre

- [x] 3.1 **Clone miroir complet du dépôt, et vérifier qu'il se relit.** C'est le seul retour
  arrière. Fait le 2026-08-18 depuis `origin`, l'état qui fait foi : **214 refs, 1 081
  commits, 37 Mo**, `fsck` propre.
- [ ] 3.2 Réduire ce qu'il faudra reprendre : fusionner ou fermer ce qui peut l'être parmi
  les **46 branches distantes** et la PR de release en cours.
- [x] 3.3 **Éprouvé sur une copie, et la liste de fichiers était fausse.** La proposition
  n'en nommait que deux. Le galop d'essai a montré qu'après leur retrait, **104 empreintes
  et 104 courriels subsistaient** — dans `src/data/fixtures/vagrant.dump.sql`, que le
  premier relevé avait manqué.

  Inventaire exhaustif, refait fichier par fichier sur tout l'historique :

  | fichier | empreintes | courriels |
  | --- | --- | --- |
  | `src/data/fixtures/musiqueapproximative.sql` | 197 | 171 |
  | `src/data/fixtures/net_musiqueapproximative_www.dump.sql` | 114 | 102 |
  | `src/data/fixtures/vagrant.dump.sql` | **104** | **102** |
  | `src/data/fixtures/subsonic.sql` | 0 | 2 — **fictifs**, `alice@example.net` et `bob@example.net`, RFC 2606 |
  | **union** | **207** | **173** |

  La commande à lancer porte donc sur **trois** fichiers :

  ```
  git filter-repo --force --invert-paths \
    --path src/data/fixtures/musiqueapproximative.sql \
    --path src/data/fixtures/net_musiqueapproximative_www.dump.sql \
    --path src/data/fixtures/vagrant.dump.sql
  ```
- [x] 3.4 **Vérifié sur la copie réécrite** : `0` empreinte, `0` courriel réel, **1 081
  commits conservés** — aucun n'est perdu, seuls les blobs sortent. Le pack passe de
  35,9 à 33,3 Mo.
- [ ] 3.5 Poussée forcée. **Elle déclenche le déploiement** — Plesk tire `main`. Le contenu
  final étant identique, l'effet devrait être nul ; vérifier le site après plutôt que de le
  supposer.
- [ ] 3.6 Reprendre les branches restantes sur le nouvel historique.
- [ ] 3.7 Régénérer la PR de release-please, invalidée par la réécriture.

## 4. Ce qui reste hors d'atteinte, et qu'il faut dire

- [ ] 4.1 Demander au **support GitHub** de purger les objets devenus inatteignables. Sans
  cette demande, ils restent accessibles par leur empreinte : la réécriture les aura rendus
  difficiles à trouver, pas absents.
- [ ] 4.2 Écrire aux propriétaires des **4 forks**. Ils portent l'historique complet et
  aucune réécriture ne les atteint. Ils peuvent être prévenus ; ils ne peuvent pas être
  contraints.
- [ ] 4.3 Consigner ce que ce change **n'a pas** accompli : les clones locaux déjà pris, les
  sauvegardes, et tout ce qui a été indexé. Annoncer une purge complète serait faux.

## 5. Vérification

- [ ] 5.1 Reprendre la mesure de la proposition sur le dépôt réécrit : **0 empreinte, 0
  courriel réel** attendus.
- [ ] 5.2 Vérifier que le site fonctionne après la poussée forcée.
- [ ] 5.3 Vérifier que la suite passe sur un clone neuf du dépôt réécrit — c'est ce que
  recevra la prochaine personne qui arrive.
- [ ] 5.4 `openspec validate purger-les-identifiants-de-l-historique --type change --strict`.
