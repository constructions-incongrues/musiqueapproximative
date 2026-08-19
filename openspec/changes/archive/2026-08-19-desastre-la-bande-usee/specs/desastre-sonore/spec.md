## ADDED Requirements

### Requirement: Un désastre peut altérer le signal du morceau

Un désastre SHALL pouvoir traiter le signal audio du morceau en cours de lecture, et non
seulement décorer la page autour de lui.

L'altération NE SHALL PAS empêcher la lecture, ni la rendre inaudible, ni interrompre le
morceau. Un désastre est un ornement : il déforme, il ne casse pas.

Le traitement SHALL avoir lieu sur le fil de rendu audio, échantillon par échantillon. Le
faire depuis le fil principal produirait des craquements — ce qui serait entendu comme une
panne du site et non comme un geste.

Le morceau SHALL rester téléchargeable et lisible tel qu'il est publié : l'altération vaut
pour la lecture sur la page, jamais pour le fichier.

#### Scenario: un morceau lu avec le désastre appliqué

- **GIVEN** une page dont la règle a retenu la recette d'altération sonore
- **WHEN** le visiteur lance la lecture
- **THEN** le morceau est audible du début à la fin
- **AND** sa hauteur flotte au lieu d'être stable

#### Scenario: le fichier lui-même est intact

- **GIVEN** un morceau dont la lecture a été altérée sur la page
- **WHEN** le fichier est récupéré à son adresse
- **THEN** il est identique à celui qui a été publié

### Requirement: Le visiteur peut comprendre que l'altération est voulue

L'altération sonore NE SHALL PAS être servie seule. Elle SHALL être accompagnée d'au moins
un signe perceptible par un autre sens, piloté par **la même modulation** que le son.

La raison n'est pas esthétique. Un son qui flotte sans autre indice se lit comme une panne —
connexion, casque, fichier — et un visiteur qui croit le site cassé s'en va. Le désastre se
retournerait alors contre le morceau qu'il devait accompagner.

Deux signes simultanés mais indépendants NE SHALL PAS satisfaire cette exigence : ils
seraient perçus comme deux événements, non comme une cause unique. C'est le partage du
signal qui fait la démonstration.

#### Scenario: le son flotte

- **GIVEN** un morceau dont la lecture est altérée
- **WHEN** le visiteur écoute
- **THEN** au moins un élément de la page suit la même modulation, au même rythme

#### Scenario: aucune altération sonore

- **GIVEN** une page sans désastre sonore
- **WHEN** elle est consultée
- **THEN** aucun de ces signes n'est présent

### Requirement: Le visiteur qui refuse le mouvement est entendu

Les contrepoints visuels et tactiles SHALL être supprimés lorsque le navigateur signale
`prefers-reduced-motion: reduce`. L'altération sonore, elle, SHALL continuer.

Ce réglage est le seul par lequel un visiteur demande explicitement qu'on ne l'agite pas.
Aucun des dix-neuf désastres existants ne le lit ; celui-ci SHALL le lire.

Il n'existe pas de réglage standard équivalent pour le son. Une sortie SHALL donc être
fournie autrement, et documentée.

#### Scenario: un visiteur ayant demandé moins de mouvement

- **GIVEN** un navigateur signalant `prefers-reduced-motion: reduce`
- **WHEN** une page portant le désastre est servie
- **THEN** ni le titre ni la réponse au pointeur ne sont modulés
- **AND** l'altération sonore reste appliquée
