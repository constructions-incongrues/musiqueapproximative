# Tâches

Pas de `design.md` : ni schéma, ni module, ni dépendance nouvelle. Un fichier converti, une
entrée de navigation, une ligne de renvoi. La règle du dépôt réserve cet artefact aux
changements structurants.

## 1. Mesurer avant de corriger

- [x] 1.1 Reproduire la construction Antora en local. Antora 3.1.14 refuse un worktree Git
  lié : `docs/` est rejoué dans un dépôt jetable, avec un playbook sur `branches: HEAD`.
- [x] 1.2 Sur l'état de départ, **une seule** erreur de référence croisée :
  `deploiement.adoc | target of xref not found: API_SUBSONIC.adoc`.
- [x] 1.3 **`README.adoc:112` n'en produit aucune.** L'exemple `xref:autre-page.adoc[]` est
  déjà enfermé dans un bloc `[source,asciidoc]` délimité par cinq tirets (lignes 83 à 115) —
  le tiret supplémentaire lui permet de contenir un bloc `----` sans se refermer. La page
  publiée affiche `xref:autre-page.adoc[Lien vers une autre page]` en toutes lettres et ne
  porte aucun lien vers `autre-page`. Le relevé du change précédent était une supposition ;
  la construction l'a démentie. **Rien à corriger : le fichier n'est pas touché.**
- [x] 1.4 Constater que la surface Subsonic est livrée, et non prévue :
  `src/apps/frontend/modules/rest/`, les routes `/rest/:method` et `/rest/:method.view` de
  `routing.yml`, la tâche `musiqueapproximative:scan-tracks`. Sa documentation est donc de
  la documentation d'usage, pas une note de projet.

## 2. La page

- [x] 2.1 Convertir `docs/API_SUBSONIC.md` en
  `docs/modules/ROOT/pages/api-subsonic.adoc` : titre et `:description:` comme les seize
  autres pages, tableau AsciiDoc, listes, blocs `[source,bash]` et `[source,sql]`, liste
  ordonnée à continuation pour l'`ALTER TABLE` de l'étape 1.
- [x] 2.2 **Aucune phrase réécrite.** Sections, ordre, exemples et formulations sont ceux du
  Markdown. Ce qui y était périmé y reste, et est nommé en « Hors périmètre ».
- [x] 2.3 Retirer `docs/API_SUBSONIC.md`, dont le contenu est désormais servi.

## 3. L'accès

- [x] 3.1 Inscrire la page à `docs/modules/ROOT/nav.adoc`, sous « Déployer et exploiter »,
  après `migration-utf8mb4` — c'est la migration qu'elle détaille.
- [x] 3.2 Faire pointer `deploiement.adoc:36` sur `xref:api-subsonic.adoc[]`. La mention
  « ou `docs/API_SUBSONIC.md` », qui compensait le lien mort, disparaît avec lui.

## 4. Vérification

- [x] 4.1 Construction Antora sur l'état corrigé : **plus aucune erreur de référence
  croisée**, et l'avertissement `Could not resolve nav entry` a disparu avec `nav.adoc`.
  Restent quatre journaux, tous antérieurs à ce change et hors de son périmètre : trois sur
  `migration-utf8mb4.adoc` (tables mal fermées, lignes 236, 253, 255) et un attribut
  `database_name_test` absent dans `developpement/environnement.adoc`.
- [x] 4.2 Le site se génère : `api-subsonic.html` est écrit, avec son tableau de
  configuration, sa liste ordonnée de déploiement et ses blocs SQL et bash.
- [x] 4.3 Le renvoi aboutit : `deploiement.html` porte
  `<a href="api-subsonic.html" class="xref page">API Subsonic</a>`.
- [x] 4.4 La page est atteignable depuis la barre latérale, qui porte `API Subsonic` sur
  chacune des pages générées.
- [x] 4.5 Le contrôle d'exhaustivité de `ci.yml` passe : **17 pages, 17 entrées de
  navigation, 0 manquante.**
- [x] 4.6 `openspec validate --changes` : 2 passés, 0 échoué.

### Vérification manuelle — après la mise en ligne

- [x] 4.7 Site publié vérifié — `api-subsonic.html` répond **200**. Attendu :
  « API Subsonic » dans la barre latérale, sous « Déployer et exploiter ».
- [x] 4.8 Le renvoi de `deploiement.html` pointe sur `href="api-subsonic.html"`, page qui
  répond. Le lien cassé est réparé. Ouvrir la page de déploiement et suivre le renvoi
  suivantes ». Attendu : la page Subsonic, et non une 404.
- [ ] 4.9 Sur cette page, vérifier que le tableau de configuration d'un client est bien
  rendu en tableau, et l'`ALTER TABLE` en bloc SQL sous l'étape 1 de la liste numérotée.
