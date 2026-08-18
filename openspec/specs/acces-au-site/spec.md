# acces-au-site Specification

## Purpose

Décrit ce que le site doit à quiconque l'ouvre, avant et indépendamment de ce qu'il vient y
chercher. `catalogue-morceaux` dit ce qu'on y trouve, `formats-de-sortie` comment on le
récupère, `metadonnees-partage` comment on le partage ; cette capacité dit ce que le
visiteur peut faire de la page qu'on lui sert.

## Requirements

### Requirement: Le visiteur peut agrandir la page

Le site SHALL laisser un visiteur agrandir n'importe laquelle de ses pages, par le geste
que son terminal prévoit pour cela. Aucune déclaration du site ne SHALL empêcher ce geste
ni en borner l'amplitude.

Cette garantie SHALL tenir sans que la page se mette à l'échelle d'elle-même lorsqu'un
champ de saisie reçoit la mise au point — l'un ne s'obtient pas en supprimant l'autre.

#### Scénario : Agrandissement à la demande du visiteur

- **QUAND** un visiteur ouvre une page du site sur un terminal tactile et écarte deux
  doigts pour agrandir
- **ALORS** la page s'agrandit
- **ET** aucune déclaration du site ne limite jusqu'où

#### Scénario : Pas de mise à l'échelle non demandée

- **QUAND** un visiteur met au point le champ de recherche sur un terminal tactile
- **ALORS** la page ne se met pas à l'échelle d'elle-même
- **ET** ce qui l'en empêche est la taille de police du champ, non une interdiction faite
  au visiteur

#### Scénario : Adaptation à la largeur du terminal

- **QUAND** une page est servie sur un terminal quelconque
- **ALORS** elle s'adapte à la largeur de cet appareil
- **ET** son échelle de départ est l'échelle naturelle

### Requirement: Le visiteur sait quelle version il consulte

Le site SHALL indiquer, sur chaque page, la version qu'il sert, et SHALL offrir un lien vers
les notes de publication correspondantes.

Lorsque aucune version nommée n'est disponible — une installation servie hors publication —
le site SHALL le dire plutôt que d'afficher une version fausse, et SHALL renvoyer vers
l'ensemble des notes de publication plutôt que vers une notice inexistante.

#### Scénario : Version nommée servie

- **QUAND** un visiteur ouvre une page du site servie depuis une version publiée
- **ALORS** le numéro de cette version lui est présenté
- **ET** un lien mène aux notes de publication de cette version précise

#### Scénario : Installation sans version nommée

- **QUAND** le site est servi sans version publiée
- **ALORS** il l'indique comme telle au lieu d'afficher un numéro
- **ET** le lien mène à l'ensemble des notes de publication, non à une notice qui n'existe
  pas
