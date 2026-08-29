## 1. Le compteur d'écoutes

- [x] 1.1 Écrire dans `src/web/desastres/bande-usee/javascript/bande-usee.js` la lecture de
      la mémoire : `localStorage["desastres:bande-usee:usure"]`, analysé en JSON, indexé par
      slug du morceau (D3). Tout accès enveloppé dans un `try` ; en cas d'échec, renvoyer
      une usure nulle et laisser le désastre fonctionner sur le seul âge (R3).
- [x] 1.2 Écrire la décroissance à la lecture : `usure * Math.pow(2, -jours / DEMI_VIE)`,
      calculée depuis l'horodatage stocké, sans écriture (D2).
- [x] 1.3 Écrire l'incrément : au premier démarrage de lecture de ce chargement de page,
      ajouter l'âge virtuel d'une écoute et remettre l'horodatage à maintenant (D4). Un
      drapeau au niveau du module empêche qu'une pause suivie d'une reprise compte deux fois.
- [x] 1.4 À l'écriture, purger les entrées dont l'usure décroît sous un seuil négligeable,
      afin que le stockage se borne au répertoire réellement écouté (R2, question ouverte 2).
- [x] 1.5 Retrouver le slug depuis l'adresse de la page (`/post/:slug`) sans ajouter de
      balise au gabarit — la date de publication, elle, en avait exigé une.

## 2. La composition avec l'âge

- [x] 2.1 Modifier `intensiteSelonAge()` pour verser l'usure en jours **avant** la courbe :
      `part = min(1, (jours + usure) / (referenceAns * 365,25))` (D1). Ne toucher ni au
      plancher, ni à l'exposant, ni à la constante de dix-huit ans.
- [x] 2.2 Renommer la fonction pour qu'elle dise ce qu'elle calcule désormais — elle ne suit
      plus le seul âge — et mettre à jour son commentaire d'en-tête.
- [x] 2.3 Ajouter au message de console existant l'usure appliquée et le nombre d'écoutes,
      à côté de l'âge déjà journalisé. C'est le seul moyen de vérifier le cumul sans mesure.
- [x] 2.4 Exposer `ageVirtuelParEcouteAns` et `demiVieOubliJours` en options de la recette,
      lues comme le sont déjà `plancherUsure` et `referenceAns`, avec leurs valeurs par
      défaut (1 an, 30 jours).

## 3. La configuration et la documentation

- [x] 3.1 Déclarer les deux options dans
      `src/apps/frontend/config/desastres/recettes/bande-usee.yml`, avec un commentaire
      disant explicitement qu'elles sont **provisoires et non mesurées**, contrairement à
      `wowHz`, `flutterHz` et `profondeurMs` (R4).
- [x] 3.2 Compléter `src/web/desastres/bande-usee/README.adoc` : ce que l'usure personnelle
      ajoute, qu'elle ne quitte pas le navigateur, qu'elle s'oublie — et **que les morceaux
      de dix-huit ans et plus n'en portent pas la marque, étant déjà au plafond** (R1). Le
      dire ici évite qu'on le découvre comme un défaut.
- [x] 3.3 Reporter les options dans `docs/modules/ROOT/pages/desastres.adoc` si cette page
      documente les options des recettes ; sinon, ne rien y ajouter et le noter ici.

## 4. La sortie, et sa notification

> Dérivé de l'analyse « Ce que ce change promulgue » de la proposition. `?sans-desastre`
> existe et fonctionne, mais n'est documenté que dans le README de la recette et la
> documentation Antora — jamais là où un visiteur regarde.

- [x] 4.1 Vérifier que le retour anticipé de `bande-usee.js` (test de `?sans-desastre`, en
      tête du module) reste **avant** toute lecture ou écriture du compteur. C'est ce qui
      fait que la sortie suspend aussi la mémoire ; l'ordre est aujourd'hui correct par
      accident, il devient une exigence.
