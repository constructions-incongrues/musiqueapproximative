> Pas de `design.md`. Six conditions alignées sur une constante qui existe déjà : aucune
> architecture, aucune dépendance, aucune migration.

## 1. Relever l'état de départ

- [x] 1.1 Relever combien de morceaux sont atteignables à tort — publiés et en ligne, mais
  sans identifiant d'URL — et lesquels.
- [x] 1.2 Vérifier que le scénario « Morceau aléatoire : le morceau tiré est publiable » est
  bien marqué non vérifié dans `catalogueEtNavigationTest.php`, et pour ce motif.

## 2. Aligner les six méthodes

- [x] 2.1 `getLastPost`, `getOnlinePostById`, `getNextPost`, `getPreviousPost`,
  `getRandomPost`, `getByMd5Sum` : employer `self::WHERE_ONLINE` au lieu de réécrire la
  condition.
- [x] 2.2 Ne pas toucher `getOnlinePostBySlug` : elle compare le slug à une valeur donnée,
  donc un slug vide ne peut pas correspondre. La modifier n'ajouterait rien et élargirait le
  diff.
- [x] 2.3 Vérifier que `getPreviousPost` conserve sa borne propre — elle cherche
  `publish_on < ?`, ce que `WHERE_ONLINE` ne remplace pas.
- [x] 2.4 Vérifier qu'aucune de ces requêtes ne dépendait de l'absence de la clause de slug
  pour fonctionner.

## 3. Le scénario passe de non vérifié à vérifié

- [x] 3.1 Remplacer le `skip()` de `catalogueEtNavigationTest.php` par l'assertion qu'il
  décrivait, et retirer le commentaire qui expliquait pourquoi elle était impossible.
- [x] 3.2 L'assertion doit être **déterministe** : le tirage est aléatoire et le cache le
  fige, donc constater une fois ne suffit pas. Exercer le tirage assez de fois, ou vérifier
  la requête plutôt que son résultat.

## 4. Vérification

- [x] 4.1 **Voir le test échouer** : rétablir temporairement l'une des conditions à la main,
  constater l'échec, corriger à nouveau. Un test qu'on n'a pas vu rouge ne prouve rien.
- [x] 4.2 Reprendre le relevé de 1.1 : le morceau sans slug n'est plus atteignable par
  aucune des six routes.
- [x] 4.3 Vérifier que les morceaux **publiables** le restent tous — la clause ajoutée ne
  doit rien retirer d'autre. Comparer les comptes avant et après.
- [x] 4.4 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après.
- [x] 4.5 Vérifier que la navigation du site fonctionne toujours : `/posts/next`,
  `/posts/prev`, `/posts/random`, et le lecteur dans un navigateur.
- [x] 4.6 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [x] 4.7 `openspec validate restreindre-les-tirages-aux-morceaux-publiables --type change --strict`.

### Mesures relevées le 2026-08-18

| | avant | après |
| --- | --- | --- |
| conditions écrites à la main | 7 | **1** — `getOnlinePostBySlug`, laissée exprès |
| morceaux publiables | 8 098 | **8 098** — rien d'autre n'est retiré |
| morceau 523 atteignable par `getOnlinePostById` | oui | **non** |
| morceau 523 atteignable par `getByMd5Sum` | oui | **non** |
| suite | 624 tests | **625** |

**Faillibilité vérifiée** : condition rétablie à la main dans `getRandomPost`, le test échoue
**trois fois sur trois** ; corrigée, il passe cinq fois sur cinq. Il discrimine.

Le test interroge le modèle sur **60 tirages** plutôt que la route une fois : le tirage est
aléatoire et le cache le fige, donc constater une seule réponse ne démontrerait rien.

### Le morceau qui a révélé le défaut

Un seul morceau était atteignable à tort sur 8 098 : **`????? — ??????`**, publié le
28 septembre 2009. Un titre cyrillique détruit par l'encodage `latin1`, dont `Sluggable` n'a
rien pu tirer.

Les deux défauts s'enchaînent — l'encodage détruit le titre, l'absence de titre empêche le
slug, l'absence de slug fait servir une page morte. La migration Unicode tarit la source ;
elle ne répare pas cette entrée, et ce change ne la répare pas non plus : il l'empêche
seulement d'être servie. Lui rendre un slug demanderait d'inventer un titre, ce qui est la
story 20.
