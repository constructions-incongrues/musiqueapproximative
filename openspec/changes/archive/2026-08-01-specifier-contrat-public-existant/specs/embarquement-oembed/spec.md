## Purpose

Décrit comment un site tiers obtient un lecteur embarquable pour un morceau, par le
protocole oEmbed ou en pointant directement le gabarit d'embarquement.

## ADDED Requirements

### Requirement: Point d'entrée oEmbed

Le système SHALL répondre aux demandes oEmbed portant sur l'adresse d'un morceau
publiable.

#### Scénario : Demande sur un morceau publiable

- **QUAND** un consommateur demande `/oembed` avec le paramètre `url` valant l'adresse
  d'un morceau publiable
- **ALORS** une réponse oEmbed décrivant ce morceau est servie

#### Scénario : Morceau inconnu

- **QUAND** l'adresse fournie ne désigne aucun morceau publiable
- **ALORS** la réponse est une erreur 404

#### Scénario : Résolution du morceau depuis l'adresse

- **QUAND** l'adresse fournie comporte un chemin
- **ALORS** le morceau est résolu à partir du dernier segment de ce chemin, les segments
  précédents étant ignorés

### Requirement: Contenu de la réponse oEmbed

La réponse oEmbed SHALL être de type `rich` et SHALL porter un fragment HTML prêt à être
inséré par le consommateur.

#### Scénario : Champs de la réponse

- **QUAND** une réponse oEmbed est servie
- **ALORS** elle porte `version` valant 1 et `type` valant `rich`
- **ET** elle porte `provider_name` et `provider_url` identifiant le site
- **ET** elle porte `title` valant « artiste - titre »
- **ET** elle porte `description` valant le corps du post débarrassé de son balisage
- **ET** elle porte `width` et `height` correspondant aux dimensions du gabarit
  d'embarquement effectivement servi

#### Scénario : Fragment embarquable

- **QUAND** une réponse oEmbed est servie
- **ALORS** le champ `html` contient un élément `iframe` sans bordure ni défilement
- **ET** l'adresse source de cet `iframe` est celle de la page du morceau assortie du
  paramètre `embed`
- **ET** ses dimensions sont celles déclarées par `width` et `height`

### Requirement: Négociation du format oEmbed

La réponse oEmbed SHALL être servie en JSON par défaut, et en XML sur demande explicite.

#### Scénario : Format JSON par défaut

- **QUAND** aucun paramètre `format` n'est fourni, ou qu'il vaut `json`
- **ALORS** la réponse est un objet JSON
- **ET** le type de contenu est `application/json`

#### Scénario : Format XML

- **QUAND** le paramètre `format` vaut `xml`
- **ALORS** la réponse est un document XML dont la racine est `oembed`
- **ET** chaque champ devient un élément enfant, son contenu étant échappé
- **ET** le type de contenu est `text/xml+oembed`

#### Scénario : Format inconnu

- **QUAND** le paramètre `format` vaut autre chose que `json` ou `xml`
- **ALORS** aucune donnée n'est encodée et la réponse est inexploitable

> Comportement constaté, non souhaitable : ni erreur, ni repli sur le format par défaut.
> Le type de contenu `application/json+oembed` attendu par la spécification oEmbed n'est
> par ailleurs pas déclaré. À traiter par un changement dédié.

### Requirement: Gabarit d'embarquement

Le système SHALL servir, pour un morceau publiable, un document autonome contenant un
lecteur audio et les liens de retour vers le site.

#### Scénario : Demande du gabarit

- **QUAND** un visiteur demande `/post/:slug` avec le paramètre `embed`
- **ALORS** un document autonome est servi, sans l'habillage du site

#### Scénario : Contenu du gabarit

- **QUAND** le gabarit d'embarquement d'un morceau est servi
- **ALORS** le document porte le titre du morceau, lié à sa page, ouvrant dans un nouvel
  onglet
- **ET** il porte le corps du post rendu depuis son Markdown
- **ET** il porte un lecteur audio HTML avec ses contrôles, dont la source est le
  fichier audio du morceau
- **ET** il mentionne le contributeur, lié à sa playlist, et le site

#### Scénario : Variante d'embarquement

- **QUAND** le paramètre `embed` porte une valeur
- **ALORS** le gabarit correspondant à cette variante est servi si il existe
