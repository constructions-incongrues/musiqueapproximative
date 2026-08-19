# desastre-sonore Specification

## Purpose
TBD - created by archiving change desastre-la-bande-usee. Update Purpose after archive.

## Requirements

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

### Requirement: L'intensité de l'altération suit l'âge du morceau

L'altération sonore SHALL être d'autant plus marquée que le morceau est ancien. Un morceau
publié le jour même SHALL être presque net ; un morceau de dix-huit ans SHALL porter
l'altération maximale.

La courbe SHALL être choisie sur la distribution réelle du catalogue et non sur sa forme
mathématique. Une courbe linéaire sur dix-huit ans placerait 43 % des morceaux dans la
bande la plus altérée, ce qui ferait de l'usure l'état normal plutôt que la marque d'un âge.

Un **plancher** SHALL être appliqué : aucun morceau ne SHALL recevoir une altération
inaudible alors que la réponse annonce le désastre par son en-tête. Un désastre déclaré et
imperceptible se lit comme un désastre cassé.

La référence d'âge maximal SHALL être une constante et NE SHALL PAS être calculée depuis
l'étendue du catalogue. Cette étendue grandit chaque jour : l'usure d'un morceau donné
changerait alors sans que personne ne l'ait décidé.

#### Scenario: un morceau récent

- **GIVEN** un morceau publié il y a quelques semaines
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est perceptible mais minimale

#### Scenario: un morceau des débuts

- **GIVEN** un morceau publié il y a dix-huit ans
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est maximale

#### Scenario: deux morceaux d'âges différents

- **GIVEN** deux morceaux dont les dates de publication diffèrent de plusieurs années
- **WHEN** le désastre est appliqué à chacun
- **THEN** le plus ancien porte l'altération la plus marquée

### Requirement: Le site dit quel âge il donne au morceau

La page d'un morceau SHALL porter sa date de publication sous une forme lisible par une
machine.

Elle n'y figure aujourd'hui nulle part, ce qui est notable pour un catalogue couvrant
dix-huit ans : la date existe en base, elle est servie dans les représentations machine,
mais la page HTML n'en dit rien.

Cette date NE SHALL PAS être réservée au désastre : elle décrit le morceau, et quiconque
lit la page doit pouvoir la retrouver.

#### Scenario: lire la date depuis la page

- **GIVEN** la page d'un morceau publié
- **WHEN** on en lit le contenu
- **THEN** la date de publication y est présente sous forme normalisée

#### Scenario: le désastre s'en sert

- **GIVEN** une page dont le désastre sonore est appliqué
- **WHEN** l'altération est calculée
- **THEN** elle l'est depuis cette date, et non depuis une valeur codée dans la recette
