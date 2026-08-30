## 1. Étendre la mesure

- [x] 1.1 Porter la matrice de `.github/workflows/tests.yml` à
      `php: ["7.4", "8.1", "8.2", "8.3"]` (D1). Ne pas toucher au pas d'installation : sa
      branche `else` couvre déjà toute version autre que 7.4.
- [x] 1.2 Mettre à jour le commentaire d'en-tête de la matrice, qui annonce encore
      « 7.4 est la version de production, 8.1 la version visée ». La version visée n'est
      plus servable ; dire ce que chaque branche mesure et pourquoi 8.1 reste comme témoin.
- [x] 1.3 Lancer la matrice et **relever, version par version**, le nombre d'échecs, les
      fichiers et les lignes nommés. C'est le livrable principal de ce change.

## 2. La tâche de maintenance

- [x] 2.1 Écrire sous `src/lib/task/` une tâche qui enveloppe `doctrine:insert-sql` : elle
      exécute le travail, ne traite que la `PDOException` « There is no active transaction »,
      et vérifie la présence effective des tables avant de rendre 0 (D2, R3).
- [x] 2.2 Vérifier qu'elle rend **1** quand le schéma est réellement incomplet — le garde
      doit être éprouvé par l'échec avant d'être déclaré bon, comme l'a été la matrice.
- [x] 2.3 Retirer le `|| echo ...` de `tests.yml` et le garde qui vérifie les tables juste
      après : ils sont désormais dans la tâche. Ne PAS retirer l'assertion si la tâche ne
      la porte pas — elle est ce qui empêche un schéma incomplet de passer.
- [x] 2.4 Vérifier que `make test-init` rend 0 sous PHP 8, ce qu'il ne fait pas aujourd'hui.

## 3. Le verdict

- [x] 3.1 Ajouter en tête de
      `docs/modules/ROOT/pages/developpement/compatibilite-php-8.adoc` l'inventaire de
      l'hébergement : versions proposées, version servie, **date du relevé** (D4).
- [x] 3.2 Écrire que **8.1 est hors d'atteinte** : ni installée ni installable sur ce
      panel. La page présente aujourd'hui 8.1 comme la prochaine étape ; c'est faux.
- [x] 3.3 Verser le résultat de 1.3 : ce que 8.2 et 8.3 donnent, avec la date et les
      versions exactes des interpréteurs employés.
- [x] 3.4 Nommer les deux écarts que la spécification exige : versions mesurées non
      servables, versions servables non mesurées.

## 4. La déclaration, conditionnelle

> **La mesure est VERTE : ce groupe s'applique.** 8.2 et 8.3 passent les 696 tests sans une
> seule dépréciation. Consigne d'origine conservée ci-dessous pour mémoire.
>
> Ne rien faire de ce groupe si la mesure est rouge. Écrire alors, dans le verdict, ce qui
> a échoué et pourquoi la contrainte reste en l'état. Un groupe non fait n'est pas un
> oubli : c'est le résultat.

- [x] 4.1 Retirer `--ignore-platform-req=php` pour les versions rendues vertes — **d'abord**,
      avant 4.2 (D3).
- [x] 4.2 Élargir `"php"` dans `src/composer.json` selon la table de D3, à ces versions
      seulement.
- [x] 4.3 Vérifier que `composer install` réussit **sans drapeau** sur chaque version
      déclarée. C'est ce qui prouve que la déclaration n'est plus une intention.

## 5. Cohérence du dossier

- [x] 5.1 Corriger l'objectif 2 du T4 dans `.nanopm/wiki/docs/objectives.md`, qui nomme
      PHP 8.1 comme cible. Dire ce que ce relevé a établi plutôt que réécrire l'histoire.
- [x] 5.2 Lancer `openspec validate mesurer-la-compatibilite-php-8-2-et-8-3 --type change --strict`.

## 6. Vérification manuelle

> **6.2 et 6.3 sont sans objet** : elles ne se déclenchent que si une branche est rouge, et
> aucune ne l'est. Décochées plutôt que cochées — une case cochée dit « vérifié », pas
> « la condition ne s'est pas présentée ».

Rien ici ne touche la production, et rien ne s'écoute : ces vérifications se lisent dans
des sorties de commande.

- [x] 6.1 **La matrice rend quatre verdicts distincts.** Ouvrir l'exécution de CI.
      *Attendu* : quatre branches, aucune escamotée par `fail-fast`, chacune avec son
      statut propre.
- [ ] 6.2 *(sans objet — aucune branche n'est rouge)* **Un échec nomme un fichier et une ligne.** Si une branche est rouge, lire son
      journal. *Attendu* : le fichier et la ligne, pas « PHP 8 échoue ».
- [ ] 6.3 *(sans objet — 8.2 ne casse pas)* **Le témoin 8.1 sert.** Si 8.2 casse, vérifier ce que 8.1 rend sur le même test.
      *Attendu* : pouvoir dire si le défaut vient de 8.2 ou de PHP 8 en général.
- [x] 6.4 **La tâche enveloppante rend 0 sur un schéma complet et 1 sur un schéma
      incomplet.** Les deux essais, pas seulement le premier.
- [x] 6.5 **L'inventaire de l'hébergement est reproductible.** Relancer
      `ls -d /opt/plesk/php/*/` et `apt-cache search ^plesk-php` sur le panel. *Attendu* :
      le relevé de la page correspond, ou la page est mise à jour avec la nouvelle date.
