---
type: overview
section: define
generated: 2026-08-20
sources: [product.md, personas.md]
---

# PM Context Brief
Generated 2026-08-20 · Project: musiqueapproximative · Sources: product.md, personas.md

## What we do
Musique Approximative est un site de partage musical quotidien : un collectif publie un
morceau à la fois — fichier audio, artiste, titre, et un texte en markdown qui dit
pourquoi — et le site sert ce catalogue à l'écoute, à la navigation et à la récupération
machine (JSON, XSPF, CSV, Max/MSP, RSS, oEmbed, Subsonic). Le visiteur arrive sur le
dernier morceau publié et enchaîne ; le contributeur poste depuis un admin sfGuard. Une
couche de « désastres » abîme volontairement les pages — et depuis peu le son — selon des
règles tirées du contenu : c'est un parti pris éditorial, pas un défaut. 8 097 morceaux
en production. Le modèle mental est celui d'une **émission, pas d'une bibliothèque** :
`/` ouvre sur *le* morceau du jour, jamais sur l'index.
_More detail: `.nanopm/wiki/docs/product.md`_

## Who it's for
**Deux personas co-primaires, que l'auteur refuse de séparer** — ce sont deux moments de
la même personne. **Le mélomane fêlé** (le contributeur) : poster un morceau avec le
texte qui dit pourquoi, et le retrouver ensuite ; poster marche, retrouver est cassé
(993 morceaux sans filtre dans l'admin, recherche limitée à artiste/titre). **L'auditeur
du jour** : écouter le morceau du jour et enchaîner, être surpris par un choix humain
plutôt que servi par un calcul **(motivation assumed — aucun mot d'auditeur réel dans le
dépôt)**. Le DJ de soirée est le cas de charge de l'auditeur, pas un troisième persona.
Anti-persona : **l'auditeur de plateforme** — celui qui veut un compte, un catalogue à
parcourir, ses favoris, des recommandations ; le servir transformerait l'émission en
service de streaming et exigerait d'éteindre les désastres.
_More detail: `.nanopm/wiki/docs/personas.md`_

## How we make money
Il ne gagne pas d'argent **(assumed — aucun document de modèle économique dans le dépôt)**.
Aucune monétisation dans le code : pas de paiement, pas de compte visiteur, pas de
publicité. Le seul signal financier est un lien de don HelloAsso en pied de page
(« le fonctionnement de ce site demande du temps et de l'argent »). Code sous AGPLv3,
hébergement Pastis Hosting, développement par Constructions Incongrues.

## Why we exist
**(assumed — reconstruit depuis le code, aucune vision écrite dans le dépôt)** : donner un
lieu à la recommandation musicale entre gens qui se connaissent, sans algorithme ni
catalogue négocié. Le schéma rend le texte du contributeur (`body`) aussi obligatoire que
la date de publication — le message compte autant que le morceau. **Un principe est
explicite, lui, et confirmé par l'auteur : l'ouverture des formats.** AGPLv3, aucun silo,
la donnée sort par autant de portes que possible — tenue sans usage constaté, parce
qu'un principe ne se mesure pas à son audience. Stade : produit mature et stable,
alimenté quotidiennement de longue date ; l'enjeu est de tenir l'archive à l'échelle
qu'elle a prise, pas de croître.

## Who decides
**(assumed)** Tristan Rivoallan / Constructions Incongrues est le mainteneur du code et
l'auteur du plan de release ; les décisions produit passent par `openspec/discovery.md`
et les changes OpenSpec. `bertier@musiqueapproximative.net` est le contact public. Les
contributeurs du collectif décident du contenu, pas du produit.

## What's NOT known yet
- **Le pari central n'est pas mesuré** : tout repose sur le fait que le collectif
  continue de poster, et rien ne suit le nombre de contributeurs distincts qui postent
  par trimestre. Test le moins cher : une requête SQL sur trois ans.
- **Aucune mesure d'audience** : le seul traceur est un `ga.js` mort (`layout.php:486`).
  Ni l'usage de `/posts`, ni les abonnés RSS, ni les appels aux formats machine ne sont
  comptés. Toutes les questions « est-ce que quelqu'un s'en sert » sont ouvertes.
- **43 % de l'effort de vérification s'adresse à un absent** — 12 fichiers de test sur 28
  portent sur des surfaces sans consommateur observé. C'est un coût assumé au titre du
  principe d'ouverture, pas une erreur ; mais il coexiste avec deux douleurs chiffrées du
  contributeur qui n'ont pas bougé en six mois.
- **Subsonic** : 28 méthodes livrées et testées, classées aspirationnelles par l'auteur,
  sans date ni décision d'annonce ou de retrait.
- **Pages Define manquantes** : vision-mission, business-model, org. Tout ce qui est
  marqué (assumed) ci-dessus vient de là.
