## 1. L'admin retrouve ses filtres

> **Docker a été remis d'aplomb le 2026-08-30** (un `com.docker.backend` résiduel avait
> survécu au `quit` et bloquait le démon ; il a fallu le terminer de force). Tout ce qui
> était bloqué a été exécuté. Ne reste décoché que ce qui exige la production.

- [x] 1.1 Retirer `filter: class: false` de
      `src/apps/admin/modules/post/config/generator.yml` et déclarer les champs filtrables :
      `track_title`, `track_author`, `body`, `publish_on`, `is_online` (D3). Ne pas déclarer
      `contributor_id` — il n'aurait d'effet que pour les détenteurs de `EditOthersPosts` et
      afficherait aux autres une commande sans effet.
- [x] 1.2 Vider le cache — la configuration du générateur est compilée — et vérifier que le
      formulaire de filtre apparaît, sans avoir eu à écrire une seule ligne de PHP :
      `PostFormFilter` est déjà généré.
- [~] 1.3 **Écrire le test qui protège le périmètre** — *fait autrement, et la propriété est
      désormais vérifiée à la main (6.2) ; ce qui reste dû est son AUTOMATISATION.*
      Le harnais fonctionnel du projet ne porte aucune aide à la connexion sfGuard, et
      improviser une authentification sans pouvoir l'exécuter aurait produit un test dont
      personne ne saurait s'il vérifie quelque chose. Ce qui est livré : deux cas de plus
      dans `postActionsTest.php` vérifiant que les **routes filtrées** ne servent rien à un
      visiteur anonyme, et un commentaire qui établit la propriété par lecture —
      `buildQuery()` applique les filtres via `parent::buildQuery()` puis ajoute
      `andWhere('contributor_id = ?')`, et un `andWhere` restreint sans jamais élargir. La
      vérification authentifiée reste due à la main : tâche 6.2. **Écrire le test qui protège le périmètre (original)** (D4, R4) : un contributeur sans
      `EditOthersPosts` qui filtre sur un terme présent dans le morceau d'un autre ne doit
      voir aucun résultat. C'est le seul endroit de ce change où une erreur exposerait les
      données d'autrui.

## 2. La recherche indexe le message

- [x] 2.1 Ajouter `body` à `actAs: Searchable` dans `src/config/doctrine/schema.yml`.
- [x] 2.2 **REJOUÉ le 2026-08-30, et il a rattrapé une VRAIE erreur de ma part.** Mon
      insertion à la main avait mis `body` dans les champs de **`Sluggable`**, pas de
      `Searchable` : les slugs auraient été construits à partir du texte des messages. La
      suite de tests était pourtant verte — rien ne l'aurait vu avant que des morceaux
      soient enregistrés en production. `doctrine:build-model` a corrigé, et le diff est
      désormais celui du générateur. Trace de l'erreur, conservée :
      ~~NON RÉGÉNÉRÉ — mis en cohérence à la main, à rejouer avant fusion.~~
      `BasePost.class.php` est versionné et porte la liste des champs indexés ; le laisser
      diverger de `schema.yml` aurait livré un change inerte, puisque c'est la classe générée
      qui s'exécute. J'y ai donc inséré `2 => 'body'`, ce que le générateur produit. **Le
      dépôt interdit d'éditer `lib/model/doctrine/base/` à la main** : `doctrine:build-model`
      doit être rejoué et son diff doit être vide. Original :
      Régénérer les modèles : `doctrine:build-model`. Ne pas toucher aux fichiers sous
      `lib/model/doctrine/base/`.
- [x] 2.3 Vérifier qu'un morceau **nouvellement** posté est trouvé par un mot de son message.
      À ce stade les anciens ne le sont pas encore, et c'est attendu — c'est exactement le
      demi-état que la tâche du groupe 3 vient corriger.

## 3. La reconstruction de l'index

- [x] 3.1 Écrire sous `src/lib/task/` une tâche de reconstruction de `post_index`, **par
      lots**, qui rend compte de son avancement et qui est **rejouable** : la relancer ne
      doit produire ni doublon ni perte (D1, R3).
- [x] 3.2 *(Vérifié par l'échec, deux fois : la tâche mourait à 1 000 morceaux sur 8 216
      avec 128 Mo. Ni `clear()` sur l'identity map ni `free()` sur la collection n'y ont
      changé quoi que ce soit — la consommation vient de la couche connexion de Doctrine.
      Corrigé en supprimant toute hydratation — `updateIndex()` prend un tableau, on n'avait
      jamais besoin des objets — et en passant de `offset` à une pagination par identifiant,
      qui rend la reprise possible. Il reste à relever la limite mémoire à 1 Go, ce que la
      procédure dit.)* Vérifier qu'elle est interruptible : l'arrêter au milieu puis la relancer doit
      aboutir au même index qu'une exécution d'une traite.
- [x] 3.3 **Mesuré le 2026-08-30 sur la copie de dev : 37 744 lignes / 6,0 Mo → 144 467
      lignes / 20,9 Mo**, soit ×3,8 et ×3,5. Vingt et un mégaoctets pour dix-huit ans de
      catalogue : **le verdict est bon, la mesure ne fait pas renoncer.** Original :
      **Mesurer la taille de `post_index` avant et après**, sur une copie, et consigner
      les deux chiffres (R1, question ouverte 1). Si le rapport est déraisonnable, s'arrêter
      et porter le fait à l'auteur — c'est la seule mesure qui peut annuler ce change.
