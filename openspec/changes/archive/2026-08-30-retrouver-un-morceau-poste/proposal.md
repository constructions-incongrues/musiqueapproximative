## Why

Le mélomane fêlé — le contributeur du collectif — sait poster. Il ne sait pas retrouver.

C'est la douleur la mieux chiffrée du produit, et elle n'a pas bougé depuis six mois :

- **Dans l'admin, ni filtre ni recherche.** `generator.yml` porte `filter: class: false`.
  `buildQuery()` restreint pourtant correctement la liste à ses propres morceaux et la trie
  par date décroissante — mais chez le contributeur le plus prolifique, cela fait
  **993 morceaux à parcourir vingt par vingt**, soit cinquante pages.
- **Sur le site, la recherche n'indexe que l'artiste et le titre.** `actAs: Searchable`
  déclare `fields: [track_author, track_title]`. Le message que le contributeur a écrit
  sous le morceau — le seul champ que le schéma rend obligatoire avec la date — n'est pas
  cherchable. On peut donc écrire pourquoi un morceau compte, et ne jamais le retrouver par
  ce qu'on en a dit.

Ces deux personnes sont co-primaires dans la carte des personas, et le contributeur est
celui dont dépend l'existence quotidienne du produit : sans lui, pas de morceau du jour.

**Ce change rouvre un anti-goal du trimestre, et c'est délibéré.** Les objectifs T4 avaient
placé « réparer retrouver » hors périmètre, avec cette règle : *« la réponse est non sans
conversation de repriorisation »*. La conversation a eu lieu le 2026-08-30 et l'auteur a
tranché pour. Le déclencheur écrit — « un doublon publié faute d'avoir retrouvé un
morceau » — n'a pas été attendu.

## What Changes

- **L'admin retrouve ses filtres.** `filter: class: false` disparaît. Le formulaire existe
  déjà : `PostFormFilter` est généré et porte des widgets pour `body`, `track_title`,
  `track_author`, `publish_on`, `is_online` et `contributor_id`. Il ne servait à personne.
- **La recherche du site indexe le message.** `body` rejoint `track_author` et
  `track_title` dans `actAs: Searchable`.
- **L'index existant doit être reconstruit.** Ajouter un champ à `Searchable` ne réécrit pas
  `post_index` : les 8 100 morceaux déjà publiés y resteraient indexés sans leur message. Une
  tâche versionnée fait la reconstruction, et une procédure écrite dit comment la lancer en
  production — le déploiement n'exécute aucune migration.
- **`PostTable::search()` cesse de coûter une requête par résultat.** Elle boucle
  aujourd'hui sur les résultats et appelle `getOnlinePostById()` pour chacun.

**Le contrat public est concerné, et de façon compatible.** Aucune route, aucun format,
aucun paramètre ne change. Ce qui change est l'**ensemble des résultats** que `?q=` renvoie :
il s'élargit. Un appelant qui cherchait « daft » recevra désormais aussi les morceaux dont
le message parle de Daft Punk. C'est l'objet même du change, mais il faut le dire.

### Pourquoi le N+1 de la recherche est dans ce périmètre

`catalogue-morceaux` porte déjà l'exigence *« Servir une liste coûte un nombre de requêtes
constant »*. `PostTable::search()` la viole : une requête par résultat. Ce n'est pas une
dette qu'on découvre, c'est une exigence écrite que le code contredit.

Elle entre ici parce que **ce change l'aggrave** : élargir l'index au message multiplie les
résultats, donc les requêtes. Corriger le défaut qu'on empire fait partie du travail ; le
laisser reviendrait à rendre la recherche plus lente au moment précis où on la rend plus
utile.

## Capabilities

### New Capabilities

- `contribution-au-catalogue` : ce que le site doit au contributeur qui alimente le
  catalogue — poster un morceau, et **le retrouver ensuite**. Cette surface n'est décrite
  par aucune spécification aujourd'hui, alors qu'elle porte la moitié du produit : le
  collectif publie quotidiennement depuis 2008. La capacité est ouverte ici et ce change
  n'en remplit qu'une partie — celle qui concerne retrouver.

### Modified Capabilities

- `catalogue-morceaux` : la recherche plein texte porte désormais sur le message du
  contributeur, et non plus seulement sur l'artiste et le titre. L'exigence de coût constant
  s'applique explicitement aux résultats de recherche, qui n'y satisfaisaient pas.

## Impact

- `src/apps/admin/modules/post/config/generator.yml` — la section `filter`.
- `src/config/doctrine/schema.yml` — `actAs: Searchable`, puis `doctrine:build-model`.
- `src/lib/model/doctrine/PostTable.class.php` — `search()`.
- `src/lib/task/` — la tâche de reconstruction d'index.
- `docs/` — la procédure de reconstruction, et la mention de ce que la recherche couvre.
- **La production** : un geste manuel du détenteur des accès, comme pour la conversion
  utf8mb4. Le déploiement tire `main` et rien d'autre.

## Hors périmètre

- **Chercher un contributeur depuis le champ de recherche.** `/posts?c=<username>` sert
  déjà la playlist d'un contributeur, et la liste du pied de page y renvoie : le
  contributeur est atteignable, il n'est simplement pas dans l'index plein texte. Il ne doit
  pas y entrer — un contributeur dont le nom d'utilisateur contient « daft » remonterait sur
  toute recherche de Daft Punk. Deux mécanismes qui répondent à deux questions différentes.
- **Paginer ou borner la liste de l'admin.** Elle reste à vingt par page ; c'est le filtre
  qui rend les cinquante pages inutiles, pas un changement de pagination. Borner les listes
  publiques est l'objectif 3 et a ses propres stories, gelées.
- **Indexer autre chose que le message** — le lien d'achat, le nom de fichier. Rien
  n'indique que quiconque les cherche.
- **Toucher au formulaire de publication.** Poster fonctionne ; ce change ne regarde que
  retrouver.
- **Corriger les autres N+1 du projet.** Seul celui de `search()` entre, et seulement parce
  que ce change l'aggrave.
