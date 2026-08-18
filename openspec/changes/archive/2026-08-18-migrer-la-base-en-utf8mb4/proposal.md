## Why

La base de production est en `latin1`. Tout caractère hors cp1252 saisi par un contributeur
est remplacé par `?` à l'écriture, définitivement : **81 morceaux** ont déjà un titre ou un
auteur détruit, **37 contributeurs** sont concernés, et **cinq dégâts datent de 2026**. Le
site publie quotidiennement — le dommage est en cours.

La story 18 a livré le test qui dit ce que le site doit faire. Il passe en environnement de
test pendant que la production reste cassée. Cette story ferme l'écart.

## What Changes

- Les tables passent en `utf8mb4` / `utf8mb4_unicode_ci` : `post`, `post_index`,
  `user_profile` et les sept tables `sf_guard_*`.
- La connexion de production déclare `encoding: utf8mb4`, comme celle de test.
- Un script de migration versionné au dépôt, **exécuté à la main** : le déploiement ne lance
  aucune migration, Plesk se contentant de tirer `main`. Personne d'autre que le détenteur
  des accès ne peut le lancer, et le dire est la moitié du travail.
- Un contrôle préalable qui refuse de s'exécuter si la base n'est pas dans l'état attendu.

## L'ordre compte, et il n'est pas intuitif

**Convertir les tables d'abord, changer l'encodage de connexion ensuite.**

Aujourd'hui la connexion négocie déjà `utf8` — `sfDoctrineDatabase` applique ce défaut sans
qu'on l'ait demandé — et la base est en `latin1` : MySQL convertit à chaque écriture, et
c'est là que le caractère se perd.

`CONVERT TO CHARACTER SET utf8mb4` réinterprète correctement les octets latin1 existants et
les ré-encode. Entre la conversion et le changement d'encodage, l'application lit des tables
`utf8mb4` sur une connexion `utf8` : MySQL reconvertit, et `utf8` couvrant tout le plan
multilingue de base, rien de l'existant ne se perd. La fenêtre est donc sûre.

**L'inverse ne l'est pas** : déclarer `encoding: utf8mb4` avant de convertir ferait envoyer
de l'utf8mb4 vers des colonnes latin1, c'est-à-dire exactement le mécanisme qui détruit
aujourd'hui, en pire.

## Capabilities

Aucune. Le comportement attendu est déjà spécifié — `catalogue-morceaux`, « Le morceau est
restitué tel qu'il a été saisi », écrit par la story 18. Cette story met la production en
conformité avec une exigence existante ; elle n'en crée pas. `skip_specs` est déclaré en
conséquence.

## Hors périmètre

- **Les 81 morceaux déjà détruits.** Aucune migration ne les rend : le `?` a remplacé
  l'octet à l'écriture. C'est la story 20.
- **Le garde-fou à la saisie**, qui perd son objet une fois cette story livrée.
- **La base de développement locale**, qui porte un dump vieux de cinq ans et n'est pas la
  production.
- **Toute autre correction de schéma** rencontrée en chemin.

## Impact

- **Modifié** : `src/config/databases.yml-dist` (le bloc `all`).
- **Ajouté** : un script SQL de migration et sa documentation d'exécution.
- **Non modifié** : le code applicatif.
- **Exécution manuelle requise** : la fusion sur `main` déploie le code mais **ne convertit
  rien**. Tant que le script n'est pas lancé, la connexion déclarera `utf8mb4` face à des
  tables `latin1` — état plus dangereux que l'actuel. L'ordre des deux gestes est donc une
  contrainte de livraison, pas une préférence.
