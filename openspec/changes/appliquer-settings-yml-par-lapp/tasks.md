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

> **La prémisse de ce changement est fausse.** Constat établi après vérification des
> réglages du dépôt : il n'existait plus **ni ruleset, ni règle de protection classique**
> sur `main`. La branche était sans aucune protection.
>
> `settings.yml` déclare pourtant une section `branches` complète, l'app est installée, et
> plusieurs push sur la branche par défaut ont eu lieu depuis. Aucune règle n'a été créée.
> **L'app n'applique donc pas le fichier.** Deux lectures, non départagées : elle échoue
> silencieusement — permissions, ou section refusée par l'API — ou bien elle a supprimé la
> protection existante sans la remplacer.
>
> Dans les deux cas, faire de ce fichier la source de vérité de la protection ne marche
> pas. Il reste légitime pour les métadonnées et les libellés.
>
> Une protection a été restaurée à la main. La prochaine fusion sur `main` dira si l'app
> la supprime à nouveau : c'est le test qui désigne le coupable.

- [x] 3.1 Installer l'app GitHub Settings — fait. La protection de `main` a par ailleurs été corrigée à la main, indépendamment de l'app.
- [x] 3.2 Établir lequel des deux systèmes gouverne `main`
      — **aucun des deux.** Ni ruleset, ni règle classique. La branche était ouverte.
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
- [x] 3.2nonies Retirer la section `branches` de `.github/settings.yml` — fait. Tant
      qu'elle y figurait, chaque fusion sur `main` effaçait la protection. Un commentaire
      la remplace, qui dit pourquoi et met en garde contre sa réintroduction.
- [ ] 3.2decies Restaurer la protection de `main` à la main, une dernière fois. Elle ne sera plus effacée, la section fautive ayant disparu du fichier
- [x] 3.3 Vérifier que les réglages du dépôt et les libellés n'ont pas été altérés
      — jamais fait, et désormais sans urgence : l'app est désinstallée, plus rien
      n'applique le fichier. Un écart éventuel resterait figé plutôt que réappliqué.
      L'audit garde un intérêt documentaire, il n'en a plus de préventif.
- [ ] 3.4 Ouvrir une pull request de contrôle et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le test qui clôt `reparer-fusion-automatique`
      — première tentative sur la PR #98 : **non concluante**. Les dix checks étaient
      verts, les quatre contextes requis compris, et la pull request est restée
      `blocked` plusieurs minutes avant d'être fusionnée sans qu'on puisse dire si
      l'automatisme a joué. Reste inexpliqué : sans aucune protection, GitHub aurait dû
      rapporter `clean`. Une règle au niveau de l'organisation reste à écarter.
- [x] 3.5 Décider du sort des sections `repository` et `labels`
      — **tranché par les faits : l'app est désinstallée.** Plus rien n'applique le
      fichier, quelle que soit sa section. `settings.yml` redevient ce qu'il était au
      départ — une description que rien ne fait respecter — et rejoint la catégorie des
      artefacts décoratifs de ce dépôt. Son sort propre reste à décider : le supprimer,
      ou le garder avec un en-tête sans ambiguïté.
