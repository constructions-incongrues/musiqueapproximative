# desastre-sonore Specification

## Purpose

Décrit l'altération volontaire du **signal sonore** d'un morceau — et non de la page qui
l'entoure, qui relève de `desastres`. Dit ce que l'altération doit préserver (le morceau
reste audible, le fichier reste intact), ce qui la rend lisible comme un geste plutôt que
comme une panne, ce qui en règle l'intensité — l'âge du morceau et les écoutes de ce
visiteur — et par où l'on y échappe.

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

Cette sortie SHALL être découvrable **depuis le site lui-même**, et non seulement depuis la
documentation technique. Une dérogation qu'un visiteur ne peut pas trouver est, de son point
de vue, une règle sans dérogation : la sortie existe alors pour le mainteneur et pour
personne d'autre.

Cette sortie SHALL également suspendre le décompte des écoutes. Un visiteur qui refuse le
désastre n'a pas à voir son écoute enregistrée pour autant, et retrouverait sinon, en le
réactivant, une usure accumulée pendant qu'il l'avait refusé.

#### Scenario: un visiteur ayant demandé moins de mouvement

- **GIVEN** un navigateur signalant `prefers-reduced-motion: reduce`
- **WHEN** une page portant le désastre est servie
- **THEN** ni le titre ni la réponse au pointeur ne sont modulés
- **AND** l'altération sonore reste appliquée

#### Scenario: la sortie est trouvable depuis le site

- **GIVEN** un visiteur qui subit l'altération sonore et veut y échapper
- **WHEN** il cherche dans les pages du site comment faire
- **THEN** il y trouve la sortie décrite, sans avoir à lire la documentation technique ni le
  code de la recette

#### Scenario: la sortie suspend aussi la mémoire

- **GIVEN** un morceau déjà usé par ce navigateur
- **WHEN** le visiteur demande la sortie et écoute le morceau plusieurs fois
- **THEN** le morceau est servi sans altération
- **AND** l'usure enregistrée n'a pas augmenté

### Requirement: L'intensité de l'altération suit l'âge du morceau

À écoutes égales, l'altération sonore SHALL être d'autant plus marquée que le morceau est
ancien. Un morceau publié le jour même et jamais écouté SHALL être presque net ; un morceau
de dix-huit ans SHALL porter l'altération que son âge lui vaut.

L'âge n'est plus la seule source d'altération : l'usure due aux écoutes de ce visiteur s'y
ajoute, sous l'exigence « L'usure s'accroît avec les écoutes de ce visiteur ». Les
comparaisons d'âge énoncées ici valent donc **entre morceaux également écoutés**.

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

- **GIVEN** un morceau publié il y a quelques semaines, jamais écouté par ce navigateur
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est perceptible mais minimale

#### Scenario: un morceau des débuts

- **GIVEN** un morceau publié il y a dix-huit ans, jamais écouté par ce navigateur
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est celle que son âge lui vaut, et elle est marquée

#### Scenario: deux morceaux d'âges différents

- **GIVEN** deux morceaux dont les dates de publication diffèrent de plusieurs années, et
  que ce navigateur a écoutés le même nombre de fois
- **WHEN** le désastre est appliqué à chacun
- **THEN** le plus ancien porte l'altération la plus marquée

### Requirement: L'usure s'accroît avec les écoutes de ce visiteur

L'altération SHALL être d'autant plus marquée que **ce navigateur** a écouté ce morceau
souvent. La dixième écoute SHALL porter une altération plus marquée que la première.

Cet accroissement SHALL s'ajouter à celui que produit l'âge du morceau, sans le remplacer :
un morceau ancien jamais écouté reste altéré par son âge, un morceau récent beaucoup écouté
s'altère malgré sa jeunesse.

Le décompte SHALL porter sur le morceau, et non sur le site : user une bande n'use que
celle-là. Deux morceaux écoutés séparément SHALL porter deux usures indépendantes.

Une écoute SHALL être comptée lorsque la lecture démarre effectivement, et non à
l'affichage de la page. Une page ouverte puis quittée sans lecture n'use rien — ce serait
compter des visites et non des écoutes, ce que le geste ne prétend pas faire.

#### Scenario: une première écoute et une dixième