- [x] 4.2 Ajouter `?sans-desastre` au bloc « Raccourcis » du pied de page
      (`src/apps/frontend/templates/layout.php`), à côté de `espace`, `j`, `k`, `r`, `s`.
      Une ligne, formulée pour un visiteur et non pour un mainteneur.
- [x] 4.3 Vérifier que la formulation retenue dit ce que la sortie retire — **tout, son
      compris** — et non seulement qu'elle existe.

## 5. Vérification automatisée

- [x] 5.1 Vérifier que `src/test/functional/frontend/desastreInvarianceTest.php` reste vert :
      le corps du document ne doit pas avoir bougé d'un octet (D5).
- [x] 5.2 Exécuter la suite complète : `docker-compose exec php php symfony test:all`.
- [x] 5.3 Lancer `openspec validate l-usure-vous-appartient --type change --strict`.

## 6. Vérification manuelle

> **ARCHIVÉ AVEC TROIS CASES DÉCOCHÉES — 2026-08-29.** L'auteur a confirmé n'avoir pas
> écouté, et a demandé l'archivage en l'état. Les cases 6.1, 6.10 et 6.11 ne sont donc PAS
> cochées : une case cochée signifie vérifiée, jamais « probablement bon ».
>
> Ce qui reste dû, si quelqu'un veut fermer ce dossier :
> **6.1** l'usure s'entend-elle entre la 1ʳᵉ et la 10ᵉ écoute · **6.10** le chemin lecture
> quand `localStorage` est indisponible · **6.11** les deux réglages provisoires
> (`ageVirtuelParEcouteAns`, `demiVieOubliJours`) tiennent-ils à l'oreille.
>
> Repère pour celui qui écoutera : un morceau récent passe de 0,350 à **0,552 vers dix
> écoutes** et **sature vers dix-neuf**. Si le plafond arrive trop vite, baisser
> `ageVirtuelParEcouteAns`.

> **Ce qui a été vérifié, et comment.** Les vérifications de LOGIQUE ont été menées dans un
> navigateur sur `http://localhost:8001`, désastre forcé par `?bande_usee`, en provoquant
> l'événement `play` de l'élément audio — c'est exactement l'écouteur auquel le comptage est
> branché. Les valeurs citées ci-dessous sont relevées, pas supposées.
>
> **Ce qui n'a PAS pu être vérifié : tout ce qui passe par l'oreille.** Le fichier audio est
> servi par nginx sur le port 8080, qui n'a pas démarré dans ce worktree — le port était déjà
> pris. Aucun son n'a été entendu. Les cases 6.1 et 6.11 restent donc décochées, et 6.10 ne
> l'est qu'à moitié. Elles attendent quelqu'un avec des enceintes.

Ces vérifications ne sont couvertes par aucun test : la suite est en PHP et ne traverse pas
`localStorage`. Elles se font dans un navigateur, sur le développement
(`http://localhost:8080`), et le forçage d'une recette se fait par le paramètre d'URL que
documente la capacité `desastres` (`?bande_usee`).

- [ ] 6.1 **L'usure s'accumule.** Ouvrir un morceau **récent** en forçant la recette, lancer
      la lecture, noter l'usure journalisée en console. Recharger et relancer dix fois.
      *Attendu* : la valeur journalisée croît à chaque fois, et l'oreille entend le
      flottement s'accentuer entre la première et la dixième.
- [x] 6.2 **Une page ouverte sans écoute n'use rien.** Recharger la même page cinq fois sans
      jamais lancer la lecture. *Attendu* : l'usure journalisée ne bouge pas. *Vérifié* : `localStorage` reste `null` après ouverture sans lecture.
- [x] 6.3 **Une pause ne compte pas double.** Lancer la lecture, mettre en pause, reprendre.
      *Attendu* : une seule écoute comptée pour ce chargement de page. *Vérifié* : deux `play` sur le même chargement → une seule entrée, `n: 1`, horodatage inchangé.
