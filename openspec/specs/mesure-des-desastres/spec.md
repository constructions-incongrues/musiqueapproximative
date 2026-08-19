# mesure-des-desastres Specification

## Purpose
TBD - created by archiving change mesurer-les-desastres. Update Purpose after archive.

## Requirements

### Requirement: Le site enregistre quelle recette de désastre a été tirée

Chaque tirage SHALL être enregistré avec le nom de la recette retenue et le moment du
tirage. Sans cet enregistrement, aucune décision sur les désastres ne repose sur autre
chose qu'une impression : dix-neuf recettes sont déclarées et rien n'indique laquelle
sort, ni si certaines ne sortent jamais.

L'enregistrement SHALL avoir lieu au tirage, c'est-à-dire à la production de la page. Il
NE SHALL PAS exiger que du code s'exécute sur une consultation servie par le cache : le
cache englobe la mise en page et vaut vingt-quatre heures, et une collecte qui le
contournerait détruirait la propriété même qu'elle prétend mesurer.

Le fait de ne rien tirer SHALL être enregistré comme tel. Une règle qui ne se déclenche
jamais et une règle qu'on n'a jamais évaluée sont deux situations différentes, et un
relevé qui ne garde que les succès les rend indistinguables.

Une application **forcée** SHALL être distinguée d'un tirage. Le champ `trigger` d'une
règle nomme un paramètre d'URL qui, présent, applique la règle sans tirer : `?danse=1`
applique `danse`. C'est l'outil par lequel on essaie un désastre à la main, et le
mainteneur s'en sert. Confondre les deux ferait compter ses essais comme des tirages, et
gonflerait exactement les recettes sur lesquelles il travaille.

#### Scenario: une application forcée par un paramètre d'URL

- **GIVEN** une règle dont le `trigger` est présent dans les paramètres de la requête
- **WHEN** la page est produite et la règle appliquée sans tirage
- **THEN** le relevé distingue cette application d'un tirage
- **AND** elle n'entre pas dans le décompte des tirages

#### Scenario: une recette est tirée

- **GIVEN** une page dont une règle de désastre retient une recette
- **WHEN** la page est produite
- **THEN** le relevé porte le nom de cette recette et l'horodatage du tirage

#### Scenario: aucune recette n'est tirée

- **GIVEN** une page dont les règles sont évaluées sans qu'aucune ne retienne de recette
- **WHEN** la page est produite
- **THEN** le relevé porte l'évaluation, avec l'absence de recette pour résultat

#### Scenario: la page est servie depuis le cache

- **GIVEN** une page déjà produite et mise en cache
- **WHEN** elle est servie de nouveau sans que l'action soit réexécutée
- **THEN** aucun nouvel enregistrement n'est produit
- **AND** le document servi reste identique

### Requirement: Le relevé dit qu'il compte des tirages et non des visiteurs

Le relevé SHALL nommer sa grandeur : des tirages. Il NE SHALL PAS être présenté, nommé ni
documenté comme une audience, une fréquentation ou un nombre de visiteurs.

La distinction n'est pas de vocabulaire. Le cache sert la même représentation à toutes les
consultations pendant vingt-quatre heures : un tirage peut correspondre à une consultation
comme à dix mille, et le rapport entre les deux n'est pas connu. Un chiffre présenté sans
cette précision SHALL être tenu pour une statistique qu'on ne saura plus interpréter.

Là où le relevé est lu, la portée SHALL être écrite à côté du chiffre, et non dans un
document séparé.

#### Scenario: lecture du relevé

- **GIVEN** un relevé de tirages
- **WHEN** un mainteneur le consulte
- **THEN** la grandeur mesurée est nommée — des tirages
- **AND** ce que le chiffre ne dit pas est écrit à côté de lui

### Requirement: Le relevé est lisible sans interface dédiée

Le relevé SHALL être consultable par un moyen déjà disponible dans le projet, sans qu'une
interface de visualisation soit nécessaire pour en tirer les décisions des stories
suivantes.

#### Scenario: dénombrer les tirages par recette

- **GIVEN** un relevé couvrant une période
- **WHEN** un mainteneur cherche combien de fois chaque recette a été tirée
- **THEN** il obtient le décompte par recette
- **AND** les recettes jamais tirées apparaissent avec un décompte nul, plutôt que d'être
  absentes du relevé
