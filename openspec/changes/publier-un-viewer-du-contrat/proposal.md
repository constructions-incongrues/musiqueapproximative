## Why

Le contrat OpenAPI est servi depuis ce soir, mais sous sa seule forme brute : 700 lignes de
YAML. C'est lisible par une machine et par qui sait lire du YAML ; ce n'est pas une page.
La barre latérale y menait directement, ce qui revenait à répondre « voici le fichier » à
qui demande « que sert ce site ? ».

Le document est pourtant écrit pour être lu — commentaires en français, mentions VÉRIFIÉ et
NON VÉRIFIÉ, avertissement sur la version. Ce travail est perdu tant qu'il faut ouvrir un
éditeur pour en profiter.

L'auteur a tranché entre trois options : ne rien ajouter, renvoyer vers un viewer externe,
ou en héberger un. **Le viewer est auto-hébergé.** Le motif est net : renvoyer vers
`redocly.github.io` ou `petstore.swagger.io` apprendrait à un tiers qui consulte cette API.
Le dépôt n'a aucune dépendance externe à l'exécution hormis le service de glitch, qui est
maison ; celle-ci n'en introduira pas.

## What Changes

- Ajout de `src/web/api.html`, page autonome qui rend le contrat avec Redoc.
- Versement de Redoc 2.5.3 sous `src/web/frontend/assets/javascripts/redoc/`, là où le site
  verse déjà jQuery et jPlayer. Somme de contrôle et procédure de mise à jour dans un
  `PROVENANCE.md` voisin.
- La section « API » de la barre latérale mène à `/api.html` plutôt qu'au YAML brut ; la
  page conserve un lien vers celui-ci.
- Aucune requête vers un tiers, polices comprises.

Le contrat public n'est pas modifié : c'est une présentation de plus, pas une route de plus.

## Hors périmètre

- Modifier le contrat lui-même. Il est servi tel quel, le viewer le lit comme n'importe quel
  consommateur.
- Une page équivalente pour le protocole Subsonic. Il a sa propre documentation.
- Auto-héberger les polices Arvo et Rambla. Le reste du site les charge depuis Google Fonts
  et ce n'est pas la question du jour ; cette page-ci s'en passe simplement.
