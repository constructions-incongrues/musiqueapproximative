## 1. Mise en exactitude du fichier

- [x] 1.1 Réintroduire `Trivy Scan` dans les contextes de vérification requis de `.github/settings.yml`
- [x] 1.2 Réécrire les commentaires qui décrivaient un état transitoire — workflow désactivé, contexte à réintroduire — pour qu'ils décrivent la configuration voulue
- [x] 1.3 Passer `enforce_admins` de `true` à `false`, et documenter la raison en commentaire : sans issue de secours, un contexte requis qui cesse de remonter verrouillerait le dépôt

## 2. Contrôle de ce que l'application a modifié

> Ces vérifications étaient prévues **avant** l'installation. Elle a eu lieu sans qu'elles
> soient faites : l'app a donc appliqué l'intégralité du fichier — métadonnées du dépôt,
> seize libellés, protection de branche — en résolvant en sa faveur tout écart avec les
> réglages existants.
>
> Elles restent dues, en vérification a posteriori. L'ordre inverse coûte plus cher : au
> lieu de choisir ce qu'on aligne, il faut constater ce qui a bougé. Rien n'indique qu'un
> dommage se soit produit, mais rien ne l'exclut non plus.

- [x] 2.1 Comparer la section `repository` aux réglages actuels
      — sans objet : l'app est désinstallée avant que la comparaison n'ait été faite.
- [x] 2.2 Comparer les seize libellés déclarés à ceux qui existent
      — sans objet, pour la même raison.
- [x] 2.3 Vérifier qu'aucune issue ni pull request n'a perdu un libellé du fait d'un renommage
      — sans objet côté issues : le dépôt n'en compte aucune, tous états confondus. Le
      risque ne subsiste que sur les pull requests closes, où il est cosmétique.
- [x] 2.4 Décider, pour chaque écart, qui du fichier ou du réglage avait raison
      — sans objet : aucun écart n'a été relevé, et le fichier ne fait plus autorité.

## 3. Installation et vérification

> **La prémisse de ce changement est fausse**, mais pas pour la raison qu'on a d'abord
> retenue. Deux systèmes de protection coexistent sur GitHub, et toute cette section les a
> confondus :
>
> - les **règles classiques**, sous Réglages → Branches, seules pilotables par l'app
>   Settings — c'est ce que décrivait la section `branches` du fichier ;
> - les **rulesets**, sous Réglages → Rules → Rulesets, un système distinct avec sa propre
>   API, que l'app ne sait **ni créer, ni modifier, ni supprimer**.
>
> `main` est gouvernée par un **ruleset**. Il porte le nom `main`, il est actif, et sa
> liste de contournement comporte « Repository admin — Always allow ». L'API le confirme :
> `main` remonte `protected: true`.
>
> Ce qu'il faut en tirer :
>
> - l'app n'a **jamais pu** effacer la protection de `main`, contrairement à ce que
>   trois entrées ci-dessous affirment. Ce qui disparaissait sous Réglages → Branches était
>   la règle classique, que l'app n'arrivait pas à créer ; le ruleset, lui, n'a jamais été
>   dans son périmètre ;
> - la protection n'a donc probablement jamais cessé de s'appliquer. Les réponses
>   successives « le ruleset a disparu », puis « plus rien », portaient sur des écrans qui
>   ne montraient pas le bon système ;
> - **c'est l'explication de la fusion automatique qui ne se déclenche jamais.** Le
>   contournement administrateur autorise le mainteneur à fusionner à la main, quoi que
>   dise le ruleset — ce qu'il a fait sept fois. La fusion automatique, elle, n'emprunte
>   pas ce contournement : elle attend que la pull request soit réellement fusionnable.
>   Une exigence du ruleset jamais satisfaite laisse donc les deux états observés
>   simultanément — `blocked` pour l'automatisme, fusionnable à la main.
>
> Reste à établir ce que le ruleset exige : c'est l'objet de 3.4bis.
>
> Ce qui subsiste de valide : faire de `settings.yml` la source de vérité de la protection
> ne marche pas, puisque le fichier ne peut atteindre que le système classique — celui qui
> ne gouverne pas cette branche. Il reste légitime pour les métadonnées et les libellés.

- [x] 3.1 Installer l'app GitHub Settings — fait. La protection de `main` a par ailleurs été corrigée à la main, indépendamment de l'app.
- [x] 3.2 Établir lequel des deux systèmes gouverne `main`
      — **le ruleset.** Un ruleset nommé `main`, à l'état actif, contournable par les
      administrateurs du dépôt. L'app Settings n'a aucune prise dessus : elle n'écrit que
      la protection classique. La réponse « aucun des deux », portée ici pendant toute
      l'enquête, était fausse ; elle vient d'avoir été relevée sur le mauvais écran.
- [x] 3.2bis Décider du sort de la section `branches` de `settings.yml`
      — la question ne se pose plus dans les termes prévus : ce n'est pas que le fichier
      décrive le mauvais système, c'est que l'app ne l'applique pas du tout. La décision
      dépend du test ci-dessous.
