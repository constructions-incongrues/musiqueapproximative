## MODIFIED Requirements

### Requirement: Application d'une recette

L'application d'une recette SHALL enrichir la réponse des ressources du désastre
correspondant, sans modifier le contenu servi. L'enrichissement SHALL faire partie de la
représentation mise en cache, de sorte qu'une réponse servie depuis le cache soit aussi
complète que celle qui l'a produite.

#### Scénario : Options transmises au désastre

- **QUAND** la recette déclare des options
- **ALORS** ces options sont mises à disposition du désastre côté client
- **ET** elles le sont pour toute réponse portant les ressources du désastre, qu'elle soit
  produite ou servie depuis le cache

#### Scénario : Consultations successives d'une adresse enrichie

- **QUAND** une adresse dont la réponse porte un désastre est demandée plusieurs fois
  pendant la durée de vie du cache
- **ALORS** chaque réponse porte les ressources du désastre **et** ses options
- **ET** aucune consultation ne sert de ressources privées de leurs options
