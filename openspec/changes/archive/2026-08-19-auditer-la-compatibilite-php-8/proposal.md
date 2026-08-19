## Why

PHP 7.4 est hors support depuis novembre 2022. Le projet n'ignorait pas la question,
il ne l'avait jamais posée : la story 28 la posait comme un audit, en supposant qu'on
ne savait pas si la montée était atteignable.

**La mesure a inversé la supposition.** Elle est atteignable, et ce qui l'empêche tient
en une ligne.

Toute la chaîne de dépendances déclare déjà PHP 8 — `lexpress/symfony1` v1.5.24 en
`^7.4 || ^8.1`, `lexpress/doctrine1` v1.4.6 en `^7.4 || ^8.0`, swiftmailer de même. Le
seul verrou déclaré est le `"php": "^7.4"` que `src/composer.json` s'impose à lui-même.

Exécuté sous PHP 8.1 contre la même base : **269 tests unitaires verts sur 269**, la
suite fonctionnelle **admin verte**, et 64 échecs sur 408 côté frontend. Le témoin sous
7.4, lancé le même jour sur le même code, est **entièrement vert** — la casse est donc
réelle et non préexistante.

Ces 64 échecs ont **une seule cause**, et elle est de la veille.

## What Changes

- **Corriger la lecture sur `null` dans `sfGuardUser::getDisplayName()`** —
  `$this->UserProfile->display_name` quand `UserProfile` est `null`. En PHP 7.4 c'est une
  notice silencieuse ; en PHP 8 c'est un warning, que le navigateur de test de symfony 1
  transforme en exception. Les 210 utilisateurs de la base n'ont **aucun** profil : la
  branche est prise à chaque rendu de contributeur.

- **Consigner l'origine du `null`, qui n'est pas celle qu'on croirait.** Il a été
  introduit la veille par la story 34 : le `leftJoin('u.UserProfile pr')` ajouté pour
  supprimer le N+1 fait hydrater la relation absente en `null`, là où Doctrine 1
  fabriquait auparavant un objet `UserProfile` vide au chargement paresseux. Vérifié
  par comparaison directe des deux requêtes. **PHP 7.4 masque intégralement ce défaut** ;
  aucun test ne pouvait le voir sur l'interpréteur du projet.

- **Rendre le verdict reproductible** : un travail d'intégration continue qui exécute la
  suite sous PHP 8.1 en plus de 7.4. Sans lui, ce document est un chiffre qui vieillira
  comme les quatre autres de cette release.

- **Écrire le verdict** dans la documentation : atteignable, à quel prix, et ce que
  l'audit ne prouve pas.

**Hors périmètre, délibérément** : faire la migration. Ce change ne change pas la version
de PHP du conteneur ni la contrainte de `composer.json`. Il établit que la porte est
ouverte et retire ce qui la bloquait ; la franchir est une décision distincte.

## Capabilities

### New Capabilities

- `compatibilite-php-8` : ce que le projet garantit sur son interpréteur — quelles
  versions la suite doit passer, et ce que le passage démontre ou non.

### Modified Capabilities

- `acces-au-site` : le nom d'affichage d'un contributeur sans profil reste servi sans
  erreur, sur toutes les versions de PHP supportées.

## Impact

- `src/lib/model/doctrine/sfDoctrineGuardPlugin/sfGuardUser.class.php` — la ligne fautive
- `src/lib/model/doctrine/PostTable.class.php` — l'origine du `null`, à documenter
- `.github/workflows/ci.yml` — l'exécution sous PHP 8.1
- `docs/modules/ROOT/pages/` — le verdict et sa portée
- Aucun changement de version de PHP en production
