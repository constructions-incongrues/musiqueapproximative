# Tâches

## 1. La vérification

- [x] 1.1 Exposer une route qui interroge les variables de session de la connexion et rend
  un verdict **analysable par une machine** — sans quoi le rendez-vous nocturne ne peut pas
  l'interroger, et une vérification que personne ne lance ne vérifie rien.
- [x] 1.2 **Lecture seule.** `SHOW VARIABLES`, rien d'autre. C'est ce qui permet à cette
  route d'être publique sans devenir une surface.
- [x] 1.3 Porter **le verdict ET la valeur constatée**. Un verdict seul se lit à 8 h et
  n'apprend rien : il faut savoir si la connexion est retombée en `latin1`, en `utf8` — trois
  octets, rejette les emoji — ou si la base est injoignable.
- [x] 1.3bis **La réponse dit ce qu'elle ne sait pas.** Le verdict porte sur la connexion,
  et doit le dire. Trois champs l'accompagnent, sans quoi « conforme » vaut pour ce qu'il ne
  vaut pas :
  - la portée du verdict — la **connexion**, pas les titres ;
  - la date du **dernier caractère hors cp1252 effectivement stocké**. Aucun à ce jour, sur
    dix-huit ans. Tant que ce champ est vide, le vert signifie *configuration correcte,
    preuve jamais faite* ;
  - le **nombre de titres altérés encore en base** — 61 au 2026-08-19. Un « conforme »
    affiché à côté de ce nombre ne se lit plus de la même façon.

  Motif : le voyant était **déjà vert** avant que ce change soit écrit, et il cohabite avec
  61 titres mutilés qu'il ne voit pas. Voir la section « Ce que ce voyant vérifie, et ce
  qu'il laisse croire » de la proposition.
- [x] 1.4 **Trois états, pas deux** : conforme, non conforme, et « je n'ai pas pu savoir ».
  Une base injoignable n'est pas un encodage fautif ; les confondre produit le bruit qui fait
  désactiver une alerte.
- [x] 1.5 Vérifier que la route ne casse pas quand la base est absente : elle doit répondre,
  pas tomber en 500. C'est précisément l'état dans lequel le site s'est trouvé le 2026-08-18.

## 2. L'interroger automatiquement

- [x] 2.1 Ajouter le contrôle à `contrat-production.yml`, qui tourne déjà les jours ouvrés à
  8 h et sait distinguer une production injoignable d'un contrat faux.
- [x] 2.2 **Ne pas créer un second rendez-vous.** Un contrôle de même horaire et de même
  nature ne rend pas ce workflow illisible ; un second fichier serait un second endroit à
  surveiller.
- [x] 2.3 Reprendre la distinction déjà en place : réseau, 5xx et délai dépassé sont
  réessayés puis signalés **sans échouer**. Seul un encodage présent et faux fait échouer.

## 3. Le dire où on ira le chercher

- [x] 3.1 `docs/modules/ROOT/pages/fichiers-de-configuration.adoc` décrit déjà le piège de
  `make configure` sur le serveur. Y ajouter comment constater l'encodage et quoi faire quand
  la réponse est mauvaise.
- [x] 3.2 Inscrire la page à la navigation si elle ne l'est pas — le contrôle de `ci.yml`
  refusera toute page orpheline de toute façon.

## 4. Vérification

- [x] 4.1 `/encodage` répond `conforme`, `utf8mb4` constaté en local — la base de développement
  est en `utf8mb4` depuis le 2026-08-18.
- [x] 4.2 **Le contrôle mord.** `encoding: latin1` forcé → verdict `non-conforme`, valeur
  constatée `latin1` nommée dans la réponse. Configuration restaurée.
- [x] 4.3 Base injoignable → verdict `indetermine`, motif « base injoignable », **la route
  répond au lieu de tomber**.
  *Limite constatée* : le framework pose bien un `503` — vérifié par `sfBrowser` — mais le
  serveur de développement PHP rapporte `200`. La production tourne sous Apache ; non
  vérifiable avant la mise en ligne. Le workflow lit donc le **verdict** et non le seul
  statut, ce qui le rend correct dans les deux cas.
- [x] 4.4 Vérifier que rien n'est écrit — le compte de morceaux ne bouge pas, aucune table
  n'est modifiée.
- [x] 4.5 Répété en local contre la production : `404`, la route n'y est pas encore. Le
  contrôle traite ce cas comme « injoignable » et ne fait pas échouer — vérifiable pour de
  bon après la mise en ligne, tâche 4.8.
- [x] 4.6 `test:all` : **24 fichiers, 666 tests, verts.**

### Vérification manuelle — après la mise en ligne

- [x] 4.7 **Constaté en production** — et c'est la première fois que ce fait est observable
  en ligne : `verdict: conforme`, `encodage_constate: utf8mb4`,
  `caracteres_hors_cp1252_stockes: 0`, `dernier_stocke_hors_cp1252: null`,
  `titres_alteres_en_base: 60`.
- [x] 4.8 Rendez-vous nocturne lancé à la main : **succès**, et il imprime le verdict avec
  ses deux chiffres à côté, comme voulu —

  ```
  Encodage de connexion : conforme (utf8mb4).
    caracteres hors cp1252 reellement stockes : 0
    titres alteres encore en base            : 60
  ```

  Le « conforme » ne peut plus se lire seul.

## 5. Ce que ce change ne ferme pas

- [ ] 5.0 **Écrire aux 37 contributeurs.** Hors périmètre technique, et nommé ici parce que
  ce change publie un voyant « conforme » dans le même dépôt où la lettre n'est pas partie.
  Le voyant ne leur ment pas — il ne leur parle pas, et il occupe la place où la réparation
  aurait dû être visible.
- [ ] 5.1 **La production n'a toujours pas démontré qu'elle stocke un caractère de quatre
  octets.** Ce change rend le paramètre observable, il ne fait pas l'écriture. Aucun morceau
  du catalogue n'en porte, et le premier posté restera le vrai test.
- [ ] 5.2 Les autres valeurs de `databases.yml` restent invérifiables — hôte, utilisateur,
  mot de passe. Elles se signalent seules : sans elles, le site ne répond pas. L'encodage
  était le seul à pouvoir être faux sans que rien ne tombe.
