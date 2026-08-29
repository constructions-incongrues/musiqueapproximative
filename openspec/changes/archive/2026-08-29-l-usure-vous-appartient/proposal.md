## Why

Dix-neuf recettes de désastre sont déclarées, et **aucune ne se souvient de quoi que ce
soit** : `localStorage` est à zéro sur dix-neuf. Chaque visite repart de zéro, ce qui
interdit tout cumul, toute escalade, et toute forme de « vous êtes déjà venu ».

La bande usée (story 33) fait flotter la hauteur d'un morceau comme une bande magnétique
passée trop de fois, et son intensité suit l'âge du morceau (story 35). Elle décrit donc
une usure que **personne n'a provoquée** : elle est arrivée toute seule, avec les années.
Ce change ferme la boucle que le nom promet — c'est *votre* écoute qui use la bande. Le
dixième passage est plus fatigué que le premier, pour vous seul.

Ce qui change n'est pas le rendu mais la nature du geste : le désastre cesse d'être un
accident tombé sur une page et devient la conséquence d'un acte du visiteur. Aucune autre
recette du catalogue n'établit ce rapport.

## What Changes

- L'intensité de la bande usée **s'ajoute** une part tirée du nombre d'écoutes de ce
  morceau **par ce navigateur**. La part liée à l'âge, elle, ne change pas : les deux se
  composent, la seconde ne remplace pas la première.
- Un compteur d'écoutes est tenu **par morceau et par navigateur**, dans `localStorage`.
- **Un plafond** borne l'usure cumulée. Sans lui, l'habitué finit par ne plus pouvoir
  écouter le morceau qu'il aime — le désastre punirait exactement les gens qui reviennent
  le plus.
- **L'usure s'oublie.** Elle décroît avec le temps écoulé depuis la dernière écoute. C'est
  la question que le packet de la story 36 laissait ouverte : *est-ce que l'usure se
  répare ?* Réponse retenue : **oui, lentement**. Une bande magnétique ne guérit pas, mais
  la fidélité au matériau ne vaut pas qu'on rende un morceau définitivement inécoutable à
  celui qui l'écoute le plus. Le désastre est un ornement, pas une sanction.
- Rien de tout cela ne quitte le navigateur : aucune remontée au serveur, aucun
  identifiant, aucune synchronisation entre appareils. Un visiteur qui vide son stockage
  repart neuf, et c'est très bien ainsi.

**Le contrat public n'est pas concerné.** Aucune route, aucun format, aucun en-tête ne
change. Le document servi reste identique — voir « Contrainte du socle » ci-dessous.

### Contrainte du socle qui oriente la solution

La spec `desastres` exige, sous « L'invariance est préservée », qu'une page tirée et mise
en cache serve **un corps de document identique** à chaque requête. La mémoire ne peut donc
en aucun cas être calculée côté serveur ni écrite dans la page : elle serait un corps qui
varie, ce que la spec interdit et que le test `desastreInvarianceTest` vérifie.

Cette contrainte **ne limite pas** le change, elle le confirme : le périmètre de la story
plaçait déjà tout dans le navigateur. Elle est écrite ici pour que le prochain lecteur
sache que c'est une exigence, et non un choix d'implémentation qu'on pourrait revisiter.

## Capabilities

### New Capabilities

Aucune.

La mémoire locale aurait pu être posée comme une capacité à part — « un désastre peut se
souvenir ». Elle ne l'est pas : elle n'a qu'un seul consommateur, et une capacité écrite
pour un seul cas décrit une implémentation plutôt qu'un comportement. Si une seconde
recette réclame de la mémoire, elle sera extraite à ce moment-là, sur deux cas réels.

### Modified Capabilities

- `desastre-sonore` : l'intensité de l'altération ne dépend plus seulement de l'âge du
  morceau, mais aussi du nombre de fois où **ce navigateur** l'a écouté. S'y ajoutent le
  plafond qui garde le morceau écoutable et l'oubli qui rend l'usure réversible.

## Impact

- `src/web/desastres/bande-usee/javascript/bande-usee.js` — le calcul d'intensité, qui lit
  aujourd'hui la seule date de publication (`intensiteSelonAge`), lit aussi le compteur.
- `src/web/desastres/bande-usee/README.adoc` — la documentation de la recette.
- `docs/modules/ROOT/pages/desastres.adoc` — les options de la recette, si le plafond et la
  vitesse d'oubli y sont exposés.
- `openspec/specs/desastre-sonore/spec.md` — via le delta de ce change.
- Le processeur `worklet/bande-usee-processeur.js` **n'est pas touché** : il reçoit déjà
  une intensité en paramètre, il continue de la recevoir.

Aucun changement côté serveur : ni action, ni gabarit, ni configuration de désastre, ni
schéma de données.

## Ce que ce change promulgue, et qui peut y faire appel

```
// incongru-voix: lessig — usure personnelle cumulative du morceau le plus écouté
// régulée par architecture — recours: ?sans-desastre, effectif mais non notifié
```

