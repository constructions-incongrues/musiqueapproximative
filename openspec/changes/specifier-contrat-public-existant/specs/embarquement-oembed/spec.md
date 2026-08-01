## Purpose

Décrit comment un site tiers obtient un lecteur embarquable pour un morceau, par le
protocole oEmbed ou en pointant directement le gabarit d'embarquement.

## ADDED Requirements

### Requirement: Point d'entrée oEmbed

Le système SHALL répondre aux demandes oEmbed portant sur l'adresse d'un morceau
publiable.

#### Scenario: Demande sur un morceau publiable

- **WHEN** un consommateur demande `/oembed` avec le paramètre `url` valant l'adresse
  d'un morceau publiable
- **THEN** une réponse oEmbed décrivant ce morceau est servie

#### Scenario: Morceau inconnu

- **WHEN** l'adresse fournie ne désigne aucun morceau publiable
- **THEN** la réponse est une erreur 404

#### Scenario: Résolution du morceau depuis l'adresse

- **WHEN** l'adresse fournie comporte un chemin
- **THEN** le morceau est résolu à partir du dernier segment de ce chemin, les segments
  précédents étant ignorés

### Requirement: Contenu de la réponse oEmbed

La réponse oEmbed SHALL être de type `rich` et SHALL porter un fragment HTML prêt à être
inséré par le consommateur.

#### Scenario: Champs de la réponse

- **WHEN** une réponse oEmbed est servie
- **THEN** elle porte `version` valant 1 et `type` valant `rich`
- **AND** elle porte `provider_name` et `provider_url` identifiant le site
- **AND** elle porte `title` valant « artiste - titre »
- **AND** elle porte `description` valant le corps du post débarrassé de son balisage
- **AND** elle porte `width` et `height` correspondant aux dimensions du gabarit
  d'embarquement effectivement servi

#### Scenario: Fragment embarquable

- **THEN** le champ `html` contient un élément `iframe` sans bordure ni défilement
- **AND** l'adresse source de cet `iframe` est celle de la page du morceau assortie du
  paramètre `embed`
- **AND** ses dimensions sont celles déclarées par `width` et `height`

### Requirement: Négociation du format oEmbed

La réponse oEmbed SHALL être servie en JSON par défaut, et en XML sur demande explicite.

#### Scenario: Format JSON par défaut

- **WHEN** aucun paramètre `format` n'est fourni, ou qu'il vaut `json`
- **THEN** la réponse est un objet JSON
- **AND** le type de contenu est `application/json`

#### Scenario: Format XML

- **WHEN** le paramètre `format` vaut `xml`
- **THEN** la réponse est un document XML dont la racine est `oembed`
- **AND** chaque champ devient un élément enfant, son contenu étant échappé
- **AND** le type de contenu est `text/xml+oembed`

#### Scenario: Format inconnu

- **WHEN** le paramètre `format` vaut autre chose que `json` ou `xml`
- **THEN** aucune donnée n'est encodée et la réponse est inexploitable

> Comportement constaté, non souhaitable : ni erreur, ni repli sur le format par défaut.
> Le type de contenu `application/json+oembed` attendu par la spécification oEmbed n'est
> par ailleurs pas déclaré. À traiter par un changement dédié.

### Requirement: Gabarit d'embarquement

Le système SHALL servir, pour un morceau publiable, un document autonome contenant un
lecteur audio et les liens de retour vers le site.

#### Scenario: Demande du gabarit

- **WHEN** un visiteur demande `/post/:slug` avec le paramètre `embed`
- **THEN** un document autonome est servi, sans l'habillage du site

#### Scenario: Contenu du gabarit

- **THEN** le document porte le titre du morceau, lié à sa page, ouvrant dans un nouvel
  onglet
- **AND** il porte le corps du post rendu depuis son Markdown
- **AND** il porte un lecteur audio HTML avec ses contrôles, dont la source est le
  fichier audio du morceau
- **AND** il mentionne le contributeur, lié à sa playlist, et le site

#### Scenario: Variante d'embarquement

- **WHEN** le paramètre `embed` porte une valeur
- **THEN** le gabarit correspondant à cette variante est servi si il existe
