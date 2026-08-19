## Why

Le 2026-08-18, la page de consultation du contrat OpenAPI a été **auto-hébergée exprès** :
Redoc versé au dépôt, 1 071 ko, au motif que « le visiteur qui lit la description de l'API
n'a pas à être annoncé à un tiers ».

Le même jour, les désastres continuaient d'appeler deux CDN à l'exécution, sur des pages
publiques, au hasard des tirages. **Les deux décisions ne peuvent pas rester vraies en même
temps sans qu'on l'ait tranché**, et aujourd'hui la contradiction est muette : elle n'est
écrite nulle part.

Le relevé, mesuré :

| hôte | renvois | fichiers |
| --- | --- | --- |
| `cdn.jsdelivr.net` | 6 | `gsap@3.13.0/dist/gsap.min.js`, `gsap@3.13.0/dist/SplitText.min.js` |
| `cdnjs.cloudflare.com` | 4 | `animejs/3.2.2/anime.min.js` |

**Trois fichiers distincts**, appelés par **7 recettes sur 19** — `amour`, `consonnard`,
`light`, `musique`, `noir`, `splitouine_titles_matchduration`, `voyelliste`.

La story 29, dont celle-ci dépendait, est livrée : le relevé existe, et il montre que ces
recettes sortent réellement. Sur les productions relevées à ce jour, `voyelliste` et
`consonnard` — deux appelantes de jsdelivr — sont parmi les plus tirées. **Ce ne sont pas
des chemins morts.**

## What Changes

- **Trancher, et écrire la décision.** C'est l'objet premier de ce change : la
  contradiction cesse d'être implicite, quelle que soit l'issue.

- **Auto-héberger les trois fichiers**, en suivant le motif déjà posé par Redoc :
  `web/frontend/assets/javascripts/<bibliothèque>/<nom>-<version>.min.js`, la version dans
  le nom, la licence à côté. C'est l'issue retenue — elle aligne les désastres sur une
  décision déjà prise pour la même raison, et la taille ne s'y oppose pas : les trois
  fichiers réunis pèsent une fraction du Redoc déjà versé.

- **Vérifier que chaque licence autorise la redistribution AVANT de verser le fichier.**
  Auto-héberger, c'est redistribuer. `anime.js` est sous MIT ; les conditions de GSAP ont
  changé au fil des versions et `SplitText` était un greffon réservé. Ce point est un
  **verrou**, pas une formalité : si une licence ne le permet pas, la décision pour ce
  fichier bascule sur l'autre branche, et il faut alors l'écrire aussi.

- **Documenter ce que le visiteur cesse d'exposer** : les deux CDN voient aujourd'hui son
  adresse IP, son en-tête `Referer` et son empreinte de navigateur, au hasard d'un tirage
  qu'il ne choisit pas.

**Hors périmètre** : le webhook `n8n`, service maison du collectif, qui relève d'un autre
arbitrage ; les liens externes des pages de contenu ; toute modification du tirage ou des
règles.

## Capabilities

### New Capabilities

- `dependances-tierces` : ce que le site fait charger au navigateur du visiteur depuis un
  hôte qu'il n'a pas choisi, et ce qu'il en écrit.

### Modified Capabilities

- `desastres` : les recettes servent leurs bibliothèques depuis le site, sans que le
  comportement des désastres change.

## Impact

- `src/apps/frontend/config/desastres/recettes/*.yml` — les sept recettes concernées
- `src/web/frontend/assets/javascripts/` — les fichiers versés, à côté de Redoc
- `docs/modules/ROOT/pages/` — la décision écrite
- Aucun changement du tirage, des règles ni du cache
