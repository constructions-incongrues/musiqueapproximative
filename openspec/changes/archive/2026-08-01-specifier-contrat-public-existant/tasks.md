## 1. Rédaction des spécifications

- [x] 1.1 Spécifier `catalogue-morceaux` d'après `PostTable.class.php`, `routing.yml` et les actions `home`, `show`, `next`, `prev`, `random`, `md5`, `list`
- [x] 1.2 Spécifier `formats-de-sortie` d'après `setFormats()`, `Post::toJson()` et les gabarits `*.json.php`, `*.xspf.php`, `*.max.php`
- [x] 1.3 Spécifier `flux-syndication` d'après `executeFeed()`
- [x] 1.4 Spécifier `embarquement-oembed` d'après `executeOembed()` et `showEmbed.php`
- [x] 1.5 Spécifier `desastres` d'après `sfDesastreManager`, `DesastreHelper` et les recettes et règles de `config/desastres/`
- [x] 1.6 Consigner les défauts constatés dans les specs concernées, sans les corriger

## 2. Vérification manuelle des specs contre le code

> Exécutée par le mainteneur, sur un environnement dont l'agent ne disposait pas. Les
> scénarios avaient été écrits d'après le code lu ; ils ont été exercés contre
> l'application, et **aucun n'a été contredit**. La lecture du code s'est donc révélée
> exacte, y compris sur les points les plus incertains : la fenêtre de deux heures sur
> `publish_on`, les extrémités du catalogue et la résolution d'URL par `/oembed`.
>
> La tâche 2.6 a constaté le comportement d'alors — une réponse vide sur format inconnu.
> Le changement `fiabiliser-negociation-format-oembed` l'a remplacé depuis par un `501` :
> c'est ce dernier qui décrit l'état visé, et son delta prévaudra dans le corpus.

- [x] 2.1 Vérifier la fenêtre de deux heures sur `publish_on` : un morceau à publication dans une heure est-il bien accessible, à trois heures bien inaccessible
- [x] 2.2 Vérifier le comportement de `/posts/next` et `/posts/prev` aux extrémités du catalogue, quand aucun morceau suivant ou précédent n'existe
- [x] 2.3 Vérifier la réponse de `/post/md5/:md5sum` pour une empreinte inconnue
- [x] 2.4 Vérifier les types de contenu réellement émis pour `format=json`, `format=xspf` et `format=max`
- [x] 2.5 Vérifier la résolution du morceau par `/oembed` pour une URL comportant des paramètres de requête ou une barre oblique finale
- [x] 2.6 Vérifier qu'un `format` inconnu sur `/oembed` produit bien une réponse vide, et non une erreur
- [x] 2.7 Vérifier le déclenchement d'un désastre par paramètre d'URL, et le cumul de plusieurs recettes sur une même page
- [x] 2.8 Corriger les scénarios que la vérification contredit — le comportement observé fait foi, pas la lecture du code
      — sans objet : aucun scénario n'a été contredit.
- [x] 2.9 Reporter dans `openspec/specs/` les corrections issues de 2.8
      — sans objet, 2.8 n'ayant produit aucune correction. Le corpus, peuplé avant
      vérification, se trouve confirmé tel quel.

## 3. Promotion dans le corpus principal

- [x] 3.1 Promouvoir les specs dans `openspec/specs/`
      — fait avant la vérification de la section 2, sur décision explicite. Le pari s'est
      avéré : la vérification n'a rien contredit, le corpus était juste dès sa promotion.
- [x] 3.2 Supprimer `docs/memory-bank/README.adoc`
      — le corpus de specs le remplace sur le comportement observable, et
      `openspec/config.yaml` sur le contexte technique. Le document avait dérivé au point
      de décrire cinq routes inexistantes et un lecteur abandonné. Ni le code ni la
      navigation Antora ne le référençaient ; les renvois qui le mentionnaient dans le
      schéma et la configuration ont été réécrits.
- [x] 3.3 Ouvrir un changement dédié pour chacun des défauts consignés
      — `implementer-format-max-morceau`, `alleger-taille-enclosure-flux` et
      `fiabiliser-negociation-format-oembed`. Artefacts complets, aucun code écrit :
      chaque proposition tranche le comportement visé, à arbitrer en relecture.
