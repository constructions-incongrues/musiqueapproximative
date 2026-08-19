# compatibilite-php-8 Specification

## Purpose
TBD - created by archiving change auditer-la-compatibilite-php-8. Update Purpose after archive.

## Requirements

### Requirement: La suite de tests passe sous chaque version de PHP déclarée supportée

Une version SHALL être tenue pour supportée quand la suite y passe, et non quand une
dépendance la déclare dans son `composer.json`. La déclaration est une intention ; le
passage est une preuve. Les deux ont divergé ici : toute la chaîne déclarait PHP 8 alors
que 64 tests sur 408 y échouaient.

L'intégration continue SHALL exécuter la suite sous la version de production et sous la
version visée. L'échec de l'une SHALL faire échouer la vérification, sans hiérarchie de
gravité entre les deux — une passe consultative ne serait pas lue.

L'échec SHALL nommer le fichier et la ligne, faute de quoi il désigne « PHP 8 » et non
le défaut.

#### Scenario: la version courante de production

- **GIVEN** le code de `main`
- **WHEN** la suite complète est exécutée sous PHP 7.4
- **THEN** elle passe entièrement

#### Scenario: la version visée

- **GIVEN** le même code
- **WHEN** la suite complète est exécutée sous PHP 8.1
- **THEN** elle passe entièrement

#### Scenario: une régression que seul PHP 8 révèle

- **GIVEN** un accès à une propriété d'une valeur nulle, introduit dans le code
- **WHEN** l'intégration continue s'exécute
- **THEN** la passe PHP 8 échoue, alors que la passe PHP 7.4 reste verte
- **AND** l'échec nomme le fichier et la ligne

### Requirement: Le verdict de compatibilité porte ce qu'il ne prouve pas

Un « la suite passe » nu SHALL être tenu pour insuffisant : il se lit comme « la
migration est sûre », ce qu'il n'établit pas. La suite ne couvre pas tout le code
exécuté, et plusieurs ruptures de PHP 8 sont silencieuses par construction — la
comparaison entre chaîne et nombre ne lève rien du tout.

La documentation SHALL porter le verdict, sa date, la version exacte de l'interpréteur
employé, et ce que la mesure ne couvre pas.

#### Scenario: la documentation du verdict

- **GIVEN** la documentation publiée
- **WHEN** un mainteneur y cherche l'état de la compatibilité PHP 8
- **THEN** elle donne le verdict, la date, la version exacte de l'interpréteur employé
- **AND** elle nomme ce que la mesure ne couvre pas
