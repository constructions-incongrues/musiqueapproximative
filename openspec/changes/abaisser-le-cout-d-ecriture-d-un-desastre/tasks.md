## 1. Écrire le verdict

- [ ] 1.1 Consigner dans `docs/modules/ROOT/pages/desastres.adoc` le coût mesuré d'un
      désastre complet : cinq fichiers, quatre répertoires, deux lignes d'import, deux
      schémas JSON.
- [ ] 1.2 Consigner la fréquence mesurée — dix-neuf recettes sur les 9 et 10 novembre 2025,
      par pull requests distinctes et non par import massif ; aucun désastre neuf depuis —
      et la décision qui en découle : on n'outille pas.
- [ ] 1.3 Écrire à quelle condition la décision se réviserait, plutôt que de la poser comme
      définitive. Une décision sans sa condition de révision se conteste au jugé, ce que ce
      relevé existe pour éviter.

## 2. Supprimer la carte fausse

- [ ] 2.1 Vérifier, avant de supprimer quoi que ce soit, ce que le README du plugin porte
      et que la page de documentation ne porte PAS. Supprimer d'abord et constater la perte
      ensuite serait l'inverse de la méthode suivie toute cette release.
- [ ] 2.2 Reverser dans la page de documentation ce qui n'y figure pas et mérite d'y être.
- [ ] 2.3 Réduire `src/plugins/sfDesastrePlugin/README.adoc` à un renvoi court vers la page
      qui fait foi, en disant pourquoi il ne décrit plus la structure — sans quoi quelqu'un
      la réécrira ici de bonne foi.

## 3. Vérifier

- [ ] 3.1 Vérifier qu'aucune description concurrente de la structure ne subsiste ailleurs
      dans le dépôt : le défaut corrigé est un doublon, et un doublon se recrée là où on
      n'a pas regardé.
- [ ] 3.2 Vérifier que la page qui fait foi décrit bien l'arborescence réelle — `recettes/`,
      `regles/`, imports — en la confrontant à la configuration, pas en la relisant.
- [ ] 3.3 Vérifier que la documentation se construit toujours et que la navigation est
      complète.

## 4. Clore

- [ ] 4.1 Cocher la story 32 dans `openspec/discovery.md` et y déclarer ce change.
- [ ] 4.2 Consigner dans le plan que le packet demandait une mesure, que la mesure a dit de
      ne pas outiller, et qu'elle a trouvé un défaut que le packet n'avait pas prévu — une
      carte fausse posée à côté du code depuis neuf mois.