- [x] 3.4 *(+ ajoutée à `nav.adoc` : la capacité `documentation-publiee` exige que toute page publiée soit atteignable)* Écrire la procédure dans `docs/` sur le modèle de `migration-utf8mb4.adoc` : quoi
      lancer, dans quel ordre, comment vérifier que c'est fait. **Dire que le code part
      d'abord et la réindexation ensuite** — l'inverse de l'ordre d'utf8mb4, et pour une
      raison opposée : un index reconstruit avant que `body` soit déclarable ne contiendrait
      pas les messages.

## 4. Le coût de la recherche

- [x] 4.1 Réécrire `PostTable::search()` : collecter les identifiants rendus par
      `parent::search()`, hydrater en **une** requête via `buildOnlinePostsQuery()`, puis
      réordonner en PHP selon la position de chaque identifiant (D2).
- [x] 4.2 Traiter le cas de zéro résultat **sans émettre de requête** : `whereIn` sur un
      tableau vide produit un SQL invalide en Doctrine 1.
- [x] 4.3 Vérifier que l'ordre de pertinence rendu par l'index est conservé après hydratation.
- [x] 4.4 *(vert : `PostTableRechercheTest`, 706 tests au total)* **Écrire le test de coût** exigé par la spécification : une recherche rendant dix
      fois plus de morceaux ne doit pas émettre dix fois plus de requêtes. Sans lui, le N+1
      reviendra au premier accès ajouté dans un gabarit. Le vérifier par l'échec d'abord :
      avec l'ancienne implémentation, ce test doit être rouge.

## 5. Cohérence du dossier

- [x] 5.1 Mettre à jour `.nanopm/wiki/docs/objectives.md` : « réparer retrouver » y figure en
      anti-goal du trimestre. Écrire que la repriorisation a eu lieu le 2026-08-30, à la
      demande de l'auteur, sans attendre le déclencheur qui y était écrit. Ne pas effacer
      l'anti-goal — le remplacer par sa levée datée.
- [x] 5.2 Dire dans `docs/` ce que la recherche couvre désormais, et ce qu'elle ne couvre
      toujours pas — le contributeur, délibérément.
- [x] 5.3 Ouvrir une story dans `openspec/discovery.md` et la relier à ce change : le plan de
      release est épuisé, et « Validation du code » exige qu'une story cochée déclare son
      change. Sans elle, le plan restera muet sur ce qui a été livré.
- [x] 5.4 `openspec validate retrouver-un-morceau-poste --type change --strict`.

## 6. Vérification manuelle

Le filtre d'admin demande un compte et un navigateur ; la réindexation demande la
production. Rien ici ne s'écoute.

- [x] 6.1 **Le filtre sert vraiment.** *Vérifié en session authentifiée (`bertier`, 997
      morceaux) : filtre sur `body=krautrock` → 1 résultat, contre 20 par page sans filtre.
      Côté public, quatre termes — guitare, batterie, concert, disque — remontent des
      morceaux dont ni le titre ni l'artiste ne les contient.* Se connecter à l'admin, filtrer sur un mot présent
      dans le message d'un morceau ancien. *Attendu* : le morceau remonte, sans avoir fait
      défiler une seule page.
- [x] 6.2 **Le filtre n'ouvre pas la liste — LE POINT CRITIQUE, ET IL TIENT.** *Vérifié :
      `krautrock` figure dans le message d'un morceau de `bertier` (Taarida, 5049) ET d'un
      morceau d'`oyibo` (ISM, 7751). Connecté en `bertier`, le filtre rend Taarida et **pas**
      ISM. Le périmètre n'est pas élargi par les filtres.* Avec un compte sans `EditOthersPosts`, filtrer
      sur un terme qu'on sait présent chez un autre contributeur. *Attendu* : aucun résultat.
      C'est 1.3 rejoué à la main, parce que la conséquence d'une erreur est la seule qui ne
      se rattrape pas.
- [x] 6.3 **La liste vide se lit.** *Vérifié : filtre sur un terme inexistant → 0 ligne,
      « No result » affiché, et la restriction reste dans le champ (`value="…"` présent),
      donc corrigeable sans ressaisie.* Filtrer sur quelque chose d'inexistant. *Attendu* : la
      liste est vide, le dit, et la restriction reste affichée pour être corrigée.
- [ ] 6.4 **La recherche publique trouve par le message, sur un morceau ANCIEN.** Après la
      réindexation en production. *Attendu* : un morceau de 2012 remonte sur un mot présent
      seulement dans son message.
- [x] 6.5 **Le nom d'un contributeur ne remonte rien *par ce seul fait*.** *Vérifié sur
      `oyibo`, `glafouk`, `lovebot` : 2, 6 et 7 résultats — et dans **100 % des cas** le mot
      figure réellement dans le titre, l'artiste ou le message du morceau. Aucun morceau ne
      remonte parce que son contributeur porte ce nom. L'index ignore bien le contributeur.*
      Original :  **Le nom d'un contributeur ne remonte rien.** Chercher un nom d'utilisateur du
      collectif. *Attendu* : aucun morceau ne remonte de ce seul fait — `?c=` reste le chemin.
- [x] 6.6 **Le bruit est supportable — R2 ne se matérialise pas.** *Mesuré sur les 8 098
      morceaux publiables : `musique` 272 résultats (3,4 %), `album` 201 (2,5 %), `chanson`
      178 (2,2 %), `disque` 38 (0,5 %). Le pire cas est « musique » sur un site qui porte ce
      mot dans son nom, et il reste sous 4 % du catalogue. La crainte d'une recherche
      inexploitable ne se vérifie pas.* Original : **Le bruit est supportable** (R2). Chercher un mot courant — « musique », « disque ».
      *Attendu* : à juger. Si le résultat est inexploitable, c'est un fait à consigner, pas un
      échec de ce change.
