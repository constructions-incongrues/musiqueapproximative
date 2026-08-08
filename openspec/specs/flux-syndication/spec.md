# Spécification : flux-syndication

## Purpose

Décrit le flux de syndication publié par le site, son contenu, ses filtres et les
attentes des agrégateurs et lecteurs de podcast qui le consomment.

## Requirements

### Requirement: Publication du flux

Le système SHALL publier un flux RSS 2.01 en français décrivant les morceaux publiables.

#### Scénario : Accès au flux

- **QUAND** un consommateur demande `/posts/feed`
- **ALORS** un document RSS 2.01 est servi
- **ET** la langue déclarée est le français
- **ET** le flux porte le titre du site, son adresse et sa description

#### Scénario : Illustration du flux

- **QUAND** un consommateur demande `/posts/feed`
- **ALORS** le flux déclare une favicône et une image de logo servies par le site
- **ET** l'image et le titre de l'illustration renvoient à l'adresse du site

### Requirement: Volume et filtrage du flux

Le flux SHALL contenir au plus cinquante morceaux par défaut, et SHALL pouvoir être
restreint à un contributeur ou à un nombre d'items choisi.

#### Scénario : Volume par défaut

- **QUAND** aucun paramètre de volume n'est fourni
- **ALORS** le flux contient au plus les cinquante morceaux publiables les plus récents

#### Scénario : Volume choisi

- **QUAND** le paramètre `count` accompagne la demande
- **ALORS** le flux contient au plus ce nombre de morceaux

#### Scénario : Flux d'un contributeur

- **QUAND** le paramètre `contributor` accompagne la demande
- **ALORS** le flux ne contient que les morceaux de ce contributeur

### Requirement: Contenu d'un item

Chaque item du flux SHALL identifier le morceau, dater sa publication et permettre son
écoute directe.

#### Scénario : Identification de l'item

- **QUAND** un morceau figure dans le flux
- **ALORS** le titre de l'item vaut « artiste - titre »
- **ET** l'item renvoie à la page du morceau
- **ET** l'auteur de l'item est le nom d'affichage du contributeur
- **ET** l'identifiant unique de l'item est l'identifiant d'URL du morceau
- **ET** la date de publication de l'item est la date de publication du morceau

#### Scénario : Description de l'item

- **QUAND** un morceau figure dans le flux
- **ALORS** la description contient une illustration cliquable renvoyant à la page du
  morceau
- **ET** elle contient le corps du post rendu depuis son Markdown
- **ET** elle mentionne le contributeur

#### Scénario : Illustration glitchée

- **QUAND** l'effet de glitch du logo est actif pour cet item
- **ALORS** l'illustration de la description est produite par le service de glitch, avec
  l'identifiant du morceau comme graine

#### Scénario : Fichier joint pour l'écoute

- **QUAND** un morceau figure dans le flux
- **ALORS** l'item porte une pièce jointe désignant l'adresse absolue du fichier audio
- **ET** le type déclaré de la pièce jointe est `audio/mpeg`
- **ET** la taille déclarée est celle du fichier, ou zéro lorsque le fichier n'est pas
  lisible sur le serveur
