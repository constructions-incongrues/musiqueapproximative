## 1. Rédaction des spécifications

- [x] 1.1 Spécifier `catalogue-morceaux` d'après `PostTable.class.php`, `routing.yml` et les actions `home`, `show`, `next`, `prev`, `random`, `md5`, `list`
- [x] 1.2 Spécifier `formats-de-sortie` d'après `setFormats()`, `Post::toJson()` et les gabarits `*.json.php`, `*.xspf.php`, `*.max.php`
- [x] 1.3 Spécifier `flux-syndication` d'après `executeFeed()`
- [x] 1.4 Spécifier `embarquement-oembed` d'après `executeOembed()` et `showEmbed.php`
- [x] 1.5 Spécifier `desastres` d'après `sfDesastreManager`, `DesastreHelper` et les recettes et règles de `config/desastres/`
- [x] 1.6 Consigner les défauts constatés dans les specs concernées, sans les corriger

## 2. Vérification manuelle des specs contre le code

> Cette section demande un environnement Docker fonctionnel, indisponible lors de la
> rédaction. Chaque scénario a été écrit d'après le code lu, mais aucun n'a été exercé
> contre l'application en fonctionnement.

- [ ] 2.1 Vérifier la fenêtre de deux heures sur `publish_on` : un morceau à publication dans une heure est-il bien accessible, à trois heures bien inaccessible
- [ ] 2.2 Vérifier le comportement de `/posts/next` et `/posts/prev` aux extrémités du catalogue, quand aucun morceau suivant ou précédent n'existe
- [ ] 2.3 Vérifier la réponse de `/post/md5/:md5sum` pour une empreinte inconnue
- [ ] 2.4 Vérifier les types de contenu réellement émis pour `format=json`, `format=xspf` et `format=max`
- [ ] 2.5 Vérifier la résolution du morceau par `/oembed` pour une URL comportant des paramètres de requête ou une barre oblique finale
- [ ] 2.6 Vérifier qu'un `format` inconnu sur `/oembed` produit bien une réponse vide, et non une erreur
- [ ] 2.7 Vérifier le déclenchement d'un désastre par paramètre d'URL, et le cumul de plusieurs recettes sur une même page
- [ ] 2.8 Corriger les scénarios que la vérification contredit — le comportement observé fait foi, pas la lecture du code
- [ ] 2.9 Reporter dans `openspec/specs/` les corrections issues de 2.8 : le corpus principal a été peuplé avant vérification, il porte les mêmes hypothèses

## 3. Promotion dans le corpus principal

- [x] 3.1 Promouvoir les specs dans `openspec/specs/`
      — fait avant la vérification de la section 2, sur décision explicite. Le corpus
      principal décrit donc un comportement lu dans le code, pas encore observé. La
      section 2 reste due, et ses conclusions devront être reportées dans le corpus.
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
