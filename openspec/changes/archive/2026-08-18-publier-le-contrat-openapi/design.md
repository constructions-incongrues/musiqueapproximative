## Context

Le socle est un framework en fin de vie sur PHP 7.4, sans gestionnaire de paquets
applicatif utilisable pour ajouter une dépendance de développement. Le dépôt a déjà connu
une documentation de référence qui a dérivé : `docs/memory-bank/` a été supprimée « après
avoir dérivé au point de documenter cinq routes inexistantes ». Le contrat proposé ici court
exactement le même risque, avec une aggravation : il ment avec l'autorité d'un format.

Le taux d'amendement attendu est concentré : six amendements pendant le plan de release,
puis à peu près zéro. C'est après le plan que la dérive commence — d'où le poids donné au
mécanisme de vérification plutôt qu'au document lui-même.

## Goals / Non-Goals

**Goals :**

- Un document OpenAPI qui décrit l'état actuel et qu'un tiers puisse lire sans lire le code.
- Un mécanisme qui rend impossible qu'il dérive sans que quelqu'un s'en aperçoive.
- Un dispositif que le mainteneur puisse réparer seul : éditer un fichier, relancer un test.

**Non-Goals :**

- Générer le contrat depuis le code (annotations, introspection). Le coût de mise en place
  est disproportionné et le résultat serait illisible.
- Valider les corps de réponse. Un validateur JSON Schema est une dépendance nouvelle.
- Servir une interface de consultation.

## Decisions

### Le contrat est un fichier écrit à la main, pas un artefact généré