- [x] 6.4 **Les morceaux sont indépendants.** Écouter un second morceau, de même époque, une
      seule fois. *Attendu* : son usure est celle d'une première écoute, celle du premier
      morceau est intacte. *Vérifié* : `un-autre-morceau` est resté à `j: 3652, n: 10` pendant que le premier montait à `n: 501`.
- [x] 6.5 **L'oubli.** Dans la console, reculer à la main l'horodatage stocké de soixante
      jours, puis recharger. *Attendu* : l'usure journalisée vaut environ le quart de sa
      valeur (deux demi-vies), et le flottement diminue. *Vérifié* : 3 652 jours reculés de 60 jours (deux demi-vies) → 913 jours, ratio **0,250** exactement.
- [x] 6.6 **Le plafond.** Porter à la main l'usure stockée à une valeur énorme, puis
      recharger. *Attendu* : le morceau reste audible du début à la fin, et l'intensité
      journalisée ne dépasse pas 1. *Vérifié* : usure portée à 10⁹ jours → intensité **exactement 1**, jamais au-dessus, et le worklet tourne toujours (53 messages reçus).
- [x] 6.7 **Le morceau ancien ne bouge pas, et c'est prévu.** Répéter 6.1 sur un morceau de
      2008. *Attendu* : l'intensité reste à 1 dès la première écoute — c'est R1, à confronter
      à ce que le README annonce. *Vérifié* sur `coffee-giuniu` (publié le 2008-06-10) : intensité **1 à vide et 1 après 100 écoutes**. R1 est exactement ce qui était annoncé.
- [x] 6.8 **Rien ne remonte.** Onglet réseau ouvert, écouter trois fois le même morceau.
      *Attendu* : aucune requête ne porte de décompte d'écoutes vers le site. *Vérifié* : sur quatre chargements, aucune requête ne porte de décompte — que des GET de pages et de ressources statiques.
- [x] 6.9 **Un stockage effacé rend le morceau neuf.** Vider le stockage du site, recharger
      un morceau usé. *Attendu* : l'usure journalisée repart de zéro. *Vérifié* : après `localStorage.clear()`, usure 0 et intensité au plancher 0,350.
- [ ] 6.10 **Le stockage indisponible ne casse rien.** Ouvrir la page dans une fenêtre privée
      stricte, ou désactiver le stockage. *Attendu* : le désastre s'applique quand même, sur
      l'usure d'âge seule, et aucune erreur ne remonte en console (R3).
      **À moitié vérifiée, donc décochée.** Chemin ÉCRITURE : avec un `localStorage` qui lève,
      le `play` ne propage aucune exception et le désastre reste actif. Chemin LECTURE au
      branchement : non testable après chargement de page — il faudrait empoisonner le
      stockage avant l'exécution du script.
- [ ] 6.11 **Les réglages tiennent à l'écoute** (question ouverte 1). Après 6.1, dire si
      l'âge virtuel par écoute et la demi-vie donnent le geste voulu, ou les corriger et
      refaire 6.1. **Ne pas cocher cette case sans avoir écouté** — c'est la seule
      vérification que le calcul ne remplace pas. **Décochée : rien n'a été écouté.**
      Un relevé utile pour celui qui le fera : avec les valeurs actuelles, un morceau récent
      atteint 0,552 vers dix écoutes et **sature vers dix-neuf**. Si ce plafond arrive trop
      vite à l'oreille, c'est `ageVirtuelParEcouteAns` qu'il faut baisser.
- [x] 6.12 **La sortie est trouvable.** Ouvrir le site en visiteur, sans lire le dépôt.
      *Attendu* : le pied de page dit qu'une sortie existe et ce qu'elle retire. *Vérifié* : `curl` sur la page servie trouve le bloc « Désastres » et `?sans-desastre` dans le pied de page.
- [x] 6.13 **La sortie suspend la mémoire.** Écouter trois fois un morceau avec
      `?sans-desastre`. *Attendu* : aucune altération, et l'usure enregistrée n'a pas bougé.
 *Vérifié* : avec `?sans-desastre`, le désastre n'est pas branché et trois `play` laissent le stockage strictement inchangé.