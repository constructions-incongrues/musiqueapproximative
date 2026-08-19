## 1. Retirer le blocage

- [x] 1.1 Garder la lecture dans `sfGuardUser::getDisplayName()` pour que la relation
      `UserProfile` absente — `null` par jointure, objet vide par chargement paresseux —
      soit traitée sans avertissement, et que la retombée sur `username` reste le
      comportement servi. **Deux sites, pas un** : `Post::toJson()` lisait aussi
      `UserProfile->website_url`, d'où un accesseur `getWebsiteUrl()` sur la même
      classe plutôt qu'un garde recopié à chaque appelant.
- [x] 1.2 Consigner en commentaire, à l'endroit du `leftJoin` dans `PostTable`, que la
      jointure hydrate la relation absente à `null` là où le chargement paresseux
      fabriquait un objet vide — c'est ce que PHP 7.4 masque, et la prochaine personne
      qui touchera la requête doit le lire là plutôt que de le redécouvrir sous PHP 8.
- [x] 1.3 Vérifier que la suite `frontend` passe entièrement sous PHP 8.1, cache isolé,
      et que les 64 échecs mesurés tombent à zéro.
- [x] 1.4 Vérifier que le témoin PHP 7.4 reste vert : le correctif doit valoir sur les
      deux versions, pas déplacer la casse.

## 2. Couvrir le défaut par un test

- [x] 2.1 Ajouter un test unitaire qui construit un contributeur sans profil et vérifie
      que son nom d'affichage est servi sans erreur. Il doit échouer sur le code
      d'avant le correctif. **Corrigé à l'implémentation** : le packet prévoyait un
      test rouge seulement sous PHP 8. En piégeant le gestionnaire d'erreurs, la
      notice de 7.4 est capturable elle aussi — le test est rouge sur les DEUX
      versions, et le défaut devient rattrapable sur l'interpréteur du projet sans
      attendre une passe PHP 8. Vérifié par retrait temporaire du garde.
- [x] 2.2 Ne pas assertir sur la forme de l'absence (`null` ou objet vide) : elle dépend
      de la requête employée, et un test qui la fige casserait à la prochaine
      optimisation de jointure.

## 3. Rendre le verdict reproductible

- [x] 3.1 Ajouter à l'intégration continue l'exécution de la suite sous PHP 8.1, en plus
      de PHP 7.4, avec le même statut bloquant pour les deux. **Corrigé à
      l'implémentation** : la CI n'exécutait AUCUN test — `validate` faisait la
      navigation de la doc, `composer install` et `php -l`. Il n'y avait donc pas de
      passe 7.4 à laquelle ajouter une passe 8.1 ; les deux ont été créées, avec un
      service MariaDB 10.11 et la préparation de la base de test.
- [x] 3.2 S'assurer que le travail isole `cache/` et `log/`. **Aucun mécanisme n'a été
      ajouté** : chaque branche de la matrice part d'un dépôt fraîchement cloné et ces
      deux répertoires sont gitignorés — l'isolation est acquise par construction. La
      raison est consignée dans le workflow, pour qui reproduira ces tests hors CI.
- [x] 3.3 Vérifier le contrôle par l'échec avant de le déclarer bon : réintroduire
      temporairement la lecture fautive, constater que la passe 8.1 échoue en nommant le
      fichier et la ligne pendant que la 7.4 reste verte, puis la retirer.

## 4. Écrire le verdict et sa portée

- [x] 4.1 Documenter le verdict : atteignable, la version exacte mesurée, la date, et le
      détail des trois passes (syntaxe, suppressions, exécution).
- [x] 4.2 Écrire ce que la mesure ne couvre pas — le code sans test, les ruptures
      silencieuses comme la comparaison chaîne/nombre de PHP 8.0, et le silence sur 8.3
      et 8.4. Sans cette section, « la suite passe » se lira « la migration est sûre ».
- [x] 4.3 Consigner les deux fausses pistes écartées — le cache d'autoload partagé et la
      comparaison des extensions — parce que chacune aurait produit un verdict faux, et
      que la prochaine mesure les rencontrera.
- [x] 4.4 Rattacher la page à la navigation de la documentation, faute de quoi le
      contrôle de complétude la refusera.

## 5. Clore la story

- [x] 5.1 Cocher la story 28 dans `openspec/discovery.md` et y déclarer ce change.
- [x] 5.2 Consigner dans le plan que la story annonçait un inventaire et que la mesure a
      rendu un verdict plus un défaut introduit la veille — cinquième packet de cette
      release corrigé par ce qui est exécutable plutôt que par ce qui est lisible.
