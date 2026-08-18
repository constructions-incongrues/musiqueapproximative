## Why

La base a été migrée en `utf8mb4` le 2026-08-18, et la connexion aussi. **Rien ne permet de
constater que c'est encore vrai.**

`databases.yml` n'expose aucune valeur observable depuis l'extérieur : ni un navigateur, ni
le contrôle nocturne du contrat ne peuvent voir sa ligne `encoding: utf8mb4`. Si elle
disparaissait, la connexion se remettrait à convertir les caractères — et **personne ne le
saurait avant le prochain titre détruit.**

Ce n'est pas hypothétique. Le 2026-08-18, `make configure` lancé sur le serveur a réécrit
toute la configuration en gabarits bruts et mis le site en 500. Il a été rétabli, mais
l'épisode montre que ce fichier peut être écrasé par un geste d'exploitation ordinaire.

Et l'angle mort est plus large que le fichier. La story 20 a établi qu'**aucun morceau du
catalogue ne porte de caractère hors cp1252**, sur dix-huit ans. La production n'a donc
jamais eu l'occasion de démontrer que la migration fonctionne : la preuve existe dans la
suite de tests, pas en ligne. **Le premier titre cyrillique posté sera le test — et s'il
échoue, il sera détruit avant qu'on le sache.**

## What Changes

- Le site expose une vérification de l'encodage de sa connexion : ce qu'elle est, et si elle
  est celle attendue.
- Le rendez-vous nocturne existant l'interroge, au même horaire que le contrôle du contrat.
- La documentation d'exploitation dit où regarder quand la réponse est mauvaise.

Le contrat public n'est pas concerné : aucune route existante ne change.

## La question ouverte, tranchée

Le plan demandait de choisir entre une **tâche symfony** lançable à la main et un **point de
contrôle HTTP** interrogeable automatiquement, en notant que l'arbitrage de la story 23 ne
valait pas forcément ici.

**Point de contrôle HTTP**, pour la raison qui avait déjà tranché la story 23 : Plesk tire
`main` à chaque poussée, **il n'existe aucune étape de mise en ligne** où accrocher une
commande manuelle. Une tâche qu'il faut penser à lancer, dans un projet sans moment pour la
lancer, ne s'exécute pas — et l'incident du 2026-08-18 est arrivé précisément à quelqu'un
qui suivait un conseil pressé sans lire la documentation.

L'objection — « cela publie un détail d'infrastructure » — est réelle mais mince : la réponse
est un jeu de caractères, pas un secret, et le contrat OpenAPI publie déjà bien davantage sur
ce que ce site est et sert. La vérification est **en lecture seule** : elle interroge les
variables de session, elle n'écrit rien.

## Hors périmètre

- **Surveiller la base elle-même** — taille, disponibilité, réplication. C'est de la
  supervision, pas la vérification d'une décision de configuration.
- **Instrumenter les autres valeurs de `databases.yml`.** L'hôte, l'utilisateur et le mot de
  passe se signalent tout seuls : sans eux le site ne répond pas. L'encodage est le seul qui
  puisse être faux sans que rien ne tombe.
- **Prouver qu'une écriture de quatre octets survit.** Il faudrait écrire en production. La
  suite de tests le prouve déjà sur une base de test ; ce qui manquait en ligne, c'est le
  paramètre de connexion, et c'est lui qu'on rend observable.

<!-- incongru-voix: debord — « rendre l'encodage vérifiable » vérifie une variable, pas un titre — coût supporté par les 37 contributeurs, qui recevront un voyant vert au lieu d'une lettre -->

## Ce que ce voyant vérifie, et ce qu'il laisse croire

*Instruit par la voix `guy-debord`, sur le travail non commité, avant qu'il parte.*

### Le fait, mesuré avant d'argumenter

```
  character_set_connection                  = utf8mb4     conforme
  morceaux en ligne                         = 8 099
  titres portant des points d'interrogation = 61          détruits, toujours là
```

