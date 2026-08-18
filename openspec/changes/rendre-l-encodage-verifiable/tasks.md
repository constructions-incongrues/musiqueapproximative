# Tâches

## 1. La vérification

- [ ] 1.1 Exposer une route qui interroge les variables de session de la connexion et rend
  un verdict **analysable par une machine** — sans quoi le rendez-vous nocturne ne peut pas
  l'interroger, et une vérification que personne ne lance ne vérifie rien.
- [ ] 1.2 **Lecture seule.** `SHOW VARIABLES`, rien d'autre. C'est ce qui permet à cette
  route d'être publique sans devenir une surface.
- [ ] 1.3 Porter **le verdict ET la valeur constatée**. Un verdict seul se lit à 8 h et
  n'apprend rien : il faut savoir si la connexion est retombée en `latin1`, en `utf8` — trois
  octets, rejette les emoji — ou si la base est injoignable.
- [ ] 1.3bis **La réponse dit ce qu'elle ne sait pas.** Le verdict porte sur la connexion,
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
- [ ] 1.4 **Trois états, pas deux** : conforme, non conforme, et « je n'ai pas pu savoir ».
  Une base injoignable n'est pas un encodage fautif ; les confondre produit le bruit qui fait
  désactiver une alerte.
- [ ] 1.5 Vérifier que la route ne casse pas quand la base est absente : elle doit répondre,
  pas tomber en 500. C'est précisément l'état dans lequel le site s'est trouvé le 2026-08-18.

## 2. L'interroger automatiquement

- [ ] 2.1 Ajouter le contrôle à `contrat-production.yml`, qui tourne déjà les jours ouvrés à
  8 h et sait distinguer une production injoignable d'un contrat faux.
- [ ] 2.2 **Ne pas créer un second rendez-vous.** Un contrôle de même horaire et de même
  nature ne rend pas ce workflow illisible ; un second fichier serait un second endroit à
  surveiller.
- [ ] 2.3 Reprendre la distinction déjà en place : réseau, 5xx et délai dépassé sont
  réessayés puis signalés **sans échouer**. Seul un encodage présent et faux fait échouer.

## 3. Le dire où on ira le chercher

- [ ] 3.1 `docs/modules/ROOT/pages/fichiers-de-configuration.adoc` décrit déjà le piège de
  `make configure` sur le serveur. Y ajouter comment constater l'encodage et quoi faire quand
  la réponse est mauvaise.
- [ ] 3.2 Inscrire la page à la navigation si elle ne l'est pas — le contrôle de `ci.yml`
  refusera toute page orpheline de toute façon.

## 4. Vérification

- [ ] 4.1 La route répond, et son verdict est conforme en local — la base de développement
  est en `utf8mb4` depuis le 2026-08-18.
- [ ] 4.2 **Vérifier que le contrôle mord** : forcer un encodage fautif dans la configuration
  de test, constater que la route le signale et nomme la valeur constatée. Restaurer.
- [ ] 4.3 Vérifier le cas « base injoignable » : la route répond et distingue cet état.
- [ ] 4.4 Vérifier que rien n'est écrit — le compte de morceaux ne bouge pas, aucune table
  n'est modifiée.
- [ ] 4.5 Le contrôle du workflow, répété en local contre la production, passe.
- [ ] 4.6 `test:all` vert.

### Vérification manuelle — après la mise en ligne

- [ ] 4.7 Interroger la route en production. Attendu : **conforme, `utf8mb4`**. C'est la
  première fois que ce fait sera constatable en ligne.
- [ ] 4.8 Lancer le rendez-vous nocturne à la main et vérifier qu'il passe au vert.

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
