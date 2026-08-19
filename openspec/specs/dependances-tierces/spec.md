# dependances-tierces Specification

## Purpose
TBD - created by archiving change trancher-les-dependances-tierces-des-desastres. Update Purpose after archive.

## Requirements

### Requirement: Le site ne fait pas charger de ressource depuis un hôte que le visiteur n'a pas choisi

Une page publique NE SHALL PAS faire télécharger au navigateur du visiteur une ressource
servie par un hôte tiers, sauf décision écrite et motivée pour cette ressource précise.

La raison est celle déjà retenue pour la page de consultation du contrat OpenAPI, où Redoc
a été versé au dépôt plutôt qu'appelé depuis un CDN : le visiteur qui consulte le site n'a
pas à être annoncé à un tiers. Un CDN reçoit l'adresse IP du visiteur, l'en-tête `Referer`
qui désigne la page consultée, et de quoi constituer une empreinte de navigateur — sans que
le visiteur l'ait demandé, et ici au hasard d'un tirage qu'il ne choisit pas.

Une ressource auto-hébergée SHALL porter sa version dans son nom et SHALL être accompagnée
de sa licence, comme l'est déjà Redoc.

Auto-héberger, c'est redistribuer. La licence de chaque ressource SHALL être vérifiée comme
autorisant la redistribution **avant** que le fichier soit versé au dépôt. Une licence qui
ne le permettrait pas NE SHALL PAS être contournée : la ressource reste alors appelée depuis
son hôte, et cette exception SHALL être écrite avec sa raison.

#### Scenario: une page publique portant un désastre

- **GIVEN** une page publique dont une recette de désastre charge une bibliothèque
- **WHEN** elle est servie à un visiteur
- **THEN** la bibliothèque est servie par le site lui-même
- **AND** aucune requête n'est faite vers un hôte tiers du fait de cette recette

#### Scenario: une ressource dont la licence interdit la redistribution

- **GIVEN** une ressource tierce dont la licence n'autorise pas qu'on la redistribue
- **WHEN** la décision d'auto-hébergement est prise
- **THEN** cette ressource est exclue de l'auto-hébergement
- **AND** l'exception est écrite avec sa raison, plutôt que laissée tacite

### Requirement: Ce que le site expose à des tiers est écrit

La documentation SHALL porter l'inventaire des hôtes tiers que le site fait contacter par
le navigateur du visiteur, y compris quand cet inventaire est vide.

Un inventaire vide NE SHALL PAS être omis : c'est ce que le lecteur cherche à savoir, et
son absence ne distingue pas « aucun tiers » de « personne n'a regardé ».

La documentation SHALL nommer ce qu'un tiers reçoit — adresse IP, page consultée, empreinte
de navigateur — plutôt que de se contenter de le désigner comme un « appel externe ».

#### Scenario: consulter ce que le site expose

- **GIVEN** la documentation publiée
- **WHEN** un lecteur cherche quels tiers le site fait contacter
- **THEN** il obtient l'inventaire, et la liste vide est déclarée comme telle
- **AND** ce que le tiers reçoit est nommé
