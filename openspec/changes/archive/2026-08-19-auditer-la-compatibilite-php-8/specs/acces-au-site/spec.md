## ADDED Requirements

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
