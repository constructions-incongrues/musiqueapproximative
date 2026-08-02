## 1. Production du document XSPF sans PEAR

- [x] 1.1 Relever ce que produit `File_XSPF` aujourd'hui, d'après `listSuccess.xspf.php` : racine `playlist`, `title`, `date`, puis un `track` par morceau portant `location`, `creator`, `title`, `annotation` et `info`
- [x] 1.2 Réécrire `listSuccess.xspf.php` en produisant le XML directement, sans `require('File/XSPF.php')` ni `set_include_path()`. Conserver l'échappement des valeurs, que la bibliothèque assurait
      — le document est bâti avec `DOMDocument`, dont `createTextNode()` échappe. La
      production est isolée dans un partiel, `_xspfPlaylist.php`, partagé avec le gabarit
      de morceau isolé : écrire deux fois le même document, c'est le laisser diverger.
- [x] 1.3 Conserver le comportement du titre : nom du contributeur si `contributor`, terme cherché si `q`, mention de l'ensemble sinon
      — repris à l'identique, y compris le repli sur la valeur du paramètre quand la liste
      filtrée par contributeur ne remonte aucun morceau.
- [x] 1.4 Retirer la manipulation de `error_reporting()` en tête de gabarit, qui n'existait que pour taire les avertissements de dépréciation de la bibliothèque PEAR
- [x] 1.5 Retirer le `// TODO : all this should not be in view` s'il n'a plus lieu d'être, ou le laisser s'il reste vrai — mais ne pas déplacer la logique hors de la vue à cette occasion : ce serait un autre changement
      — retiré. Le gabarit de liste ne fait plus que calculer un titre et déléguer ; il n'y
      a plus de logique à déplacer. Le sérialiser dans le modèle, comme `toJson()`, reste
      possible et reste un autre sujet.
- [x] 1.6 Passer par `$sf_data->getRaw('posts')` plutôt que par le décorateur d'échappement
      — corrige un défaut latent de l'ancien gabarit, qui lisait `$posts` décoré : les
      valeurs arrivaient déjà échappées en HTML, puis `File_XSPF` les échappait une
      seconde fois. Le point ne s'observait pas, le format répondant 500 avant d'y
      parvenir. `listSuccess.json.php` prend déjà cette précaution.

## 2. Morceau isolé

- [x] 2.1 Créer `showSuccess.xspf.php`, servant une playlist d'un seul élément, à l'image de `showSuccess.json.php` et `showSuccess.max.php`
- [x] 2.2 Vérifier que le titre de cette playlist a du sens pour un morceau isolé
      — `Musique Approximative : <artiste> — <titre>`. Un titre de liste
      — « Tous les morceaux » — n'aurait rien voulu dire sur un morceau unique.
- [x] 2.3 Vérifier que `?format=xspf` reste bien pris en compte sur une page de morceau,
      malgré le `$formatsLimited` de `executeShow()`
      — oui. `setFormats()` teste le paramètre contre la liste **complète** avant de poser
      `sf_format` et le type de contenu ; `$formatsLimited` ne restreint que ce qui est
      passé à la vue pour les liens annoncés. Le format reste donc accessible sans être
      annoncé, ce qui est exactement ce que dit l'exigence corrigée.

## 3. Correction de l'exigence du corpus

- [x] 3.1 Confronter le scénario « Formats annoncés au visiteur » au code : `executeShow()` limite les formats à `json`, `executeList()` les passe tous les trois, et le drapeau `display` ne gouverne que les liens visibles du pied de page
      — confirmé sur les deux canaux, dans le code et en production. Le scénario est
      scindé en deux, un par type de page.
- [x] 3.2 Vérifier qu'aucune autre exigence du corpus ne repose sur la même confusion entre `<link rel="alternate">` et liens visibles — relire `metadonnees-partage` en particulier
      — aucune. Les cinq exigences de `metadonnees-partage` et les quatre de
      `embarquement-oembed` portent sur le contenu des métadonnées, jamais sur leur
      annonce.
      — **Mais l'inverse apparaît** : les liens de découverte oEmbed
      — `<link rel="alternate" type="application/json+oembed">` et sa variante XML, émis
      par `layout.php` sur toutes les pages — **ne sont spécifiés nulle part**. C'est
      pourtant le point d'entrée par lequel un consommateur trouve `/oembed`. Un trou du
      corpus, distinct de celui que ce changement corrige, et hors de son périmètre.

