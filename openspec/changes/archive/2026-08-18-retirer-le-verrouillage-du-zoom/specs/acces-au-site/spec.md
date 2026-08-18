## ADDED Requirements

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
