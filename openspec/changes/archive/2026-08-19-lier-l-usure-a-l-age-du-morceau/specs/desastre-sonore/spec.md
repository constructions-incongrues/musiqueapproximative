## ADDED Requirements

### Requirement: L'intensité de l'altération suit l'âge du morceau

L'altération sonore SHALL être d'autant plus marquée que le morceau est ancien. Un morceau
publié le jour même SHALL être presque net ; un morceau de dix-huit ans SHALL porter
l'altération maximale.

La courbe SHALL être choisie sur la distribution réelle du catalogue et non sur sa forme
mathématique. Une courbe linéaire sur dix-huit ans placerait 43 % des morceaux dans la
bande la plus altérée, ce qui ferait de l'usure l'état normal plutôt que la marque d'un âge.

Un **plancher** SHALL être appliqué : aucun morceau ne SHALL recevoir une altération
inaudible alors que la réponse annonce le désastre par son en-tête. Un désastre déclaré et
imperceptible se lit comme un désastre cassé.

La référence d'âge maximal SHALL être une constante et NE SHALL PAS être calculée depuis
l'étendue du catalogue. Cette étendue grandit chaque jour : l'usure d'un morceau donné
changerait alors sans que personne ne l'ait décidé.

#### Scenario: un morceau récent

- **GIVEN** un morceau publié il y a quelques semaines
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est perceptible mais minimale

#### Scenario: un morceau des débuts

- **GIVEN** un morceau publié il y a dix-huit ans
- **WHEN** le désastre lui est appliqué
- **THEN** l'altération est maximale

#### Scenario: deux morceaux d'âges différents

- **GIVEN** deux morceaux dont les dates de publication diffèrent de plusieurs années
- **WHEN** le désastre est appliqué à chacun
- **THEN** le plus ancien porte l'altération la plus marquée

### Requirement: Le site dit quel âge il donne au morceau

La page d'un morceau SHALL porter sa date de publication sous une forme lisible par une
machine.

Elle n'y figure aujourd'hui nulle part, ce qui est notable pour un catalogue couvrant
dix-huit ans : la date existe en base, elle est servie dans les représentations machine,
mais la page HTML n'en dit rien.

Cette date NE SHALL PAS être réservée au désastre : elle décrit le morceau, et quiconque
lit la page doit pouvoir la retrouver.

#### Scenario: lire la date depuis la page

- **GIVEN** la page d'un morceau publié
- **WHEN** on en lit le contenu
- **THEN** la date de publication y est présente sous forme normalisée

#### Scenario: le désastre s'en sert

- **GIVEN** une page dont le désastre sonore est appliqué
- **WHEN** l'altération est calculée
- **THEN** elle l'est depuis cette date, et non depuis une valeur codée dans la recette