Alternatives écartées : annotations dans le code (`swagger-php` — dépendance, et le code
legacy n'est pas le bon endroit pour porter une description) ; génération depuis les
spécifications OpenSpec (elles disent le comportement, pas la forme de l'échange).

Un fichier YAML d'environ deux cents lignes est éditable par n'importe qui, sans étape de
compilation. Le prix de ce choix est qu'il peut mentir — d'où la décision suivante, qui est
la contrepartie non négociable.

### La vérification est un test fonctionnel qui lit le contrat comme donnée d'entrée

Le test n'écrit aucune liste de routes : il lit le document, itère sur ses `paths` et, pour
chacun, demande la route et compare statut et type de contenu à ce qui est déclaré. Écrire
la liste des routes dans le test la dupliquerait, et deux listes divergent.

Conséquence à connaître avant d'écrire le contrat : **une route ajoutée au document sans
être servie casse la suite**. C'est l'effet recherché.

### Le contrat est lu depuis le disque, pas demandé en HTTP

Le navigateur de test passe par le contrôleur frontal de l'application ; il ne sert pas les
fichiers statiques. Le test lit donc `src/web/openapi.yaml` sur le disque et se sert du
navigateur uniquement pour interroger les routes. L'analyseur YAML est celui déjà présent
dans le socle : aucune dépendance nouvelle.

Limite acceptée : le test ne démontre pas que le document est *servi* en HTTP à son adresse
publique. Cette partie relève de la vérification manuelle, comme pour `manifest.json`.

### Le domaine passe par `make configure`, comme `app.yml`

`src/web/openapi.yaml-dist` porte `${APP_DOMAIN}` ; la cible `configure` du `Makefile` de
`src/` fait déjà un `envsubst` sur tout fichier `*-dist` qu'elle trouve. Le fichier généré
rejoint `app.yml` et `databases.yml` dans `.gitignore`.

Conséquence sur le test : il doit tolérer que `src/web/openapi.yaml` soit **absent** sur un
poste où `make configure` n'a pas encore tourné. Dans ce cas il le dit et échoue clairement,
plutôt que de planter sur un fichier introuvable.

### Le contrat déclare les écarts au lieu de les corriger

`/posts?format=json` est servi en `application/vnd.api+json` là où `formats-de-sortie`
impose `application/json`. Le contrat déclare `application/vnd.api+json` : c'est ce que la
route sert. La story 1 du plan de release corrigera la route et amendera le contrat dans le
même mouvement — et le diff sera la trace de la correction.

C'est la frontière posée par le plan : **la spécification est normative, le contrat est
descriptif et vérifié. S'ils divergent, c'est le contrat qui a tort.**

## Risks / Trade-offs

- **Le contrat cesse d'être amendé après le plan de release, et dérive** → le test le rend
  impossible sur les deux axes qu'il couvre (existence des routes, type de contenu). Il
  reste possible qu'un corps de réponse dérive sans que rien n'échoue : c'est la limite
  assumée, et elle est écrite dans la spécification.
- **La description est décidée sans consommateur identifié** → la question ouverte n°3 du
  plan de release n'a jamais trouvé un seul appelant du JSON. Le bénéfice certain de ce
  changement n'est pas de servir des tiers hypothétiques mais de rendre lisibles les six
  ruptures que le plan déclare. Cela mérite d'être dit plutôt que masqué.
- **Le test allonge la suite d'autant de requêtes qu'il y a de routes déclarées** → neuf
  requêtes ; la suite en compte déjà plusieurs centaines. Négligeable.
- **Un `Content-Type` porte souvent un `charset`** → la comparaison doit porter sur le type
  de média, pas sur la chaîne entière, sans quoi le test échouera sur du bruit.

## Ce que le feu vert atteste, et ce qu'il laisse croire

<!-- incongru-voix: illich — seuil : 31 faits vérifiés sur ~65 déclarés (48 %), sous un seul feu vert — l'intégrateur -->

Le plan de release a déjà chiffré le contrat dans son ensemble : neuf heures, seuil
franchement sous 1 pendant le plan, et un basculement daté — le jour où les amendements
cessent. Ce relevé-ci porte sur autre chose, que ce calcul-là ne voit pas : **le test ne
vérifie pas tout ce que le document déclare, et rien ne dit au lecteur où passe la ligne.**

### Le décompte

| ce que le document déclare | volume | vérifié par le test |
| --- | --- | --- |
| Existence des routes (16 combinaisons route × format) | 16 | oui |
| Codes de statut | 16 | oui |
| Types de contenu | 15 | oui |
| Paramètres de requête (`format`, `q`, `c`, `play`, `url`…) | ~9 | **non** |
| Schémas de corps (morceau ~17 champs, liste, oembed, xspf) | ~25 | **non** |
| **Total** | **~65** | **31 — soit 48 %** |

Un seul feu vert couvre les deux moitiés. La suite est verte, et elle atteste trente et un
faits sur soixante-cinq — sans que le document n'ait aucun moyen de dire lequel des deux
régimes s'applique à la ligne qu'on est en train de lire.

### Où ça s'inverse

Le but du contrat est qu'un intégrateur le lise au lieu de lire le code. Voici le seuil,
et il est atteint dès la première divergence de schéma :

- **sans contrat**, il appelle la route et regarde ce qui revient. Coût : une requête.
- **avec un contrat juste**, il écrit son analyseur d'après le document. Coût : zéro requête.
- **avec un contrat faux sur un schéma**, il écrit son analyseur d'après le document, son
  code casse en production, et il débogue contre un document qui porte l'autorité d'un
  format **et** un feu vert de CI. Coût : la requête qu'il n'a pas faite, plus le temps de
  cesser de croire le document.

Le troisième cas coûte plus que le premier. C'est la définition du second seuil : passé ce
point, l'outil produit l'inverse de son but. Il n'y faut pas une dérive générale du
document — **une seule ligne de schéma périmée suffit**, parce que rien ne la distingue des
lignes vérifiées.

### Le sous-calcul des schémas

| poste | estimation |
| --- | --- |
| Écrire les schémas de corps (~25 champs, trois formats) | ~1 h 30 sur les ~4 h du contrat |
| Les tenir à jour sur les six amendements du plan | ~1 h |
| Temps rendu, vérifié | **0** |

Le seuil ne se calcule pas : le dénominateur est nul. Deux heures et demie englouties pour
une part du document dont rien n'établit jamais qu'elle est vraie. Ce n'est pas un argument
pour la supprimer — c'est un argument pour sortir le dénominateur de zéro, ce qui est
faisable pour peu cher.

### Qui supporte le coût

Pas le mainteneur : le test le protège exactement sur ce qu'il change le plus — statuts et
types de contenu, c'est-à-dire les six amendements que le plan déclare. Il verra rouge
quand il doit. **C'est l'intégrateur qui paie**, et c'est le persona que cette story
prétend servir. C'est aussi celui dont la question ouverte n°3 dit qu'on ne l'a jamais
trouvé.

### Verdict de convivialité, du point de vue du lecteur du contrat

Le plan de release a posé les trois questions au mainteneur et obtenu trois oui. Posées au
lecteur du document, l'une bascule.

| question | réponse | ce qui la fonde |
| --- | --- | --- |
| Comprend-il comment ça marche ? | oui | ~200 lignes de YAML dans un format que toute l'industrie lit. |
| Peut-il le réparer ? | oui, s'il est contributeur | Éditer le fichier, relancer le test. |
| **Sait-il ce qui est garanti et ce qui ne l'est pas ?** | **non** | Rien dans le document ne distingue les 48 % vérifiés du reste. |

### Ce que ce calcul décide

Trois options, par coût croissant :

1. **Ne pas déclarer de schémas du tout.** Le contrat se limite à ce que le test couvre ou
   pourrait couvrir. Coût négatif — il retire du travail. Prix : l'intégrateur obtient
   moins, mais rien de faux.
2. **Déclarer les schémas et marquer la part non vérifiée dans le document lui-même**, là où
   le lecteur est — un `description:` sur chaque schéma, disant qu'il n'est pas confronté au
   site. Coût : ~15 min. Une note en `tasks.md` ne remplit pas cet office : personne ne lit
   les tâches d'un change archivé.
3. **Vérifier la présence des clés de premier niveau** que les schémas déclarent. Le test a
   déjà le corps de la réponse en main ; il n'a besoin d'aucun validateur — décoder et
   constater la présence des clés suffit. Coût : ~30 min. Rendement : environ huit des ~25
   faits de schéma passent du côté vérifié, et le dénominateur cesse d'être nul.

**Retenu : 2 et 3.** Ensemble, ils portent la couverture de 48 % à ~60 % et rendent la
frontière lisible par celui qui la subit. La validation complète des corps reste écartée,
pour la raison déjà écrite dans la proposition : elle demande une dépendance que le socle
ne peut pas porter.

### Le point d'inconfort

Ce relevé conclut « gardez les schémas, marquez-les ». C'est confortable : ça ne retire
rien à personne. La lecture inconfortable est l'option 1 — **si aucun consommateur du JSON
n'a jamais été identifié, écrire vingt-cinq champs de schéma que rien ne vérifie est du
travail fait pour l'idée qu'on se fait d'un lecteur, pas pour un lecteur.** Le poste qui
tient debout sans lui est celui que le plan a déjà nommé : forcer les décisions différées.
Les schémas n'en forcent aucune.

## Ce que le mot « vérifié » vend

<!-- incongru-voix: debord — « VÉRIFIÉ par le test » : 60 faits sur 88, le badge couvre les 28 autres — le lecteur qui croit le document sur parole -->

Le document supprimé par ce changement portait en tête : *Auto-generated analysis*. Il
décrivait neuf routes qui n'existaient pas. Rien n'était généré, rien n'était analysé ; la
mention faisait le travail que le travail n'avait pas fait, et elle l'a fait pendant seize
mois sans que personne y revienne — parce qu'un badge d'automatisation dispense de lire.

Le document qui le remplace s'ouvrait, avant cette révision, sur le mot **VÉRIFIÉ** en
capitales. Même grammaire. La différence tient à un chiffre, et le chiffre était absent.

### Le décompte, relevé sur le contrat lui-même

| ce que le document déclare | volume | le test le vérifie |
| --- | --- | --- |
| Routes | 9 | oui |
| Codes de statut | 17 | oui |
| Types de contenu | 16 | oui |
| Champs de premier niveau des réponses JSON | 18 | oui |
| Champs de schéma au-delà du premier niveau | 19 | **non** |
| Paramètres déclarés jamais exercés (`q`, `c`, `play`, `embed`, `random`, `contributor`, `count`…) | 9 | **non** |
| **Total** | **88** | **60 — soit 68 %** |

Le relevé d'Illich plus haut chiffrait le même dispositif avant qu'il existe, et concluait
à 48 %. Les mentions `NON VÉRIFIÉ` portées sur chaque schéma et le contrôle des champs de
premier niveau ont porté la couverture à 68 %. Ce n'est pas le point.

### Le point

Les 32 % restants ne sont pas le problème — ils sont écrits, et chaque schéma dit lesquels.
Le problème était l'ordre de lecture. On lit l'en-tête avant les schémas. **VÉRIFIÉ** en
capitales, en tête, sans dénominateur, s'étend par défaut à tout ce qui suit : c'est
précisément ce que « Auto-generated » faisait, et ce document venait d'en hériter la place.

Un badge ne ment pas ; il dispense de vérifier. C'est plus efficace.

### Ce que ça a changé

L'en-tête du contrat porte désormais la proportion — 60 sur 88 — le détail de ce qui tombe
de chaque côté, et le rappel du document qu'il remplace. Le mot reste ; il ne s'étend plus
tout seul.

**Ce qui n'a pas changé, et qu'il faut dire** : neuf paramètres sont déclarés que rien
n'exerce. Leur présence atteste qu'ils ont été lus dans le code, pas qu'ils se comportent
comme il est écrit. C'est exactement la nature de ce qui a fait dériver le document
précédent — de la lecture consignée comme du constat. La différence est qu'ici c'est écrit
en toutes lettres, à l'endroit où on le lit.

## Migration Plan

Aucune migration : rien de servi ne change. Le retour en arrière consiste à supprimer trois
fichiers.

## Open Questions

Aucune bloquante. La valeur par défaut de la pagination — question ouverte n°1 du plan de
release — **ne se pose pas ici** : le contrat décrit l'état actuel, où aucun paramètre de
bornage n'existe. Elle sera tranchée par la story 2, qui amendera le contrat.
