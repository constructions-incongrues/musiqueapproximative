## MODIFIED Requirements

### Requirement: Négociation du format oEmbed

La réponse oEmbed SHALL être servie en JSON par défaut, et en XML sur demande explicite.
Tout autre format demandé SHALL être refusé explicitement, et non produire une réponse
vide.

#### Scénario : Format JSON par défaut

- **QUAND** aucun paramètre `format` n'est fourni, ou qu'il vaut `json`
- **ALORS** la réponse est un objet JSON
- **ET** le type de contenu est `application/json+oembed`

#### Scénario : Format XML

- **QUAND** le paramètre `format` vaut `xml`
- **ALORS** la réponse est un document XML dont la racine est `oembed`
- **ET** chaque champ devient un élément enfant, son contenu étant échappé
- **ET** le type de contenu est `text/xml+oembed`

#### Scénario : Format non pris en charge

- **QUAND** le paramètre `format` vaut autre chose que `json` ou `xml`
- **ALORS** la réponse porte le code `501 Not Implemented`
- **ET** aucune donnée de morceau n'est divulguée dans le corps

#### Scénario : Casse et espaces du paramètre

- **QUAND** le paramètre `format` vaut `JSON`, `Xml`, ou la même valeur entourée
  d'espaces
- **ALORS** le format est reconnu comme s'il était écrit en minuscules et sans espaces