**Le voyant est déjà vert.** Il l'était avant qu'on écrive une ligne de ce change. Et il
cohabite, dans la même base, avec soixante et un titres mutilés qu'il ne voit pas et ne
verra jamais.

### La table d'inversion

Prise dans le vocabulaire du change lui-même, retournée contre son origine.

| Ce que le change dit | Ce que ça nomme |
| --- | --- |
| « rendre l'encodage **vérifiable** » | rendre une **variable** vérifiable. Un titre reste invérifiable, et c'est le titre qui était détruit. |
| verdict « **conforme** » | conforme à une configuration, pas à une promesse. La configuration était déjà conforme ce matin ; 61 titres étaient déjà morts. |
| « L'installation peut être **interrogée** » | interrogée sur ce qu'elle veut bien dire d'elle-même. On ne lui demande pas ce qu'elle a détruit — elle le sait pourtant, en 61 lignes. |
| « **trois** états, pas deux » | trois états d'une variable. Le quatrième — *je n'ai jamais eu l'occasion de le prouver* — est l'état réel, et il n'a pas de voyant. |
| « **lecture seule** », vertu de sûreté | lecture seule, donc incapable par construction de produire la seule preuve qui compte. La prudence technique et l'impuissance démonstrative sont ici le même geste. |
| « le premier titre cyrillique posté restera le **vrai test** » | l'aveu, écrit par le change lui-même : le vrai test est ailleurs, et il est laissé à un contributeur qui ne sait pas qu'il le passe. |
| un contrôle **quotidien**, jours ouvrés à 8 h | une régularité qui produit de la confiance sans produire de preuve. Vert cinq fois par semaine, cinquante-deux semaines par an, sur une question jamais posée. |

### Le coût, et qui le supporte

Ce n'est pas le mainteneur. Il gagne, lui : une inquiétude en moins, cochée chaque matin.

Ce sont **les 37 contributeurs**. On a mesuré aujourd'hui que leurs titres avaient été
détruits ; on a rédigé la page qui le dit ; on a écarté l'envoi du périmètre de la release.
Et on s'apprête à publier un voyant qui affichera *conforme* tous les matins, dans le même
dépôt, pendant que la lettre n'est pas partie.

Le voyant ne leur ment pas. Il ne leur parle pas — c'est autre chose, et c'est pire : il
occupe la place où la réparation aurait dû être visible.

### Ce que ça change dans le change, concrètement

Rien à interdire. La vérification est utile : une configuration qui peut être écrasée par un
`make configure` malheureux doit être observable, et elle l'était par personne.

Mais elle doit **dire ce qu'elle ne sait pas**, dans sa réponse et non dans un fichier de
plan que le rendez-vous nocturne ne lit pas :

- Le verdict porte sur la **connexion**, et le dit.
- La réponse porte **la date du dernier caractère hors cp1252 effectivement stocké**. Aucun,
  à ce jour, depuis dix-huit ans. Tant que ce champ est vide, le vert n'est pas un vert :
  c'est *configuration correcte, preuve jamais faite*.
- La réponse porte le **nombre de titres altérés** encore en base. Soixante et un. Un voyant
  qui affiche « conforme » à côté de ce nombre ne se lit plus de la même façon.

Ces trois lignes ne coûtent rien à écrire. Elles empêchent le verdict de valoir pour ce
qu'il ne vaut pas.

### Le point d'inconfort

Cette critique arrive à un travail qui avait déjà écrit sa propre limite, en section 5, sans
qu'on la lui demande. Un change qui dit lui-même ce qu'il ne prouve pas est déjà à l'abri du
pire reproche.

Reste que la limite était écrite là où elle ne sera pas lue, et que le voyant, lui, sera lu
tous les matins. Une honnêteté rangée dans le plan et une image publiée dans la production ne
pèsent pas pareil.