## 4. Vérification manuelle

> **Une première correction a été déployée et n'a pas fonctionné.** Le détail est en 4.0 :
> un partiel symfony 1 n'hérite d'aucune variable de son appelant, et le mien attendait
> `$sf_request`. Corrigé, mais **non redéployé** à l'heure où ces lignes sont écrites.
>
> Toutes les cases ci-dessous restent donc ouvertes, et le resteront jusqu'à la prochaine
> mise en ligne. **Elle est automatique** : Plesk tire `main` à chaque poussée, il n'y a pas
> de geste de déploiement — le `make deploy` par rsync du Makefile est déprécié. Fusionner
> cette pull request, c'est donc mettre en production. Ce qui a été fait à la place — un banc d'essai reproduisant cette fois
> l'isolement du partiel, sept assertions vertes, et l'échec attendu sans la variable — ne
> les remplace pas. Le premier banc était vert lui aussi.

- [x] 4.0 **Constater que la première correction ne fonctionnait pas, et trouver pourquoi**
      — relevé après déploiement : `?format=xspf` répondait toujours `500` avec un corps
      vide, sur les deux routes. Le déploiement était pourtant bien passé — `?kraftwerk`
      forçait son désastre, donc la PR #112, postérieure à celle-ci, était en ligne.
      — **Cause : un partiel symfony 1 est hermétique.** `get_partial()` n'appelle que
      `setPartialVars()` : le porteur d'attributs du partiel ne contient que les variables
      qu'on lui passe. Ni `$sf_request`, ni `$sf_data`, ni `$sf_context`. Le partiel
      appelait `$sf_request->getUriPrefix()`, soit une méthode sur `null`, soit une erreur
      fatale, soit un 500 au corps vide.
      — **Le banc d'essai n'a pas pu l'attraper, et c'est instructif.** Il déclarait
      `$sf_request` comme variable locale avant d'inclure le gabarit : il validait la
      logique du gabarit, jamais la portée que le framework lui accorde. Un banc qui
      fournit ce que l'appelant réel ne fournit pas ne teste que lui-même.
      — Corrigé en passant `baseUrl`, calculé par l'appelant. Le partiel a besoin d'un
      préfixe, pas d'un objet requête, et son contrat en devient explicite.
      — Deux contrôles refaits, cette fois en reproduisant l'isolement : sept assertions
      vertes avec `baseUrl`, et l'échec attendu — « Undefined variable $baseUrl » — sans.
      — `DOMDocument` a été écarté comme cause par mesure, non par supposition :
      `/oembed?format=xml` répond `200` en production et construit sa réponse avec
      `SimpleXMLElement`, donc l'extension XML est bien présente dans l'image.
- [ ] 4.1 Demander `/posts?format=xspf` et vérifier un code `200`, le type `application/xspf+xml`, et un corps non vide dont la racine est une playlist XSPF
- [ ] 4.2 Demander `/posts?c=<contributeur>&format=xspf` et vérifier que le titre de la playlist nomme ce contributeur
- [ ] 4.3 Demander `/posts?q=<terme>&format=xspf` et vérifier que le titre reprend le terme
- [ ] 4.4 Demander `/post/:slug?format=xspf` et vérifier une playlist d'un seul élément, servie en `200`
- [ ] 4.5 Ouvrir le document produit dans un lecteur qui lit le XSPF — VLC le fait — et vérifier que les morceaux se chargent depuis leur `location`
- [ ] 4.6 Sur un morceau dont le titre ou le corps contient des guillemets, une esperluette ou un chevron, vérifier que le document reste bien formé. C'est ce que l'échappement de `File_XSPF` assurait, et le point le plus facile à casser en s'en passant
- [ ] 4.7 Vérifier que `/posts?format=json`, `/posts?format=max` et `/posts/feed` répondent exactement comme avant — mêmes types de contenu, mêmes tailles à peu de chose près
- [ ] 4.8 Sur une page de liste servie en HTML, vérifier que les trois `<link rel="alternate">` sont présents et que seuls `json` et `xspf` figurent dans les liens visibles du pied de page
- [ ] 4.9 Sur une page de morceau servie en HTML, vérifier que `json` est le seul format annoncé, tout en restant accessible par `?format=xspf` et `?format=max`
