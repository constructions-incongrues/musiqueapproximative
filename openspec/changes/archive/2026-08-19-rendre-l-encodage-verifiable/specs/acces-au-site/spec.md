## ADDED Requirements

### Requirement: L'installation peut être interrogée sur son encodage

Le site SHALL exposer, à une adresse fixe et sans authentification, de quoi constater
l'encodage de la connexion qu'il utilise pour lire et écrire les morceaux.

La réponse SHALL dire si cet encodage est celui attendu, et lequel est effectivement en
place — un verdict sans la valeur constatée ne se diagnostique pas.

Cette vérification SHALL être en lecture seule. Elle ne SHALL rien écrire, ni modifier aucun
état.

Elle SHALL être analysable par une machine, sans quoi elle ne peut pas être interrogée
automatiquement, et une vérification que personne ne lance ne vérifie rien.

#### Scénario : Encodage conforme

- **QUAND** un demandeur interroge l'encodage d'une installation correctement configurée
- **ALORS** la réponse indique que l'encodage est celui attendu
- **ET** elle nomme l'encodage effectivement en place

#### Scénario : Encodage non conforme

- **QUAND** l'encodage de la connexion n'est pas celui attendu
- **ALORS** la réponse l'indique comme non conforme
- **ET** elle nomme l'encodage constaté, pour que l'écart se diagnostique sans accès au serveur

#### Scénario : La vérification ne modifie rien

- **QUAND** la vérification est demandée, quel qu'en soit le résultat
- **ALORS** aucune donnée du site n'est créée, modifiée ni supprimée

#### Scénario : Base injoignable

- **QUAND** la connexion à la base ne peut pas être établie
- **ALORS** la réponse le distingue d'un encodage non conforme
- **ET** elle ne prétend pas que l'encodage est correct
