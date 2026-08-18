## ADDED Requirements

### Requirement: Représentation d'une erreur

Lorsqu'une demande faite dans un format machine n'aboutit pas, le système SHALL servir
l'échec **dans ce format**, et non dans la représentation HTML habituelle. Le corps SHALL
décrire l'erreur de façon analysable, et le type de contenu SHALL être celui du format
demandé.

Aucune demande d'une ressource absente ou mal désignée SHALL produire une erreur
d'exécution : une ressource qui n'existe pas est une réponse, pas une panne.

Les surfaces régies par une spécification propre — l'embarquement oEmbed, le protocole
d'écoute tierce — SHALL conserver le comportement d'erreur que cette spécification leur
impose.

#### Scénario : Erreur servie dans le format demandé

- **QUAND** un consommateur demande dans un format machine une ressource qui n'existe pas
- **ALORS** le type de contenu de la réponse est celui de ce format
- **ET** le corps est analysable dans ce format
- **ET** il porte le code de statut et un intitulé décrivant l'erreur

#### Scénario : Absence de ressource n'est pas une panne

- **QUAND** une ressource demandée n'existe pas, quelle que soit la façon dont elle est
  désignée
- **ALORS** la réponse porte un code de statut d'erreur du client
- **ET** aucune trace d'exécution n'est exposée

#### Scénario : Demande mal formée et ressource absente sont distinguées

- **QUAND** une demande omet un paramètre que le système exige
- **ALORS** la réponse la signale comme une demande mal formée
- **ET** ce cas se distingue de celui d'une ressource correctement demandée mais absente

#### Scénario : Surfaces à spécification propre préservées

- **QUAND** une erreur survient sur l'embarquement oEmbed ou sur le protocole d'écoute
  tierce
- **ALORS** le comportement reste celui que leur propre spécification impose
- **ET** il n'est pas aligné sur celui décrit ici
