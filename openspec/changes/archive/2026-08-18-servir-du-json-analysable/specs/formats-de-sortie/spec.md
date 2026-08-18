## ADDED Requirements

### Requirement: Analysabilité des représentations JSON

Toute réponse que le site sert en annonçant un type de contenu JSON SHALL être un document
JSON syntaxiquement valide.

Cette garantie SHALL être indépendante du contenu des morceaux. Aucun caractère saisi dans
le corps d'un morceau SHALL pouvoir rendre une réponse inanalysable, et un morceau fautif
SHALL rester sans effet sur les autres morceaux servis dans la même réponse.

#### Scénario : Un corps portant des caractères que le rendu HTML échappe

- **QUAND** le corps d'un morceau contient un antislash, une esperluette ou un guillemet
- **ALORS** la représentation JSON de ce morceau s'analyse
- **ET** le corps servi restitue ces caractères

#### Scénario : Un morceau fautif n'emporte pas la collection

- **QUAND** un morceau du catalogue porte un caractère que le rendu HTML échappe
- **ALORS** la liste JSON complète s'analyse
- **ET** les autres morceaux y restent lisibles

#### Scénario : Corps HTML valide

- **QUAND** un consommateur lit le champ `html` du corps d'un morceau
- **ALORS** les caractères qui ont une signification en HTML y figurent échappés
- **ET** le fragment servi est du HTML valide

#### Scénario : Un morceau décrit à l'identique quelle que soit sa désignation

- **QUAND** un même morceau est demandé par son identifiant d'URL puis par l'empreinte de sa
  piste
- **ALORS** les deux réponses le décrivent à l'identique
- **ET** seuls les liens de navigation, que la seconde désignation ne connaît pas, diffèrent
