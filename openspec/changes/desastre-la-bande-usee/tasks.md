## 1. Le processeur

- [x] 1.1 Écrire le processeur `AudioWorklet` : ligne à retard dont la longueur est modulée
      par deux oscillations superposées — lente pour le *wow*, rapide pour le *flutter*.
- [x] 1.2 Interpoler entre échantillons dans la ligne à retard. Sans interpolation, la
      modulation crépite, et un craquement se lit comme une panne et non comme un geste.
- [x] 1.3 Exposer l'intensité en `AudioParam`, pour qu'elle puisse s'automatiser à la
      fréquence audio — les stories 35 et 36 en dépendront.
- [x] 1.4 Émettre la valeur du modulateur sur le `MessagePort`, à une cadence utilisable par
      l'affichage sans le saturer.

## 2. Le branchement

- [x] 2.1 Charger le processeur par `audioWorklet.addModule()` depuis le script de la
      recette, qui est chargé en `<script src>` classique.
- [x] 2.2 Brancher `createMediaElementSource()` sur l'élément audio de jPlayer, en attendant
      qu'il existe : il est créé à l'exécution et non présent dans le HTML servi.
- [x] 2.3 Démarrer au premier geste du visiteur, `AudioContext` naissant suspendu. Le clic
      de lecture est le moment juste.
- [x] 2.4 Vérifier que la lecture reste possible de bout en bout, et que le son n'est jamais
      coupé si le worklet échoue à se charger. Un désastre est un ornement.

## 3. Les deux contrepoints

- [x] 3.1 Déformer le titre du morceau depuis la valeur reçue par le `MessagePort`, et non
      par une animation réglée sur les mêmes fréquences. C'est le partage du signal qui fait
      la démonstration, pas la ressemblance.
- [x] 3.2 Viser `article h2` pour le titre. Un `h1` nu attraperait la barre latérale — la
      page en compte trois — et `.title` est le nom du site.
- [x] 3.3 Retarder la réponse de la page au pointeur avec la même valeur : survols,
      contrôles, liens. Ne pas toucher au curseur lui-même.
- [x] 3.4 Vérifier que les clics restent exacts. Une réponse molle est un désastre ; un clic
      qui rate est un bug.

## 4. La sortie

- [x] 4.1 Supprimer les deux contrepoints sous `prefers-reduced-motion: reduce`, en laissant
      l'altération sonore. Aucun des dix-neuf ne lit ce signal ; celui-ci le lit.
- [x] 4.2 Fournir une sortie pour l'altération sonore elle-même, qui n'a aucun réglage
      standard pour être refusée, et la documenter. Sans elle, ce serait le seul désastre du
      catalogue auquel on ne peut pas échapper.

## 5. La recette et la règle

- [x] 5.1 Écrire la recette et la règle, déclarer les deux imports.
- [x] 5.2 Poser une probabilité basse, et écrire pourquoi : on dégrade la musique d'un
      contributeur.
- [x] 5.3 Donner un paramètre de déclenchement, comme les autres, pour pouvoir l'essayer.

## 6. Vérifier

- [ ] 6.1 Constater à l'oreille que l'effet est perceptible sans être identifiable au
      premier instant. **Moitié objectivée, moitié en attente.**
      Mesuré par rendu hors ligne et passages par zéro sur un la 440 : **±14,7 cents, 29,3
      cents d'amplitude** — dans la plage d'une bande réelle (10–50) et près de six fois le
      seuil de perception (~5). Un premier jeu de réglages donnait **154 cents**, plus d'un
      demi-ton : une machine cassée et non une bande fatiguée. Corrigé, et la raison est
      écrite dans le processeur — la déviation dépend de la DÉRIVÉE du retard, donc de la
      fréquence autant que de la profondeur, ce qui rend le flutter bien plus lourd que le
      wow à profondeur égale.
      **Reste au mainteneur** : écouter. Aucune mesure ne dira si le doute dure les quelques
      secondes qu'il faut.
- [x] 6.2 Vérifier que le fichier du morceau reste identique à son adresse : l'altération
      vaut pour la lecture, jamais pour le fichier.
- [x] 6.3 Vérifier que le tirage, l'invariance et l'en-tête `X-Desastre` sont inchangés.
- [ ] 6.4 Vérifier que le relevé de la story 29 compte ce désastre comme les autres, et
      distingue une application forcée d'un tirage.

## 7. Clore

- [x] 7.1 Documenter le désastre dans la page de référence, sortie de secours comprise.
- [x] 7.2 Cocher la story 33 dans `openspec/discovery.md` et y déclarer ce change.
- [x] 7.3 Corriger dans le plan l'affirmation selon laquelle `mangelettres` vise le titre du
      morceau : il vise `.title`, qui est le nom du site.
