# Relevé des routes — 2026-08-18

Source : environnement `test` (base à jour, fixtures), interrogé par `sfBrowser`, c'est-à-dire
le même harnais que le test de vérification. Script de sondage jetable, supprimé après usage.

> L'environnement `dev` n'a pas pu servir de source : sa base n'a pas les colonnes
> `track_duration` et `track_size` que `schema.yml` déclare, et toute route touchant `Post`
> y répond 500. C'est un défaut d'environnement local, étranger à ce changement.

## 1.1 — Statuts et types de contenu servis

| route | statut | Content-Type | clés de premier niveau (JSON) |
| --- | --- | --- | --- |
| `GET /` | 302 | `text/html; charset=utf-8` | — |
| `GET /post/{slug}` | 200 | `text/html; charset=utf-8` | — |
| `GET /post/{slug}?format=json` | 200 | `application/vnd.api+json; charset=utf-8` | `posts` |
| `GET /post/{slug}?format=xspf` | 200 | `application/xspf+xml; charset=utf-8` | — |
| `GET /post/{slug}?format=max` | 200 | `application/maxmsp+text` | — |
| `GET /post/md5/{md5sum}` | 200 | `application/vnd.api+json; charset=utf-8` | `id, track_duration, track_size, buy_url, publish_on, created_at, updated_at, href, body, track, contributor, links` |
| `GET /posts` | 200 | `text/html; charset=utf-8` | — |
| `GET /posts?format=json` | 200 | `application/vnd.api+json; charset=utf-8` | `posts` |
| `GET /posts?format=xspf` | 200 | `application/xspf+xml; charset=utf-8` | — |
| `GET /posts?format=max` | 200 | `application/maxmsp+text` | — |
| `GET /posts/feed` | 200 | `application/rss+xml; charset=UTF-8` | — |
| `GET /posts/next?current={id}` | 200 | `application/vnd.api+json; charset=utf-8` | `url, title` |
| `GET /posts/prev?current={id}` | 200 | `application/vnd.api+json; charset=utf-8` | `url, title` |
| `GET /posts/random` | 200 | `application/vnd.api+json; charset=utf-8` | `url, title` |
| `GET /oembed?url=…` | 200 | `application/json+oembed` | `version, type, provider_name, provider_url, height, width, title, description, html` |
| `GET /oembed?url=…&format=xml` | 200 | `text/xml+oembed; charset=utf-8` | — |
| `GET /post/{slug inconnu}` | 404 | `text/html; charset=utf-8` | — |
| `GET /post/{slug inconnu}?format=json` | 404 | `text/html; charset=utf-8` | — |

Trois constats que le relevé impose au contrat :

1. **Le type JSON n'est jamais `application/json`.** `JsonApiFilter` réécrit tout en
   `application/vnd.api+json`, y compris sur `/posts/next|prev|random` où
   `renderJsonPost()` pose pourtant `application/json`. La spec `formats-de-sortie` exige
   `application/json` : c'est l'écart que la story 1 corrigera. Le contrat déclare ce qui
   est servi.
2. **`/post/md5/` sert l'objet nu** là où `/post/{slug}?format=json` sert `{"posts":[…]}`.
   C'est la story 4.
3. **`/post/md5/` expose des champs que la spec dit absents** — `publish_on`, `created_at`,
   `updated_at`, plus `track_duration` et `track_size` non documentés. Constat de plus pour
   la story 4, qui ne l'avait pas relevé.

## 1.2 — Paramètres réellement pris en compte