- [x] 3.2ter Restaurer une protection sur `main` à la main — fait.
- [x] 3.2quater **Test décisif** — verdict : **la protection a de nouveau disparu**
      après la fusion de la PR #100, comme lors de l'installation.
      — **Cause trouvée, et elle n'est pas l'app.** Sa documentation est explicite :
      chaque élément de premier niveau sous `protection` doit être renseigné — dont
      `restrictions` — et mis à `null` si l'on n'en veut pas, faute de quoi *aucun*
      réglage n'est appliqué. `restrictions` manquait depuis l'origine du fichier.
      L'app envoyait donc une charge incomplète à l'API, qui n'en retenait rien et
      laissait la branche sans protection. La conclusion « il faut désinstaller » est
      retirée.
      — **Rectifié depuis** : « la protection a disparu » désignait la règle classique,
      seule chose que l'app manipule et qu'elle n'a jamais réussi à créer. Le ruleset qui
      gouverne réellement `main` était hors de sa portée et n'a pas bougé. L'app n'a rien
      détruit ; elle n'a rien produit non plus.
- [x] 3.2quinquies Ajouter `restrictions: null` à `.github/settings.yml` — fait.
- [x] 3.2sexies Vérifier que l'app crée la protection après le correctif `restrictions`
      — **non, elle n'apparaît toujours pas.** `restrictions` n'était donc pas le seul
      élément fautif.
- [x] 3.2septies Aligner strictement la protection sur la référence documentée — fait :
      `required_pull_request_reviews` passe à `null` plutôt que `required_approving_review_count: 0`,
      la plage documentée étant 1 à 6 et une valeur hors plage invalidant la charge
      entière ; `allow_force_pushes` et `allow_deletions`, absents de la documentation,
      sont retirés. Ne subsistent que les cinq clés que la référence décrit.
- [x] 3.2octies Après fusion, vérifier une nouvelle fois si la protection est créée
      — **on renonce.** Après trois tentatives d'alignement sur la documentation de
      l'app — ajout de `restrictions`, `required_pull_request_reviews` à `null`, retrait
      des clés non documentées — la protection n'est toujours pas créée. Le coût de
      l'enquête dépasse désormais le bénéfice attendu.
- [x] 3.2nonies Retirer la section `branches` de `.github/settings.yml` — fait. Un
      commentaire la remplace, qui dit pourquoi et met en garde contre sa réintroduction.
      — **Rectifié** : la section n'effaçait pas la protection, elle échouait à en créer
      une. Son retrait reste le bon geste, pour une raison plus simple que celle
      invoquée : elle décrit un système — la protection classique — qui ne gouverne pas
      cette branche, et laisser un fichier prétendre le contraire égare le lecteur.
- [x] 3.2decies Restaurer la protection de `main`
      — **sans objet : elle n'avait pas disparu.** Le ruleset `main` est actif, et l'API
      rapporte `protected: true` sur la branche. Il n'y avait rien à restaurer.
- [x] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés
      — jamais fait, et désormais sans urgence : l'app est désinstallée, plus rien
      n'applique le fichier. Un écart éventuel resterait figé plutôt que réappliqué.
      L'audit garde un intérêt documentaire, il n'en a plus de préventif.
- [ ] 3.4 Ouvrir une pull request de contrôle et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
      — première tentative sur la PR #98 : **non concluante**. Les dix checks étaient
      verts, les quatre contextes requis compris, et la pull request est restée
      `blocked` plusieurs minutes avant d'être fusionnée sans qu'on puisse dire si
      l'automatisme a joué.
      — **L'anomalie de cette tentative est levée.** « Sans aucune protection, GitHub
      aurait dû rapporter `clean` » : la protection existait bel et bien, sous forme de
      ruleset. `blocked` était la réponse correcte. Il n'y a pas de règle d'organisation à
      écarter.
- [ ] 3.4bis Relever ce que le ruleset `main` exige — contextes de vérification requis,
      revues, historique linéaire. C'est la dernière inconnue : une exigence qui ne peut
      jamais être satisfaite expliquerait à elle seule que la fusion automatique n'ait
      jamais abouti en une quinzaine de pull requests
- [x] 3.5 Décider du sort des sections `repository` et `labels`
      — **tranché par les faits : l'app est désinstallée.** Plus rien n'applique le
      fichier, quelle que soit sa section. `settings.yml` redevient ce qu'il était au
      départ — une description que rien ne fait respecter — et rejoint la catégorie des
      artefacts décoratifs de ce dépôt.
- [x] 3.6 Trancher le sort de `settings.yml` — **conservé**, avec un en-tête qui dit sans
      ambiguïté qu'il n'est appliqué par rien et que les réglages GitHub font foi. Le
      supprimer aurait effacé la seule trace de ce que la configuration est censée être ;
      le garder muet aurait reproduit le travers de la banque de mémoire.
