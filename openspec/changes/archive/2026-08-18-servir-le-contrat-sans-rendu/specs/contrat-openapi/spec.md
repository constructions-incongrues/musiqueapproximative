## MODIFIED Requirements

### Requirement: Description d'API publiée

Le site SHALL publier, à une adresse fixe et sans authentification, un document OpenAPI
décrivant les routes qu'il sert. Ce document SHALL désigner son serveur par une adresse
**relative**, de sorte qu'il se résolve contre l'installation qui le sert, quelle qu'elle
soit, sans qu'aucune étape de transformation ne soit nécessaire avant publication.

Le document SHALL être publiable tel qu'il est versionné. Aucun motif restant à substituer
SHALL y figurer.

Le document SHALL couvrir les routes du catalogue de morceaux et leurs représentations
alternatives. Il SHALL laisser de côté les surfaces décrites ailleurs.

#### Scénario : Document servi

- **QUAND** un consommateur demande l'adresse du document de description d'API
- **ALORS** le document est servi avec un code `200`
- **ET** son contenu s'analyse comme un document OpenAPI valide
- **ET** aucune authentification n'est demandée

#### Scénario : Serveur adapté au déploiement

- **QUAND** le document est servi par une installation donnée
- **ALORS** l'adresse de serveur qu'il déclare se résout sur cette installation
- **ET** deux installations de domaines différents servent le même document sans qu'il ait
  été transformé pour l'une ou pour l'autre

#### Scénario : Document publiable tel quel

- **QUAND** le document versionné est déposé sur une installation sans transformation
- **ALORS** il ne porte aucun motif de substitution restant
- **ET** ce qu'un consommateur lit est ce que le dépôt contient

#### Scénario : Portée décrite

- **QUAND** un consommateur lit le document
- **ALORS** chaque route du catalogue de morceaux, de leurs listes, de leur flux de
  syndication et de leur embarquement y figure
- **ET** les routes du protocole d'écoute tierce n'y figurent pas, leur description étant
  tenue ailleurs

#### Scénario : Le document décrit ce qui est servi, non ce qui est souhaité

- **QUAND** ce que sert une route s'écarte de ce qu'une spécification prescrit
- **ALORS** le document déclare ce que la route sert réellement
- **ET** l'écart devient lisible au lieu d'être masqué
