## Why

Le site ne dit nulle part quelle version il sert. La version existe pourtant : `src/VERSION`
est incrémenté par release-please à chaque publication, et `VersionFilter` la charge déjà
dans `app_version` — mais **uniquement pour casser le cache des assets**, sous la forme
`?v=1.11.0` dans les URL. Elle est dans la page, invisible.

Deux conséquences. Un visiteur qui constate un comportement ne peut pas dire lequel il
constate. Et le contrat OpenAPI, servi depuis ce soir, avertit que `info.version` « ne
signale pas les changements de cette API » et renvoie au diff du document comme seul canal
— sans donner de point de départ. Afficher la version et lier les notes de publication
donne ce point de départ.

Ce n'est pas un manque grave, c'est un manque bête : tout est là, rien n'est montré.

## What Changes

- La barre latérale, sous les crédits, affiche la version servie et un lien vers ses notes
  de publication sur GitHub.
- Le cas sans `src/VERSION` — un poste de développement, un clone frais — affiche « Version
  de développement » et renvoie à la liste des publications plutôt qu'à une étiquette
  inexistante.
- Aucun nouveau chargement : la valeur vient de `app_version`, déjà posée par le filtre.

Le contrat public n'est pas concerné : aucune route, aucun format, aucune sortie machine ne
change.

## Hors périmètre

- **Exposer la version dans les réponses machine**, en-tête ou champ JSON. Le contrat dit
  déjà que `info.version` n'est pas un signal de compatibilité ; l'ajouter ailleurs
  donnerait à croire l'inverse.
- **Afficher l'empreinte du commit** ou la date de déploiement. La version nommée est ce
  qui se relie aux notes de publication ; le reste demanderait une chaîne de build que ce
  déploiement n'a pas.
- Changer la façon dont la version sert de casse-cache aux assets.
