## Why

Trois des quatorze chemins d'import de `src/apps/frontend/config/desastres.yml` ne
correspondent à aucun fichier. **Quatre règles de désastre sur vingt n'ont donc jamais été
chargées**, depuis le commit `b3a7587` du 2 janvier 2026 qui a éclaté la configuration
monolithique en modules. Les chemins ont été écrits en même temps que les fichiers et n'ont
jamais concordé.

L'écart est d'une lettre à chaque fois :

| Import déclaré | Fichier présent | État |
|---|---|---|
| `desastres/regles/redirect.yml` | `regles/redirects.yml` | jamais chargé |
| `desastres/regles/slitouine.yml` | `regles/splitouine.yml` | jamais chargé |
| `desastres/recettes/slitouine.yml` | `recettes/splitouine.yml` | jamais chargé |

L'échec est **silencieux**. `sfDesastreManager::processImports()` journalise un
`error_log` et poursuit : la page est servie normalement, et le comportement observable
d'un import cassé est identique à celui d'un fichier vide.

Ce changement est daté. L'une des règles inertes est un œuf de Pâques de Noël —
redirection vers *Quickos chante Noël* les 24 et 25 décembre. Écrite le 2 janvier 2026,
huit jours après le Noël précédent, elle n'a pas encore eu une seule occasion de se
déclencher : la première est **décembre 2026**. Corriger avant est la différence entre un
bug attrapé et un bug constaté.

## What Changes

- Les trois chemins d'import fautifs sont corrigés, ce qui **ressuscite quatre règles** :
  `spooky` (redirection sur *Spooky Mix*), `quickos` en décembre puis les 24 et 25, et
  `catani` (désastre *splitouine*).
- Un import qui ne résout pas **cesse d'échouer en silence**. C'est la seule modification
  de comportement du plugin, et elle est ce qui empêche la panne de se reproduire : sans
  elle, la prochaine faute de frappe mettra à son tour sept mois à se voir.
- Le doublon de la règle `postillons_mort`, déclarée à l'identique dans `misc.yml` et
  `postillons.yml`, est tranché — voir l'arbitrage ci-dessous.
- Aucune recette n'est modifiée, aucune règle n'est ajoutée ni retirée par ailleurs.

### L'arbitrage sur le doublon

La règle `postillons_mort` figure deux fois, mot pour mot :

```yaml
- probability: 0.7
  query: query.title ~ /.*(morte?s?|deaths?|dead).*/i
  recettes: [postillons_mort]
```

`processImports()` fusionne les règles par `array_merge` sur une liste à index numériques
— une concaténation, pas une déduplication. Les deux survivent, et `findRecettes()` tire
deux fois. La probabilité effective n'est pas `0,7` mais `1 − 0,3² = 0,91`.

Le reste de la chaîne est idempotent : `$allOptions` est clé par nom de désastre, et
`addJavascript()` / `addStylesheet()` de Symfony 1.x dédoublonnent par chemin. **Le seul
effet observable du doublon est donc la probabilité.** Aucun script ni feuille de style en
double.

La question à trancher n'est pas technique, elle est éditoriale : un morceau « mort »
doit-il glitcher sept fois sur dix, ou neuf ?

**Tranché par le mainteneur : sept sur dix.** La copie de `misc.yml` est supprimée, celle
de `postillons.yml` conservée — elle y est à sa place thématique — et la probabilité
effective revient à la valeur que la règle annonce. Les 0,91 n'étaient pas voulus ; ils
étaient le produit d'une redondance que rien ne signalait.

### Approche

Le plugin ne peut pas lever d'exception sur un import manquant sans risque : `desastres.yml`
est chargé à chaque affichage de page, et une configuration fautive en production
casserait le site entier plutôt que de le priver d'un effet. Sur un socle où le déploiement
se fait par `rsync` et où rien ne valide la configuration avant mise en ligne, c'est un
compromis défavorable.

L'exigence retenue est donc **la visibilité, pas la rupture** : le système continue de
servir la page, mais l'import non résolu doit être constatable autrement qu'en fouillant
les journaux PHP d'un serveur de production.

**Tranché par le mainteneur : journaux et console.** Deux canaux, complémentaires par ce
qu'ils atteignent :

- **les journaux serveur** — l'`error_log` existant est conservé tel quel. C'est la trace
  durable, celle qu'on relit après coup ;
- **la console du navigateur** — un avertissement est émis dans la page, nommant les
  imports déclarés qui ne se résolvent pas. C'est ce qui rend la panne constatable **sans
  accès au serveur**, et donc ce qui aurait fait voir celle-ci en janvier plutôt qu'en
  août.

Le second canal emprunte le chemin déjà tracé : `sfDesastreFilter` injecte du JavaScript
avant `</head>` dans les réponses `text/html`, et `injectDesastreOptions()` sait déjà
produire un `<script>` inline. Rien de nouveau n'est à bâtir.

Ce que ce choix implique et qu'il faut assumer : **l'avertissement est public**, visible
par tout visiteur qui ouvre sa console. C'est cohérent avec le reste — les déclencheurs le
seront aussi — et il ne divulgue qu'un chemin de fichier de configuration.

## Hors périmètre

- **La généralisation du paramètre `trigger`** à l'ensemble des règles, qui rendrait la
  capacité vérifiable de l'extérieur et aurait fait apparaître ce bug en trente secondes.
  C'est le changement `generaliser-trigger-desastres`, délibérément séparé : celui-ci est
  un correctif daté, celui-là un geste structurel.
- Le contenu des recettes, leurs scripts et leurs feuilles de style.
- Les deux recettes `quickos` et `spooky`, qui se chargent aujourd'hui sans qu'aucune
  règle ne les référence — `recettes/redirect.yml`, au singulier, existe bel et bien. Elles
  cessent d'être orphelines du seul fait que leurs règles reviennent ; il n'y a rien à
  faire de plus.
- Les probabilités des seize règles qui fonctionnent, `postillons_mort` exceptée.
- Le moteur de règles `sfDesastreRuleEngine`, qui n'est pas en cause.
- Les cinq autres artefacts du dépôt qui décrivent sans contraindre. Ce changement en
  traite un ; il ne préjuge pas des autres.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `desastres` — le corpus décrit aujourd'hui l'absence totale de configuration
  (« Requirement: Absence de configuration ») mais reste muet sur une configuration
  **présente et partiellement invalide**. C'est précisément le cas qui s'est produit, et
  celui qu'aucune exigence ne couvrait. Une exigence est ajoutée sur la résolution des
  imports.

## Impact

- `src/apps/frontend/config/desastres.yml` : trois chemins d'import.
- `src/apps/frontend/config/desastres/regles/misc.yml` : une règle en moins.
- `src/plugins/sfDesastrePlugin/lib/sfDesastreManager.class.php` : traitement d'un import
  non résolu.
- **Le contrat public HTML change**, de façon volontaire et limitée : quatre règles
  reviennent, donc certaines pages injecteront des ressources qu'elles n'injectaient pas.
  Aucune route, aucun format de sortie, aucune métadonnée n'est touché — les désastres
  n'agissent que sur les réponses `text/html`, via injection avant `</head>`.
- Les visiteurs de *Spooky Mix* seront redirigés au bout de trois secondes, ce qui était
  l'intention d'origine et ne l'a jamais été en fait.
