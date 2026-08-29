## Context

Deux mécanismes distincts servent « retrouver », et ils n'ont rien en commun.

**L'admin** passe par le générateur de Symfony 1. `PostFormFilter` est déjà généré et porte
des widgets pour `body`, `track_title`, `track_author`, `publish_on`, `is_online` et
`contributor_id`. Seul `filter: class: false` dans `generator.yml` l'empêche de servir. Le
travail est donc de déclarer, pas de construire.

**Le site** passe par le comportement `Searchable` de Doctrine 1, qui entretient une table
`post_index` alimentée à l'écriture d'un `Post`. `PostTable::search()` appelle
`parent::search()`, qui rend des lignes d'index, puis **boucle** en appelant
`getOnlinePostById()` sur chacune.

Cette boucle est le point dur. Elle viole une exigence déjà écrite — « servir une liste
coûte un nombre de requêtes constant » — et ce change l'aggrave mécaniquement : élargir
l'index au message multiplie les résultats, donc les requêtes.

## Goals / Non-Goals

**Goals**

- Qu'un contributeur restreigne sa liste d'admin sur ce qui sert à reconnaître un morceau.
- Que la recherche publique trouve un morceau par ce qui a été écrit sous lui.
- Que le coût d'une recherche cesse de suivre son nombre de résultats.

**Non-Goals**

- Indexer le contributeur, ou tout autre champ que le message.
- Changer la pagination, publique ou d'admin.
- Toucher au formulaire de publication.

## Decisions

### D1 — La reconstruction de l'index est une tâche versionnée, lancée à la main

Ajouter `body` à `actAs: Searchable` ne réécrit **rien** : `post_index` n'est alimenté qu'à
l'écriture d'un `Post`. Les 8 100 morceaux déjà publiés resteraient indexés sans leur
message, et la recherche ne trouverait le message que des morceaux postés **après** la
livraison. Un tel demi-état est pire que l'état actuel : il donne des résultats qui
dépendent de la date de publication sans que rien ne le dise.

Retenu : une tâche du projet sous `src/lib/task/`, qui réindexe par lots et rend compte de
son avancement, plus une procédure écrite dans `docs/`.

*Pourquoi à la main.* Le déploiement n'exécute aucune migration — Plesk tire `main`, un
point c'est tout. C'est exactement la contrainte de la conversion utf8mb4, et la procédure
suit le même modèle : un document qui dit quoi lancer, dans quel ordre, et comment vérifier
que c'est fait.

*Pourquoi par lots.* Réindexer 8 100 morceaux en une transaction sur la base de production
n'est pas un geste qu'on veut poser sans pouvoir l'interrompre.

**L'ordre importe, et il est l'inverse de celui d'utf8mb4** : ici le code part d'abord, la
réindexation suit. Un index reconstruit avant que `body` soit déclaré indexable ne
contiendrait pas les messages — le travail serait à refaire.

### D2 — `search()` hydrate en une requête, et conserve l'ordre de pertinence

```
ids = parent::search(q)              -> lignes d'index, par pertinence
morceaux = buildOnlinePostsQuery()->andWhereIn('p.id', ids)->execute()
puis reordonner en PHP selon la position de chaque id dans `ids`
```

*Pourquoi réordonner en PHP.* `whereIn` ne préserve aucun ordre, et le SQL qui l'imposerait
— un `FIELD(id, …)` — est une construction propre à MySQL qu'on ne veut pas dans une couche
modèle. Le tri en mémoire porte sur des résultats de recherche, dont le nombre est déjà
borné par les termes.

*Ce qui est conservé.* `buildOnlinePostsQuery()` porte déjà la jointure `UserProfile` que la
story 34 a posée. La recherche en bénéficie sans rien ajouter : c'est la même requête que
celle des listes, donc le même coût constant.

*Cas limite à traiter.* Zéro résultat : `whereIn` sur un tableau vide produit un SQL invalide
en Doctrine 1. Rendre le tableau vide sans requête.

### D3 — Les filtres déclarés sont ceux qui servent à reconnaître un morceau

`track_title`, `track_author`, `body`, `publish_on`, `is_online`.

*Pourquoi pas `contributor_id`.* Pour un contributeur ordinaire, la liste est déjà restreinte
à ses propres morceaux par `buildQuery()` : ce filtre ne pourrait matcher que lui-même. Il ne
servirait qu'aux détenteurs de `EditOthersPosts`, et afficherait à tous les autres une
commande sans effet. Une commande qui ne fait rien apprend au lecteur à ignorer les commandes.

*Pourquoi pas `track_filename` ni `buy_url`.* Rien n'indique que quiconque les cherche, et
chaque champ déclaré est une colonne de plus dans un formulaire déjà dense.

### D4 — Ce que le filtre d'admin ne doit pas devenir

`buildQuery()` applique sa restriction au contributeur **après** `parent::buildQuery()`, qui
porte les filtres. L'ordre est le bon : les filtres restreignent à l'intérieur du périmètre,
ils ne l'élargissent pas. Cette propriété SHALL être vérifiée par un test — c'est le seul
endroit de ce change où une erreur exposerait les données d'autrui.

## Risks / Trade-offs

**R1 — `post_index` grossit beaucoup.** `body` est un `mediumtext` ; les titres et artistes
sont des chaînes courtes. Indexer 8 100 messages produit un nombre de lignes d'index sans
commune mesure avec l'existant.
→ *Mitigation* : mesurer la taille de `post_index` avant et après sur une copie, et l'écrire
dans la procédure. Si le rapport est déraisonnable, c'est un fait à porter à l'auteur avant
de lancer en production, pas après.

**R2 — La recherche remonte plus de bruit.** Un message est de la prose : les mots courants
y sont fréquents. Une recherche sur « musique » pourrait remonter des centaines de morceaux
là où elle en remontait trois.
→ *Mitigation* : aucune dans ce change, et c'est assumé — c'est le prix de trouver par ce
qu'on a écrit. À réévaluer sur l'usage réel, pas par anticipation.

**R3 — L'index se reconstruit pendant que le site publie.** Un morceau posté pendant la
réindexation pourrait être manqué.
→ *Mitigation* : la tâche est rejouable et idempotente ; la relancer après coup ne coûte que
du temps. La procédure le dit.

**R4 — Le filtre d'admin élargit le périmètre visible.** Le risque le plus grave du change,
même s'il est improbable vu l'ordre du code.
→ *Mitigation* : D4, avec un test qui pose la question directement — un contributeur qui
filtre voit-il un morceau d'autrui ?

## Migration Plan

1. Livrer le code : `schema.yml`, `doctrine:build-model`, `generator.yml`, `search()`, la
   tâche, les tests.
2. **Puis seulement**, en production : lancer la reconstruction d'index à la main.
3. Vérifier sur le site qu'une recherche portant sur un mot présent dans un message ancien
   remonte le morceau.

Retour arrière : retirer `body` de `actAs: Searchable` et rejouer la reconstruction. L'index
redevient ce qu'il était ; aucune donnée de morceau n'est touchée à aucun moment — `post_index`
est dérivé, il se reconstruit.

## Open Questions

1. **La taille de `post_index` après réindexation est-elle acceptable ?** À mesurer sur copie
   (R1) avant de lancer en production. C'est la seule question qui peut arrêter ce change.
2. **Faut-il une commande de réindexation d'un seul morceau ?** Utile après une correction de
   message. Pas nécessaire ici : `Searchable` réindexe à l'écriture.
