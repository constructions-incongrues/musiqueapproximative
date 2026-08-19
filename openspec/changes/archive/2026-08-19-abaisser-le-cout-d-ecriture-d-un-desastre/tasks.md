## 1. Écrire le verdict

- [x] 1.1 Consigner dans `docs/modules/ROOT/pages/desastres.adoc` le coût mesuré d'un
      désastre complet : cinq fichiers, quatre répertoires, deux lignes d'import, deux
      schémas JSON.
- [x] 1.2 Consigner la fréquence mesurée — dix-neuf recettes sur les 9 et 10 novembre 2025,
      par pull requests distinctes et non par import massif ; aucun désastre neuf depuis —
      et la décision qui en découle : on n'outille pas.
- [x] 1.3 Écrire à quelle condition la décision se réviserait, plutôt que de la poser comme
      définitive. Une décision sans sa condition de révision se conteste au jugé, ce que ce
      relevé existe pour éviter.

## 2. Supprimer la carte fausse

- [x] 2.1 Vérifier, avant de supprimer quoi que ce soit, ce que le README du plugin porte
      et que la page ne porte PAS. **Cette tâche a rattrapé le plan** : le README porte la
      référence d'API PHP et l'appel depuis du code, absents de la page. Le réduire à un
      renvoi les aurait détruits. Sept méthodes documentées, deux écarts seulement — un
      paramètre `sfContext` ajouté après coup — corrigés plutôt que supprimés. Supprimer d'abord et constater la perte
      ensuite serait l'inverse de la méthode suivie toute cette release.
- [x] 2.2 Reverser dans la page de documentation ce qui n'y figure pas et mérite d'y être.
- [x] 2.3 Réduire `src/plugins/sfDesastrePlugin/README.adoc` à un renvoi court vers la page
      qui fait foi, en disant pourquoi il ne décrit plus la structure — sans quoi quelqu'un
      la réécrira ici de bonne foi.

## 3. Vérifier

- [x] 3.1 Vérifier qu'aucune description concurrente ne subsiste. **Il n'y avait pas deux
      cartes, il y en avait plus** : `CONFIGURATION-SEGMENTATION.adoc` (409 lignes,
      document de conception à trois approches dont on ne savait pas laquelle avait
      atterri) et `README-TRIGGER.adoc` (374 lignes). Le premier est marqué historique en
      nommant l'approche retenue ; le second est juste et complète la page. Vérifié après
      coup : une seule section « comment créer », dans la page qui fait foi.
- [x] 3.2 Vérifier que la page qui fait foi décrit bien l'arborescence réelle — `recettes/`,
      `regles/`, imports — en la confrontant à la configuration, pas en la relisant.
- [x] 3.3 Vérifier que la documentation se construit toujours et que la navigation est
      complète.

## 4. Clore

- [x] 4.1 Cocher la story 32 dans `openspec/discovery.md` et y déclarer ce change.
- [x] 4.2 Consigner dans le plan que le packet demandait une mesure, que la mesure a dit de
      ne pas outiller, et qu'elle a trouvé un défaut que le packet n'avait pas prévu — une
      carte fausse posée à côté du code depuis neuf mois.
