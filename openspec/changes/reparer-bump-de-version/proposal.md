## Why

`release-please-config.json` déclare que la version doit être écrite dans `src/VERSION` :

```json
"extra-files": [ { "type": "generic", "path": "src/VERSION" } ]
```

**Cela n'a jamais rien produit.** L'updater `generic` de release-please ne remplace que les
lignes portant une annotation `x-release-please-version` ; `src/VERSION` contient `1.9.0`
et rien d'autre. Il n'y a donc rien à remplacer, et l'échec est silencieux — ni
avertissement, ni journal, ni CI rouge.

L'état constaté le 2 août 2026 :

| | |
|---|---|
| `.release-please-manifest.json` | `1.10.0` |
| `src/VERSION` | `1.9.0` |
| Dernière écriture de `src/VERSION` | `ee93253`, **à la main**, le 23 janvier |

Le bump manuel était le contournement. Il a été fait une fois, puis oublié.

### Ce que le fichier porte, et qui s'en trouve défait

`src/VERSION` a exactement un consommateur : `VersionFilter`, qui le lit à chaque requête
et pose `app_version`. Cette valeur alimente le `?v=` des ressources statiques du gabarit —
favicon, icônes, manifeste, feuilles de style, scripts.

Le commit qui a introduit ce dispositif s'intitule **« feat: invalidation du cache des
assets statiques »**. Depuis le 23 janvier, `?v=` vaut invariablement `1.9.0` : toute
ressource modifiée depuis parvient aux visiteurs de retour derrière une adresse inchangée,
et leur navigateur sert la version qu'il détient déjà. **La fonctionnalité est annulée par
le fichier qui devait la porter.**

S'y ajoute une conséquence de méthode, éprouvée cette nuit : `?v=` est la seule marque de
version que le site publie. Figée, elle prive de tout moyen de savoir ce qui est en ligne —
et toutes les vérifications de cette séance ont buté là-dessus, faute de pouvoir distinguer
« pas encore déployé » de « déployé et faux ».

## What Changes

- `src/VERSION` reçoit l'annotation que l'updater `generic` attend, afin que release-please
  l'écrive à chaque publication.
- `VersionFilter` cesse de prendre le fichier entier pour une version : il en extrait le
  premier jeton. C'est la contrepartie nécessaire de l'annotation, qui ajoute du texte à la
  ligne.
- `src/VERSION` est aligné sur la version réellement publiée, `1.10.0`, que le manifeste
  porte déjà.

### Approche

L'annotation doit cohabiter avec un lecteur qui faisait `trim(file_get_contents())` — soit
la totalité du fichier. Deux voies :

- **déplacer l'annotation sur une autre ligne** : l'updater `generic` ne remplace que sur
  les lignes annotées, la version resterait donc intouchée. Sans effet ;
- **annoter la ligne de version et rendre le lecteur tolérant.** C'est la voie retenue.

`VersionFilter` extraira le premier jeton non blanc. Un fichier réduit à `1.10.0` continue
donc de fonctionner, ce qui garde le changement réversible et ne casse rien si l'annotation
disparaissait.

La contrainte du socle qui pèse ici : `src/VERSION` est lu **à chaque requête** par un
filtre, sans mise en cache propre. Toute complication de la lecture se paie sur chaque page.
Une expression régulière sur une chaîne de six caractères est acceptable ; parser un format
ne le serait pas.

### Ce que ce changement ne peut pas prouver seul

Que le déploiement fonctionne. Il rend la version observable ; c'est la publication
suivante qui dira si Plesk la met en ligne. Une tâche le trace, et c'est le premier
instrument de mesure que ce dépôt se donne.

## Hors périmètre

- `.github/workflows/ci.yml`, qui manipule bien une variable `VERSION` mais la tire du tag
  git, jamais du fichier. Vérifié : `src/VERSION` n'a qu'un consommateur.
- La politique de cache des ressources statiques elle-même — durées, en-têtes, empreintes
  de contenu. Ce changement rétablit le dispositif existant, il ne le repense pas.
- Le cache de vues de Symfony, traité par `preciser-aleatoire-des-desastres`.
- Les neuf autres artefacts de ce dépôt qui décrivent sans contraindre. Celui-ci en traite
  un dixième ; il ne préjuge pas des autres.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur la chaîne de publication et sur l'invalidation d'un cache
de ressources, jamais sur le comportement observable décrit par le corpus — aucune route,
aucun format, aucune métadonnée. D'où `skip_specs: true`.

## Impact

- `src/VERSION` : annoté, et porté à `1.10.0`.
- `src/apps/frontend/lib/VersionFilter.class.php` : lecture du premier jeton.
- **Contrat public inchangé.** Les adresses des ressources statiques changeront de suffixe
  à la prochaine publication, ce qui est précisément l'effet recherché : les visiteurs de
  retour recevront enfin les ressources modifiées.
- Le dépôt gagne une marque de version qui avance, donc un moyen de savoir ce qui tourne.
