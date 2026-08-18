# contrat-openapi Specification

## Purpose

Décrit la description d'API que le site publie : ce qu'elle couvre, comment elle s'adapte
au déploiement, et le dispositif qui l'empêche de dériver.

Cette capacité porte sur le **document**, pas sur les routes qu'il décrit — celles-ci
relèvent de `formats-de-sortie`, `catalogue-morceaux`, `flux-syndication` et
`embarquement-oembed`, qui restent normatives. Le document est descriptif : s'il diverge
d'elles, c'est lui qui a tort.

## Requirements

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

### Requirement: Contrat vérifié contre le site

Le contrat SHALL être confronté au site par un test automatisé, pour chaque route qu'il
déclare. Une description qui cesse d'être vraie SHALL faire échouer la suite de tests, afin
qu'un document non exercé ne puisse pas dériver en silence.

#### Scénario : Chaque route déclarée répond comme annoncé

- **QUAND** la suite de tests s'exécute
- **ALORS** chaque route déclarée au contrat est demandée
- **ET** le code de statut qu'elle renvoie est celui que le contrat annonce
- **ET** le type de contenu qu'elle sert est celui que le contrat annonce

#### Scénario : Route décrite mais absente

- **QUAND** le contrat déclare une route que le site ne sert pas
- **ALORS** la suite de tests échoue en nommant cette route

#### Scénario : Réponse qui s'écarte du contrat

- **QUAND** une route servie change de code de statut ou de type de contenu sans que le
  contrat soit amendé
- **ALORS** la suite de tests échoue en nommant l'écart constaté

#### Scénario : Portée de la vérification

- **QUAND** la vérification s'exécute
- **ALORS** elle porte sur le code de statut, le type de contenu et la présence des champs
  de premier niveau annoncés
- **ET** elle ne prétend pas valider la structure complète des corps de réponse

#### Scénario : Le document dit lui-même ce qui n'est pas vérifié

- **QUAND** un consommateur lit une partie du document que la vérification ne couvre pas
- **ALORS** cette partie porte la mention qu'elle n'est pas confrontée au site
- **ET** le consommateur distingue ce qui est garanti de ce qui est seulement déclaré, sans
  sortir du document
