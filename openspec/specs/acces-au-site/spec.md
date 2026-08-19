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

### Requirement: Un contributeur sans profil est affiché sans erreur

Le profil SHALL être traité comme facultatif : les 210 comptes de la base en sont
dépourvus. L'affichage SHALL retomber sur l'identifiant du compte, et cette retombée
SHALL être le cas normal et non le cas dégradé.

Aucun avertissement PHP NE SHALL être émis à cette occasion, quelle que soit la version
de l'interpréteur. La distinction compte : en PHP 7.4 la lecture d'une propriété sur
`null` n'est qu'une notice silencieuse, en PHP 8 c'est un avertissement — le même code
est correct sur l'un et fautif sur l'autre.

Le code qui lit la relation SHALL traiter indifféremment ses deux formes d'absence :
`null` quand elle est chargée par jointure, objet vide quand elle l'est paresseusement.

#### Scenario: le nom d'affichage retombe sur l'identifiant

- **GIVEN** un morceau publié dont le contributeur n'a aucune ligne de profil
- **WHEN** une page qui nomme le contributeur est servie
- **THEN** l'identifiant du compte est affiché
- **AND** aucun avertissement PHP n'est émis, quelle que soit la version de l'interpréteur

#### Scenario: la relation absente, chargée par jointure

- **GIVEN** une liste de morceaux obtenue par une requête qui joint le profil pour éviter le N+1
- **WHEN** la relation est absente en base
- **THEN** elle est hydratée à `null`, et non en objet vide comme le ferait le chargement paresseux
- **AND** le code qui la lit la traite sans erreur