| route | paramètre | obligatoire | note |
| --- | --- | --- | --- |
| toutes les pages HTML | `play` | non | lu par `layout.php`, défaut `app_autoplay` |
| `/` | `c` | non | redirige vers le dernier morceau du contributeur |
| `/post/{slug}` | `slug` | oui (chemin) | |
| `/post/{slug}` | `format` | non | `json`, `xspf`, `max` ; valeur inconnue → HTML |
| `/post/{slug}` | `embed` | non | choisit un gabarit `Embed*` |
| `/post/{slug}` | `random` | non | défaut `0` |
| `/post/md5/{md5sum}` | `md5sum` | oui (chemin) | |
| `/posts` | `q` | non | recherche ; l'emporte sur `c` |
| `/posts` | `c` | non | filtre par contributeur |
| `/posts` | `format` | non | `json`, `xspf`, `max` |
| `/posts/feed` | `contributor` | non | |
| `/posts/feed` | `count` | non | **défaut 50** — le seul bornage existant du site |
| `/posts/next`, `/posts/prev` | `current` | **oui** | identifiant numérique, pas un slug |
| `/oembed` | `url` | **oui** | le slug en est extrait par `basename()` |
| `/oembed` | `format` | non | `json` (défaut) ou `xml` |

Aucun paramètre de bornage sur `/posts` : `getOnlinePosts()` est appelé sans limite. Le
relevé confirme ce que la story 2 corrigera ; le contrat ne l'invente pas.

## Deux défauts trouvés par la machine, hors périmètre de ce changement

Ni l'un ni l'autre n'est un 404 : ce sont des erreurs PHP fatales, donc des 500.

1. **`/post/md5/{md5sum inconnu}`** → `executeMd5()` appelle `$post->toJson()` sans
   `forward404Unless` préalable. Appel de méthode sur `false`.
   (`actions.class.php:157-159`)
2. **`/posts/next` et `/posts/prev` sans `current`, ou avec un identifiant inconnu** →
   `PostTable::getNextPost()` reçoit `false` là où sa signature exige un `Post`. TypeError.
   (`actions.class.php:299` et `:305`)

Ils relèvent de la **story 5** (`servir-les-erreurs-en-json`), qui les traitera. Le contrat
ne déclare pour ces routes que la réponse de succès : déclarer un 404 qui n'existe pas
serait exactement le mensonge que ce changement entend rendre impossible.

## 1.3 — Ce que `docs/API_CURRENT_STATE.md` dit de faux

Le document est daté du 2026-04-20 et se présente comme une « auto-generated analysis ».
Confronté au relevé ci-dessus, **son tableau des routes est faux de bout en bout** : les
neuf adresses qu'il donne n'existent pas.

| ce que le document annonce | ce que la machine répond |
| --- | --- |
| `GET /:slug.json` | n'existe pas. La route est `/post/{slug}?format=json` |
| `GET /posts.json` | n'existe pas. La route est `/posts?format=json` |
| `GET /posts.json?q=X`, `?c=X` | n'existent pas, mêmes motifs |
| `GET /md5/:md5sum` | n'existe pas. La route est `/post/md5/{md5sum}` |
| `GET /random` | n'existe pas. La route est `/posts/random` |
| `GET /next/:current`, `/prev/:current` | n'existent pas. Ce sont `/posts/next?current=` et `/posts/prev?current=`, où `current` est un paramètre de requête, pas un segment de chemin |
| Content-Type `application/json` sur les neuf lignes | faux neuf fois sur neuf : c'est `application/vnd.api+json` partout, et `application/json+oembed` sur `/oembed` |

Seule la moitié « Response Structures » est à peu près juste — et elle décrit `/md5` comme
non enveloppé, ce que le relevé confirme.

**Neuf routes inexistantes.** C'est exactement le nombre, et exactement le mode de défaillance,
qui ont fait supprimer `docs/memory-bank/` : « après avoir dérivé au point de documenter cinq
routes inexistantes ». Ce document a fait pire, en dix-huit mois de moins, et il porte la
mention « auto-generated » qui lui donne une autorité qu'il n'a jamais eue — rien ne l'a
jamais confronté au site.

C'est la justification de son retrait, et c'est aussi la démonstration pratique de pourquoi
le test de la tâche 3 n'est pas négociable.