Ce change ne règle pas un conflit : il inscrit une règle dans du code, appliquée à des
visiteurs qui n'ont rien signé et ne seront pas prévenus. Les quatre modalités, remplies :

```
CONTRAINTE : le morceau qu'un visiteur écoute le plus est celui qu'il entend
             le plus dégradé — et la dégradation le suit d'une visite à l'autre.

  loi           rien. Aucune condition d'utilisation, aucun bandeau, aucun
                consentement. Le stockage est local, non partagé, non corrélé,
                à finalité ornementale : le droit ne s'y intéresse pas. Ce qui
                veut dire qu'il n'offre aucune voie de recours non plus.

  norme         rien. Aucun usage du web ne sanctionne un site qui dégrade son
                propre média. C'est même la signature revendiquée de celui-ci.

  prix          connaître l'existence de `?sans-desastre`. Pour qui l'ignore,
                le prix du contournement est infini — c'est la définition d'une
                règle sans dérogation.

  architecture  totale. Tout se passe là, et rien qu'là.

  RECOURS       `?sans-desastre` dans l'adresse. Retire tout, son compris, et
                — par l'effet du retour anticipé en tête de `bande-usee.js` —
                empêche aussi le comptage. La sortie EXISTE et FONCTIONNE.
                Elle n'est documentée que dans le README de la recette et dans
                `docs/modules/ROOT/pages/desastres.adoc`, c'est-à-dire dans la
                documentation technique. Le pied de page du site, qui énumère
                pourtant cinq raccourcis clavier (`espace`, `j`, `k`, `r`, `s`),
                n'en dit pas un mot.
```

**Le constat tient en une ligne : le recours existe et n'est pas notifié.** Ce n'est pas
un défaut de conception — quelqu'un a vu le problème et l'a résolu, la spec
`desastre-sonore` exigeait déjà « une sortie fournie autrement, et documentée », et elle
l'est. Elle l'est pour un mainteneur. Une dérogation qu'un visiteur ne peut pas découvrir
est, de son point de vue, une règle sans dérogation.

**Ce que ce change ajoute à l'affaire.** L'usure d'âge est une propriété du morceau :
identique pour tous, elle ne dit rien de personne. L'usure d'écoute est un enregistrement
**sur le visiteur**, conservé sur sa machine, et employé contre sa propre écoute. Dix-neuf
recettes, zéro mémoire — la proposition s'en félicite en ouverture. Ce change est donc le
premier à tenir une trace de qui écoute quoi. Elle ne quitte pas le navigateur, elle
n'identifie personne, et l'enjeu est modeste : c'est un ornement sur un site de partage
musical, pas une plateforme. Mais c'est un précédent, et un précédent se déclare.

**La règle aurait-elle été votée si elle avait été présentée comme une règle ?**
Probablement oui — « plus vous aimez un morceau, plus il s'use » est exactement le geste
que le site revendique, et il est réussi. Ce n'est donc pas la règle qui pose problème,
c'est que la porte de sortie soit invisible depuis la pièce.

**Ce qui en découle, et rien de plus.** Rendre `?sans-desastre` trouvable depuis le site
lui-même — une ligne dans le bloc « Raccourcis » du pied de page, à côté des cinq autres.
C'est la correction proportionnée.

**Ce qui n'en découle pas :** une seconde sortie propre à la mémoire (« gardez les
désastres, ne vous souvenez pas de moi »). Elle serait défendable, mais elle ajouterait un
paramètre pour une seule recette alors que la sortie existante couvre déjà le cas. Le
signaler comme option écartée suffit.


## Hors périmètre

- **Toute synchronisation entre appareils, et toute remontée au serveur.** Le compteur
  reste dans le navigateur qui l'a produit. Rien n'est envoyé, rien n'est corrélé, aucun
  visiteur n'est identifié.
- **Doter les dix-huit autres recettes de mémoire.** Ce change en écrit une, pas un
  mécanisme partagé. L'abstraction viendra du second cas, si le second cas vient.
- **Réviser la part d'usure liée à l'âge**, sa courbe quadratique, son plancher ou sa
  constante de dix-huit ans. Ils sont issus d'une mesure sur le catalogue réel (story 35)
  et ne sont pas rouverts ici.
- **Modifier le tirage, les règles ou les probabilités** qui décident quand la bande usée
  s'applique. Le désastre se déclenche exactement comme aujourd'hui.
- **Rendre l'usure visible par un compteur affiché.** Le geste vaut parce qu'il se
  remarque à l'oreille, pas parce qu'un chiffre l'annonce.
- **Toucher au fichier audio.** Il reste téléchargeable et lisible tel qu'il est publié,
  comme l'exige déjà `desastre-sonore`.
- **Une sortie propre à la mémoire**, distincte de `?sans-desastre`. Écartée : la sortie
  existante retire déjà tout, comptage compris. Un second paramètre pour une seule recette
  coûterait plus qu'il ne rapporte.
