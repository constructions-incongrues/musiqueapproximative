## ADDED Requirements

### Requirement: Les versions exercées sont celles que la production peut servir

L'ensemble des versions que l'intégration continue exerce SHALL être déterminé par ce que
l'hébergement peut réellement servir, et non par ce qu'il a paru intéressant de mesurer.

Une version que la production ne peut pas servir NE SHALL PAS être tenue pour une cible de
migration, quel que soit le verdict rendu sur elle. Un verdict « atteignable » portant sur
une version indisponible est un verdict sans objet : il donne le sentiment d'une porte
ouverte sur un mur.

Réciproquement, toute version que l'hébergement propose et vers laquelle une montée est
envisagée SHALL être exercée avant qu'on la déclare possible.

La documentation SHALL nommer les versions que l'hébergement propose, la date du relevé, et
la version servie au moment de ce relevé. Sans la date, l'inventaire se lit comme permanent
alors qu'il change avec l'hébergeur.

#### Scenario: une version mesurée mais indisponible

- **GIVEN** un verdict de compatibilité rendu sur une version d'interpréteur
- **WHEN** cette version n'est pas proposée par l'hébergement
- **THEN** la documentation dit qu'elle est hors d'atteinte, plutôt que de la présenter
  comme la prochaine étape

#### Scenario: une version disponible et non mesurée

- **GIVEN** une version d'interpréteur que l'hébergement propose
- **WHEN** une montée vers cette version est envisagée
- **THEN** l'intégration continue l'exerce avant que la montée soit déclarée possible

#### Scenario: l'inventaire porte sa date

- **GIVEN** la documentation publiée
- **WHEN** un mainteneur y cherche les versions disponibles
- **THEN** il y trouve le relevé, sa date, et la version servie ce jour-là

### Requirement: La déclaration de version supportée suit la preuve

La contrainte de version déclarée par le projet NE SHALL PAS être élargie à une version que
la suite n'a pas exercée avec succès.

L'élargissement SHALL suivre le retrait du contournement qui permettait d'installer malgré
la contrainte, et non l'inverse : retirer le contournement en premier fait de la contrainte
la seule chose qui tienne, et son élargissement devient alors une déclaration vérifiée.

Cette exigence existe parce que la faute inverse a été commise : toute la chaîne de
dépendances déclarait PHP 8 alors que 64 tests sur 408 y échouaient. Une déclaration qui
précède la mesure ne coûte rien à écrire et se paye en production.

#### Scenario: une version verte

- **GIVEN** une version d'interpréteur que la suite complète traverse sans échec
- **WHEN** le projet déclare la supporter
- **THEN** le contournement d'installation la concernant a d'abord été retiré

#### Scenario: une version rouge

- **GIVEN** une version d'interpréteur sous laquelle la suite échoue
- **WHEN** la contrainte de version du projet est examinée
- **THEN** elle ne mentionne pas cette version
- **AND** la documentation dit ce qui a échoué

## MODIFIED Requirements

### Requirement: Le verdict de compatibilité porte ce qu'il ne prouve pas

Un « la suite passe » nu SHALL être tenu pour insuffisant : il se lit comme « la
migration est sûre », ce qu'il n'établit pas. La suite ne couvre pas tout le code
exécuté, et plusieurs ruptures de PHP 8 sont silencieuses par construction — la
comparaison entre chaîne et nombre ne lève rien du tout.

La documentation SHALL porter le verdict, sa date, la version exacte de l'interpréteur
employé, et ce que la mesure ne couvre pas.

Elle SHALL également porter **ce que la mesure ne peut pas servir** : une version mesurée
que l'hébergement ne propose pas, et une version proposée que la mesure n'a pas couverte.
Un verdict qui tait ces deux écarts se lit comme une feuille de route alors qu'il n'en est
pas une.

#### Scenario: la documentation du verdict

- **GIVEN** la documentation publiée
- **WHEN** un mainteneur y cherche l'état de la compatibilité PHP 8
- **THEN** elle donne le verdict, la date, la version exacte de l'interpréteur employé
- **AND** elle nomme ce que la mesure ne couvre pas

#### Scenario: l'écart entre ce qui est mesuré et ce qui est servable

- **GIVEN** un verdict rendu sur une version, et un hébergement qui en propose d'autres
- **WHEN** un mainteneur lit la page pour décider d'une montée
- **THEN** elle nomme les versions mesurées que l'hébergement ne propose pas
- **AND** elle nomme les versions proposées que la mesure ne couvre pas
