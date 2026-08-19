## 1. Enregistrer le tirage

- [ ] 1.1 Écrire une ligne de relevé au point de tirage dans `sfDesastreManager`, portant
      la recette retenue, la règle évaluée et l'horodatage.
- [ ] 1.2 Enregistrer aussi l'évaluation qui ne retient RIEN. Une règle qui ne se déclenche
      jamais et une règle jamais évaluée sont deux situations différentes ; un relevé qui
      ne garde que les succès les confond, et c'est la question même que pose cette story.
- [ ] 1.3 Diriger ces lignes vers un journal dédié sous `log/`, distinct du journal
      applicatif, pour qu'un dénombrement n'ait pas à filtrer le reste.
- [ ] 1.4 Vérifier que rien n'est écrit quand la page est servie depuis le cache — c'est la
      contrepartie directe du choix de compter les tirages, et elle doit être constatée et
      non supposée.

## 2. Nommer le désastre dans la réponse

- [ ] 2.1 Poser un en-tête nommant la recette appliquée, pendant la production de la page.
- [ ] 2.2 Déclarer explicitement l'absence de désastre plutôt que d'omettre l'en-tête : un
      en-tête absent ne distingue pas « aucun désastre » de « en-tête cassé ».
- [ ] 2.3 Vérifier que l'en-tête est resservi à l'identique sur un succès de cache. Le
      mécanisme est connu — la réponse entière est sérialisée — mais c'est une lecture de
      code, pas une mesure, et cette distinction a coûté cher aujourd'hui.

## 3. Lire le relevé

- [ ] 3.1 Ajouter une tâche symfony qui dénombre les tirages par recette sur le journal.
- [ ] 3.2 Faire apparaître les recettes déclarées mais jamais tirées, avec un décompte nul.
      La tâche doit donc croiser le journal avec la configuration, et non se contenter de
      grouper ce qu'elle lit.
- [ ] 3.3 Écrire la portée du chiffre dans la sortie même de la tâche : ce sont des
      tirages, pas des visiteurs, et le rapport entre les deux n'est pas connu.

## 4. Ne pas casser l'invariance

- [ ] 4.1 Vérifier que `desastreInvarianceTest` reste vert.
- [ ] 4.2 Ajouter la couverture qui manque : l'invariance de l'EN-TÊTE, que le test
      existant ne regarde pas.
- [ ] 4.3 Vérifier qu'aucune configuration de cache n'a été modifiée — ni `cache.yml`, ni
      une désactivation locale. C'est la façon la plus tentante de se simplifier la
      collecte, et celle qui détruirait ce qu'on mesure.

## 5. Documenter et clore

- [ ] 5.1 Documenter le relevé : ce qu'il compte, ce qu'il ne compte pas, comment le lire,
      et le fait que les journaux tournent — l'historique n'est pas éternel.
- [ ] 5.2 Rattacher la page à la navigation de la documentation.
- [ ] 5.3 Cocher la story 29 dans `openspec/discovery.md` et y déclarer ce change.
- [ ] 5.4 Consigner dans le plan l'inventaire mesuré — 19 recettes, 19 règles, aucune
      référence en l'air — car les stories 30 et 31 ont été écrites en supposant le
      contraire, et devront être relues à cette lumière.
