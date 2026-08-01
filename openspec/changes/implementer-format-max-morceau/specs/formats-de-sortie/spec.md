## MODIFIED Requirements

### Requirement: Représentation Max/MSP d'un morceau isolé

Le format `max` appliqué à un morceau isolé SHALL produire une ligne unique, de même
structure que celle qu'une liste produit pour ce morceau, un morceau isolé étant traité
comme une liste d'un seul élément.

#### Scénario : Demande du format max sur un morceau

- **QUAND** un consommateur demande `/post/:slug` au format `max`
- **ALORS** la réponse est une ligne portant le rang, l'artiste, le titre, l'adresse du
  fichier audio, l'adresse de la page, le contributeur, le nombre total de morceaux et le
  corps du post
- **ET** le rang vaut `0` et le nombre total de morceaux vaut `1`
- **ET** le type de contenu est `application/maxmsp+text`

#### Scénario : Champs textuels assainis

- **QUAND** l'artiste, le titre ou le corps du post contiennent des guillemets ou des
  retours à la ligne
- **ALORS** ces caractères sont retirés de la ligne produite, comme dans la
  représentation d'une liste

#### Scénario : Structure identique à celle d'une liste

- **QUAND** un consommateur compare la ligne obtenue pour un morceau isolé et celle que
  produit pour ce même morceau une liste au format `max`
- **ALORS** les deux lignes portent les mêmes champs, dans le même ordre et avec le même
  échappement
- **ET** seuls le rang et le nombre total de morceaux peuvent différer
