> Pas de `design.md`. Son instruction est conditionnelle et ce changement enveloppe une
> réponse : un module, aucune dépendance, aucune migration. La contrainte du socle qui
> oriente la solution — les quatre `$.get` de `layout.php` qu'il ne faut pas casser — est
> consignée dans la proposition, comme la règle du dépôt le prévoit dans ce cas.

## 1. Relever l'état de départ

- [x] 1.1 Relever les deux réponses côte à côte : `/post/{slug}?format=json` et
  `/post/md5/{md5sum}` pour le même morceau. Constater que les clés de l'objet sont
  **identiques** et que seule l'enveloppe diffère. C'est ce qui borne cette story à
  l'enveloppe.
- [x] 1.2 Relever ce que servent `/posts/next`, `/posts/prev` et `/posts/random` :
  `{url, title}`. Ce sont elles qu'il ne faut **pas** toucher.
- [x] 1.3 Relever les quatre appels de `layout.php` qui consomment ces routes, pour savoir
  précisément ce qui casserait.

## 2. Envelopper la réponse

- [x] 2.1 Dans `executeMd5`, servir `{"posts":[<morceau>]}` au lieu de l'objet nu. Le
  gabarit de liste montre la forme attendue ; il porte le commentaire qui la justifie —
  « Even single ressources are displayed as lists ».
- [x] 2.2 Ne pas toucher à `Post::toJson()`, partagé par toutes les représentations. Ce
  changement enveloppe, il ne modifie pas l'objet.
- [x] 2.3 Ne pas ajouter de `forward404Unless` : une empreinte inconnue produit
  aujourd'hui une erreur fatale, et la corriger est la story 5. Le constater, le laisser.

## 3. Amender le contrat

- [x] 3.1 `/post/md5/{md5sum}` passe du schéma de l'objet nu à celui de l'enveloppe.
- [x] 3.2 Retirer de la description de la route la mention « Sert l'objet **nu**, sans
  l'enveloppe… Deux contrats pour le même objet ; c'est un écart connu » : l'écart n'existe
  plus. Retirer de même la mention de cet écart dans `info.description`.
- [x] 3.3 Vérifier que la description des routes du lecteur dit toujours que leur forme est
  **délibérée** — c'est désormais la seule divergence assumée, et elle doit rester lisible.
- [x] 3.4 Le test de contrat vérifie les champs de premier niveau : `/post/md5/` doit
  maintenant présenter `posts`, et non plus la liste des champs de l'objet. C'est le
  contrat qui le dit, le test suit.
- [x] 3.5 `make configure`, puis vérifier que le contrat rendu a conservé ses `$ref`.

## 4. Vérification

- [x] 4.1 `docker-compose exec php php symfony test:all` — la suite passe.
- [x] 4.2 Le test de contrat passe, et il **aurait échoué** sans l'amendement : le vérifier
  en rétablissant temporairement l'ancien schéma, puis rétablir.
- [x] 4.3 Comparer au relevé de 1.1 : les deux réponses ont maintenant la même forme.
  Le démontrer en comparant les clés de premier niveau, qui doivent être identiques.
- [x] 4.4 **Ne pas casser la navigation du site.** Vérifier que `/posts/next`, `/posts/prev`
  et `/posts/random` servent toujours `{url, title}`, inchangés.
- [~] 4.5 **Partiellement menée.** Ce qui est vérifié : dans le navigateur, `.nav-l a`
  porte bien `href="/post/ibn-al-rabin-a-geneve?random=0"` et
  `title="Ibn al Rabin - À Genève"`, renseignés par l'AJAX depuis `data.url` et
  `data.title`. La chaîne de consommation est donc intacte, et c'est elle que ce changement
  pouvait casser. Ce qui n'est PAS vérifié : la frappe clavier elle-même — `r` déclenche
  `$('#random').click()` via `jquery.hotkeys`, liaison que le harnais d'automatisation
  n'atteint pas. Le geste n'est pas revendiqué.
- [x] 4.6 Vérifier qu'une empreinte inconnue se comporte **exactement comme avant** : ce
  changement ne doit ni améliorer ni aggraver le cas d'erreur, qui appartient à la story 5.
- [x] 4.7 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [x] 4.8 `openspec validate aligner-la-route-md5-sur-la-forme-commune --type change --strict`.

### Relevés et constats du 2026-08-18

**Six points d'appel, non quatre.** Le packet de la story annonçait quatre `$.get` dans
`layout.php` ; il y en a six (lignes 368, 371, 390, 393, 403, 408). Tous portent sur
`/posts/random|next|prev`, **aucun sur `/post/md5/`** : envelopper cette route ne pouvait
pas casser le lecteur. C'est le fait de sécurité de ce changement.

**`html_entity_decode` n'a pas été repris, délibérément.** `showSuccess.json.php` et
`listSuccess.json.php` l'appliquent à tout le document JSON ; `executeMd5` ne l'a jamais
fait. Mesuré : `Markdown()` échappe bien `&` en `&amp;` et `<` en `&lt;`. Désechapper ces
entités produit un `body.html` où `&` figure nu — du HTML invalide. La route `/md5` est donc
la plus correcte des deux sur ce point, et l'aligner aurait répandu le comportement au lieu
de le corriger. La différence porte sur le **contenu**, non sur la **forme** : un seul
analyseur lit les deux, ce qui était l'objet de cette story.

**Un défaut préexistant, rencontré et écarté.** Dans l'environnement de **test**, une
requête `/post/md5/` isolée sert un corps précédé d'avertissements `Deprecated` de la
bibliothèque PHP-Markdown vendorisée, ce qui invalide le JSON. Mesure A/B :

| | longueur | JSON |
| --- | --- | --- |
| avec ce changement | 1 716 | invalide |
| sans ce changement | 1 701 | invalide |

Les 15 octets d'écart sont exactement l'enveloppe ajoutée. **Le défaut est antérieur et ce
changement lui est neutre.** Il tient à `echo` + `sfView::NONE`, qui écrivent hors de la
réponse. Il ne se manifeste pas dans la séquence du test de contrat — exercée dix fois de
suite sans un échec — ni sur le serveur local, où `/post/md5/` répond normalement.

**La production n'est pas concernée** — vérifié plutôt que supposé. Un premier échantillon
donnait du JSON invalide, ce qui a failli être consigné comme un défaut de production.
C'étaient des **désastres** : une réponse remplaçait les valeurs par leurs types, une autre
introduisait un retour à la ligne brut. Huit demandes successives de la même route donnent
huit réponses valides de 1 001 octets. Le constat initial est retiré.

**La base de développement ne permet pas d'exercer `/post/md5/`** : ses 6 103 morceaux en
ligne portent `track_md5 = ''`. La vérification passe donc par l'environnement de test, qui
a de vraies empreintes, et par le test de contrat.