- **GIVEN** un morceau portant le désastre, jamais écouté par ce navigateur
- **WHEN** le visiteur l'écoute une première fois, puis y revient dix fois
- **THEN** l'altération de la dixième écoute est plus marquée que celle de la première

#### Scenario: deux morceaux écoutés inégalement

- **GIVEN** deux morceaux de même date de publication, portant tous deux le désastre
- **WHEN** le visiteur en écoute un dix fois et l'autre une fois
- **THEN** le premier porte l'altération la plus marquée

#### Scenario: une page ouverte sans écoute

- **GIVEN** un morceau portant le désastre
- **WHEN** le visiteur ouvre la page plusieurs fois sans jamais lancer la lecture
- **THEN** l'altération reste celle de la première visite

### Requirement: L'usure ne rend jamais le morceau inécoutable

L'usure cumulée SHALL être bornée par un plafond. Passé ce plafond, écouter davantage
n'aggrave plus l'altération.

Ce plafond SHALL laisser le morceau audible et reconnaissable, conformément à l'exigence
« Un désastre peut altérer le signal du morceau » : un désastre déforme, il ne casse pas.

Sans cette borne, le désastre punirait exactement les visiteurs qui reviennent le plus, en
leur rendant définitivement pénible le morceau qu'ils écoutent le plus. Ce serait retourner
le geste contre ceux qu'il vise.

#### Scenario: un morceau écouté un très grand nombre de fois

- **GIVEN** un morceau que ce navigateur a écouté bien au-delà du plafond
- **WHEN** le visiteur l'écoute encore
- **THEN** le morceau reste audible du début à la fin
- **AND** l'altération n'est pas plus marquée qu'au plafond

### Requirement: L'usure s'oublie

L'usure accumulée SHALL décroître avec le temps écoulé depuis la dernière écoute, jusqu'à
disparaître.

Une bande magnétique ne guérit pas ; le site n'est pas une bande magnétique. Une usure
définitive ferait du désastre une sanction permanente infligée à l'habitué, alors qu'il est
un ornement. L'oubli est ce qui permet de revenir à un morceau des mois plus tard et de le
retrouver tel qu'il était.

La décroissance SHALL être assez lente pour qu'une série d'écoutes rapprochées produise un
cumul perceptible — sans quoi l'usure ne s'accumulerait jamais et l'exigence précédente
serait vide.

#### Scenario: un morceau délaissé longtemps

- **GIVEN** un morceau que ce navigateur a beaucoup écouté, puis laissé de côté longtemps
- **WHEN** le visiteur y revient
- **THEN** l'altération est moins marquée qu'à sa dernière écoute

#### Scenario: des écoutes rapprochées

- **GIVEN** un morceau portant le désastre
- **WHEN** le visiteur l'écoute plusieurs fois dans la même session
- **THEN** l'altération s'accroît d'une écoute à l'autre

### Requirement: La mémoire de l'usure ne quitte pas le navigateur

Le décompte des écoutes SHALL être conservé par le navigateur du visiteur, et NE SHALL PAS
être transmis au site, ni corrélé entre appareils, ni rattaché à une identité.

Le document servi NE SHALL PAS varier selon cette mémoire. Une page tirée et mise en cache
sert un corps identique à chaque requête — exigence « L'invariance est préservée » de la
capacité `desastres` — et une usure calculée au service la romprait.

Un visiteur qui efface le stockage de son navigateur SHALL retrouver le morceau neuf. Ce
n'est pas une perte : c'est la contrepartie de n'avoir rien confié au site.

#### Scenario: le site ne sait rien de l'usure

- **GIVEN** un morceau que ce navigateur a beaucoup écouté
- **WHEN** la page est demandée à nouveau
- **THEN** le corps du document servi est identique à celui servi au premier visiteur
- **AND** aucune requête ne porte le décompte des écoutes vers le site

#### Scenario: un autre navigateur

- **GIVEN** un morceau usé sur un premier navigateur
- **WHEN** le même morceau est ouvert depuis un second navigateur
- **THEN** il y est neuf

#### Scenario: un stockage effacé

- **GIVEN** un morceau usé par ce navigateur
- **WHEN** le visiteur efface le stockage du site
- **THEN** le morceau est de nouveau altéré comme à la première écoute

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
