## 1. Correction

- [x] 1.1 Déplacer la déclaration `desastre` sous `cache` dans `src/apps/frontend/config/filters.yml`, pour que l'injection précède l'écriture de l'entrée de cache
- [x] 1.2 Consigner la raison en commentaire dans le fichier : l'ordre de déclaration est contre-intuitif, la chaîne remontant en sens inverse, et rien ne signalait le piège
- [x] 1.3 Ne pas ajouter de garde dans les scripts de désastre. Elle masquerait le défaut au lieu de le corriger, et laisserait les visiteurs télécharger des ressources inertes

## 2. Correction de l'exigence

- [x] 2.1 Étendre « Requirement: Application d'une recette » : l'enrichissement fait partie de la représentation mise en cache
- [x] 2.2 Compléter le scénario « Options transmises au désastre » — les options valent pour toute réponse portant les ressources, produite ou servie depuis le cache
- [x] 2.3 Ajouter un scénario sur les consultations successives d'une adresse enrichie, qui est le cas que la mesure a pris en défaut

## 3. Vérification manuelle

- [x] 3.1 Sur une instance locale en environnement `prod`, cache vidé, demander quatre fois une adresse portant un désastre et vérifier que `window.DesastreOptions` figure dans les quatre réponses
      — mesuré le 7 août 2026 sur trois morceaux : `1,1,1,1` pour chacun, contre `1,0,0,0`
      avant le déplacement.
- [x] 3.2 Vérifier que les réponses successives restent identiques à l'octet près : le tirage est mis en cache, son effet doit l'être aussi
      — trois demandes de `/post/mannequin-moves-baby-love` : corps identiques.
- [x] 3.3 Vérifier qu'aucun autre format ne régresse — `json`, `xspf`, `max`, le flux
      — `posts`, `posts?format=json` et `posts/feed` répondent `200` avec leur type propre.
      Le `500` observé sur `xspf` vient de l'arbre de travail utilisé pour la mesure, qui
      précède le correctif de `reparer-format-xspf` ; il n'a pas de rapport avec ce
      changement.
- [x] 3.4 **Après déploiement** : reprendre la mesure de production faite pour ce changement — deux adresses jamais servies, quatre demandes chacune — et vérifier que les options figurent partout. C'est le seul contrôle qui vaille : le défaut a été établi en production, il doit y être levé
      — **mesuré le 7 août 2026**, après la mise en ligne de `v1.10.2`. Sur les deux mêmes
      morceaux, adresses neuves : `1,1,1,1` chacun, contre `1,0,0,0` avant. Les quatre
      demandes mesurées sont toutes servies depuis le cache — l'entrée avait été créée par
      la demande de découverte — donc c'est exactement le cas qui était en défaut.
- [x] 3.5 Vérifier qu'un désastre s'applique visuellement à la deuxième consultation d'une page, dans un navigateur. La présence du bloc d'options ne prouve pas que le script s'exécute — c'est la même distinction qui a fait passer ce défaut inaperçu
      — **le script s'exécute**, vérifié dans un navigateur sur
      `/post/free-kitten-greener-pastures?z=900` en production, page servie depuis le cache.
      `window.DesastreOptions.mangelettres` porte sa configuration complète, et la console
      trace le déroulé : « SplitText initialized », « Loaded », « Analyzing 20 characters »,
      « Will remove N characters ». Aucune `TypeError`. Rechargée, la page se comporte de
      même.
      — **Ce qui n'a pas été constaté** : la disparition des lettres elle-même. `mangelettres`
      étale son effet sur la durée du morceau — 157 secondes ici — et le titre est encore
      entier au chargement. La tâche demandait « visuellement » ; ce qui est établi est que
      le script part et programme son travail, pas qu'on l'ait vu aboutir.
      — **Relevé à l'occasion, et qui nuance `preciser-aleatoire-des-desastres`** : deux
      chargements de la même adresse annoncent « Will remove 10 characters » puis « 6 ». Le
      tirage serveur est bien figé par le cache, mais la réalisation côté client tire à
      nouveau. « Deux visiteurs voient le même effet » est donc vrai de la recette, pas de
      son rendu.

## 4. Portée laissée ouverte

- [x] 4.1 Décider si les scripts de désastre doivent malgré tout se garder contre l'absence d'options. Ce changement dit que la garde ne remplace pas le correctif ; il ne dit pas qu'elle est inutile. Un désastre qui échoue devrait le signaler plutôt que mourir sur une `TypeError` silencieuse
      — **il n'y avait pas d'arbitrage à rendre : le dépôt avait déjà tranché.** Quatre
      recettes sur sept portent une garde, et trois d'entre elles écrivent un
      `console.error` nommant la recette absente. La convention existe ; `mangelettres`,
      `redirect` et `splitouine` ne la suivaient pas. Elles y sont alignées, à l'identique.
- [x] 4.1bis **Ne pas confondre ce qui est lu avec ce qui est vu.** Ce changement a affirmé,
      dans sa proposition comme dans son message de commit, que le script « lève une
      `TypeError` ». **Cela n'a jamais été observé.** C'est une lecture de code : la ligne
      déréférence sans garde, donc elle doit lever. Les deux tentatives de constat ont
      échoué pour des raisons opposées — en local le corps de la recette ne s'exécute pas,
      faute de fichiers audio dans les fixtures (`readyState` reste à `0`, donc
      `DesastreAudio.onReady` ne se déclenche jamais) ; en production il s'exécute, mais le
      défaut y est corrigé depuis. Ce qui est mesuré reste solide — l'absence du bloc
      d'options, sa réapparition après correctif. Ce qui ne l'est pas, c'est la forme exacte
      de l'échec côté client.
- [x] 4.2 Relire les autres recettes de `src/web/desastres/` : `mangelettres` a servi de cas de mesure, rien ne dit que les treize autres déréférencent leurs options de la même façon, ni qu'elles échouent aussi discrètement
      — sept recettes lisent `window.DesastreOptions`. Relevé le 7 août 2026, vérifié à la
      main après qu'une détection automatique eut produit un faux positif sur `splitouine` :

      | Recette | Garde avant | Sans options |
      |---|---|---|
      | `amour` | oui | ne fait rien |
      | `mamie` | oui | `console.error` |
      | `postillons` | oui | `console.error` |
      | `tts` | oui | `console.error` |
      | `mangelettres` | **non** | échec silencieux |
      | `redirect` | **non** | la redirection n'a pas lieu |
      | `splitouine` | **non** | échec silencieux |

      `mangelettres` n'était donc pas un cas isolé : trois recettes sur sept partageaient le
      défaut. Les trois sont alignées sur la convention des quatre autres.
      — Le faux positif mérite d'être noté : `splitouine` teste bien `!window.DesastreOptions
      .splitouine.tween.configuration.duration`, mais c'est un test de propriété interne, pas
      un test de présence. Une recherche de motif l'a compté comme une garde. Les sept ont été
      relues une à une.
