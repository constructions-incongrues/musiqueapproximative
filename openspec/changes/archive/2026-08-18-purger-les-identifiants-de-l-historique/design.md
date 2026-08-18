## Context

207 empreintes SHA1 salées et 171 courriels, dans l'historique d'un dépôt public, depuis des
années. Le dépôt compte **4 forks**, 46 branches distantes et 7 observateurs.

Une réécriture d'historique est le geste que la situation appelle en premier réflexe. C'est
aussi celui qui produit le plus facilement une fausse tranquillité, et le relevé ci-dessus
explique pourquoi.

## Goals / Non-Goals

**Goals :** que les identifiants exposés cessent d'ouvrir quoi que ce soit ; que les
personnes concernées le sachent ; que l'exposition cesse de se propager à chaque nouveau
clone.

**Non-Goals :** effacer ce qui est déjà copié — c'est impossible et le prétendre serait
malhonnête ; changer l'algorithme de hachage de sfGuard.

## Decisions

### Invalider d'abord, réécrire ensuite — et l'ordre n'est pas une préférence

Une réécriture d'historique **ne récupère rien**. Les objets sont clonés sur tous les postes
qui ont tiré le dépôt, présents dans **4 forks** qu'aucune réécriture locale n'atteint, et
indexés par qui a voulu les indexer. Ils circulent depuis des années.

Ce qui change réellement l'exposition, c'est que les empreintes cessent d'ouvrir un compte.
Tout le reste vient après.

`sf_guard_user.algorithm` vaut `sha1`. Ce n'est pas un détail de configuration : une
empreinte SHA1 salée, dont le sel figure **dans le même dump**, se casse à la vitesse d'un
processeur graphique. Il faut traiter ces mots de passe comme connus, non comme protégés.

### Prévenir n'est pas une politesse

171 personnes ont vu leur adresse et l'empreinte de leur mot de passe publiées, sans le
savoir et sans y avoir consenti. Beaucoup réemploient probablement ce mot de passe ailleurs —
c'est la raison pour laquelle la fuite compte au-delà de ce site.

Les prévenir est ce qui leur rend la possibilité d'agir. C'est aussi la seule partie de ce
changement qu'un dépôt ne peut pas faire à leur place.

### La réécriture reste utile, pour ce qu'elle est

Elle ne répare pas le passé ; elle arrête la **propagation**. Chaque nouveau clone, chaque
nouvelle personne qui découvre le projet, cesse de recevoir les identifiants avec le code.

C'est un gain réel et modeste. Le présenter comme une réparation serait le mensonge que ce
change doit éviter.

Outil : `git filter-repo`, qui remplace `filter-branch` et que GitHub documente pour cet
usage. Le contenu final de l'arbre ne change pas — le dump actuel est déjà anonymisé ; seuls
les blobs historiques sont réécrits.

### Le ramasse-miettes de GitHub demande une demande explicite

Après une poussée forcée, les anciens objets restent accessibles **par leur empreinte** tant
que le service ne les a pas collectés. Une PR fermée qui les référence suffit à les garder
vivants. Seul le support peut déclencher la purge, et il faut la demander.

Sans cette étape, la réécriture ne retire rien de public — elle rend seulement les objets
moins faciles à trouver.

### Ce que les forks imposent, et qu'on ne peut pas contourner

Les 4 forks portent l'historique complet et n'appartiennent pas à ce dépôt. Aucune
réécriture ne les touche. Leurs propriétaires peuvent être contactés ; ils ne peuvent pas
être contraints.

C'est la limite qui doit figurer dans la conclusion de ce change, sans quoi il annoncerait
une purge qu'il n'accomplit pas.

## Risks / Trade-offs

- **La réécriture est prise pour une réparation** → c'est le risque principal, et il est
  humain, non technique. D'où l'ordre imposé : invalider, prévenir, puis réécrire.
- **46 branches et 4 forks divergent** → toute branche non fusionnée doit être reprise sur
  le nouvel historique. Coût de coordination réel, à porter avant de commencer.
- **Une poussée forcée sur `main` déclenche le déploiement** → Plesk tire `main`. Le contenu
  final étant identique, l'effet devrait être nul, mais « devrait » n'est pas « est » :
  vérifier le site après.
- **La release-please en cours** (PR #149) sera invalidée et devra être régénérée.

## Migration Plan

1. Invalider les mots de passe : forcer leur renouvellement, ou les réinitialiser.
2. Prévenir les personnes concernées.
3. Sauvegarder le dépôt — un clone miroir complet, vérifié.
4. Fusionner ou fermer ce qui peut l'être, pour réduire le nombre de branches à reprendre.
5. `git filter-repo` sur les blobs des deux dumps.
6. Poussée forcée, puis reprise des branches restantes.
7. Demander la purge au support GitHub.
8. Prévenir les propriétaires des 4 forks.

Retour arrière : le clone miroir de l'étape 3.

## Open Questions

**Est-ce que la réécriture vaut son coût ?** Elle arrête la propagation et ne répare rien.
Les étapes 1 et 2 valent d'être menées quoi qu'il arrive ; les suivantes se discutent, et
cette décision appartient à l'auteur. Ce change les sépare précisément pour qu'elle puisse
être prise.
