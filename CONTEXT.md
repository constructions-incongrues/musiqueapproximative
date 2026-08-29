# Context
# nanopm uses this to challenge your product thinking. Edit freely.
# Lines marked [auto] were pre-filled — verify they're accurate.

1. What are you building? (one sentence, no jargon)
   [auto from product.md] Un site de partage musical quotidien : une bande d'amis y publie
   un morceau à la fois — le fichier, l'artiste, le titre, et un texte qui dit pourquoi —
   et le site le sert à l'écoute, à la navigation, et en formats machine ouverts.

2. Who is the primary user? (job title, company size, situation)
   [auto from personas.md] Deux co-primaires, indissociables : le mélomane fêlé (membre du
   collectif, qui poste ses obsessions) et l'auditeur du jour (qui ouvre le site pour
   écouter et enchaîner, sans compte). Anti-persona : l'auditeur de plateforme.

3. What is the single most important thing users do with it today?
   [auto from product.md] Ouvrir le site, tomber sur le morceau du jour, l'écouter, et
   enchaîner (j / k / r). Côté collectif : poster un morceau avec son texte.

4. What did you ship in the last 30 days?
   [auto from git log] 145 commits. Le désastre sonore (bande-usée, premier désastre qui
   touche le signal audio, avec usure indexée sur l'âge du morceau), la mesure des tirages
   de désastres, la compatibilité PHP 8.1 vérifiée en CI, le cache des ressources statiques,
   la suite de tests affranchie d'un miroir de paquets, le champ avatar de l'admin réparé.

5. What are your top 1-2 goals for this quarter?
   Solder la dette technique : finir PHP 8, réparer l'Unicode, borner les listes — que le
   socle ne soit plus une menace.

6. What are your users doing RIGHT NOW when your product doesn't cover their need?
   [auto from personas.md] Le contributeur qui cherche un morceau qu'il a posté : il fait
   défiler l'admin vingt par vingt (jusqu'à 50 pages chez le plus prolifique), ou il
   renonce, ou il republie un doublon sans le savoir. Coût : ni filtre ni recherche
   (`generator.yml: filter: class: false`), et `Searchable` n'indexe ni le corps du texte
   ni le nom du contributeur. L'auditeur, lui, retourne aux plateformes de streaming, qui
   recommandent sans jamais dire pourquoi.

7. What have you explicitly decided NOT to build, and why?
   [auto from personas.md] Tout ce qui sert l'auditeur de plateforme : comptes visiteurs,
   catalogue à parcourir, favoris, playlists personnelles, recommandations. Le produit est
   une émission, pas une bibliothèque — `/` ouvre sur le morceau du jour, jamais sur
   l'index. Et les désastres sont incompatibles avec un service dont on attend qu'il
   fonctionne.

8. Who are your 3 most important users/customers right now?
   [ANSWER — non renseigné]
   Mesuré à leur place : 3 contributeurs signent 68 % des morceaux de 2026, et le premier
   à lui seul 38 %. Leurs identités n'ont pas été demandées.

9. What is the one metric that matters most to you right now?
   Aucune — et c'est voulu. Mesurer changerait le produit ; le site n'a pas à rendre de
   comptes à un chiffre. (État de fait : aucune métrique n'est collectée, le seul traceur
   du site est un `ga.js` mort — `layout.php:486`.)

10. What's the biggest thing you're uncertain or worried about?
    Que ça ne serve à personne. Que le site tourne, que le code s'améliore, et que plus
    personne n'écoute — sans aucun moyen de le savoir.

11. What development methodology does your team use?
    [auto from openspec/discovery.md] OpenSpec : un plan de release en stories, une story
    = un change, une seule à la fois. Conventional Commits, release-please, déploiement
    automatique à la poussée sur `main`.

12. How does this project ship?
    [auto] a) Solo + AI agents. Auteur unique, agents de code, worktrees. 439 commits en
    2026 contre 1 en 2019 — le rythme est celui d'une chaîne assistée.
