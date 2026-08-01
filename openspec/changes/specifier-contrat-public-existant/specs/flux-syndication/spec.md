## Purpose

Décrit le flux de syndication publié par le site, son contenu, ses filtres et les
attentes des agrégateurs et lecteurs de podcast qui le consomment.

## ADDED Requirements

### Requirement: Publication du flux

Le système SHALL publier un flux RSS 2.01 en français décrivant les morceaux publiables.

#### Scenario: Accès au flux

- **WHEN** un consommateur demande `/posts/feed`
- **THEN** un document RSS 2.01 est servi
- **AND** la langue déclarée est le français
- **AND** le flux porte le titre du site, son adresse et sa description

#### Scenario: Illustration du flux

- **THEN** le flux déclare une favicône et une image de logo servies par le site
- **AND** l'image et le titre de l'illustration renvoient à l'adresse du site

### Requirement: Volume et filtrage du flux

Le flux SHALL contenir au plus cinquante morceaux par défaut, et SHALL pouvoir être
restreint à un contributeur ou à un nombre d'items choisi.

#### Scenario: Volume par défaut

- **WHEN** aucun paramètre de volume n'est fourni
- **THEN** le flux contient au plus les cinquante morceaux publiables les plus récents

#### Scenario: Volume choisi

- **WHEN** le paramètre `count` accompagne la demande
- **THEN** le flux contient au plus ce nombre de morceaux

#### Scenario: Flux d'un contributeur

- **WHEN** le paramètre `contributor` accompagne la demande
- **THEN** le flux ne contient que les morceaux de ce contributeur

### Requirement: Contenu d'un item

Chaque item du flux SHALL identifier le morceau, dater sa publication et permettre son
écoute directe.

#### Scenario: Identification de l'item

- **WHEN** un morceau figure dans le flux
- **THEN** le titre de l'item vaut « artiste - titre »
- **AND** l'item renvoie à la page du morceau
- **AND** l'auteur de l'item est le nom d'affichage du contributeur
- **AND** l'identifiant unique de l'item est l'identifiant d'URL du morceau
- **AND** la date de publication de l'item est la date de publication du morceau

#### Scenario: Description de l'item

- **THEN** la description contient une illustration cliquable renvoyant à la page du
  morceau
- **AND** elle contient le corps du post rendu depuis son Markdown
- **AND** elle mentionne le contributeur

#### Scenario: Illustration glitchée

- **WHEN** l'effet de glitch du logo est actif pour cet item
- **THEN** l'illustration de la description est produite par le service de glitch, avec
  l'identifiant du morceau comme graine

#### Scenario: Fichier joint pour l'écoute

- **THEN** l'item porte une pièce jointe désignant l'adresse absolue du fichier audio
- **AND** le type déclaré de la pièce jointe est `audio/mpeg`
- **AND** la taille déclarée est celle du fichier, ou zéro lorsque le fichier n'est pas
  lisible sur le serveur

> Comportement constaté, non souhaitable : la taille de la pièce jointe est obtenue en
> chargeant l'intégralité de chaque fichier audio en mémoire, pour chaque item et à
> chaque demande du flux. À traiter par un changement dédié.
