## Why

`docs/antora.yml` déclare `nav: - modules/ROOT/nav.adoc`. **Ce fichier n'existe pas.** Le
site de documentation n'a donc aucune barre latérale, et la seule voie vers une page est le
sommaire écrit à la main dans `index.adoc`.

Ce sommaire a dérivé. Relevé du jour : **16 pages publiées, 7 atteignables, 8 orphelines.**

```
  README                        Guide pour contribuer à la documentation
  cicd/github-actions           GitHub Actions
  cicd/release-please           Release Please
  developpement/commandes       Commandes utiles
  developpement/environnement   Environnement de développement
  developpement/tests           Tests
  ghcr                          Utilisation de GitHub Container Registry
  migration-utf8mb4             Migration de la base en utf8mb4
```

**Trois des orphelines expliquent comment monter l'environnement et lancer les tests.** Un
contributeur qui arrive sur la documentation ne peut pas les trouver : elles sont publiées,
servies, et hors d'atteinte. C'est le même mode de défaillance que le contrat OpenAPI servi
en 404 — le travail existe, personne ne peut y accéder.

La dérive n'est pas un accident isolé : `migration-utf8mb4.adoc`, écrit ce matin, est déjà
orphelin. Un sommaire manuel dérive à chaque page ajoutée, sans rien signaler.

## What Changes

- Création de `docs/modules/ROOT/nav.adoc`, qui inscrit les **seize** pages, groupées comme
  la documentation l'est déjà : démarrer, développer, déployer, contribuer.
- `index.adoc` cesse de porter la liste exhaustive des pages techniques et redevient une
  page d'accueil : ce qu'est le projet, comment y participer. La navigation exhaustive est
  le travail de `nav.adoc`, et la duplication est ce qui a dérivé.
- Ajout d'un contrôle en intégration : **toute page absente de `nav.adoc` fait échouer la
  CI, en la nommant.** C'est la question ouverte de la story, et elle est tranchée ici.

## Hors périmètre

- Le contenu des pages. Aucune n'est réécrite ; certaines sont sûrement périmées, c'est un
  autre travail. **Deux liens internes cassés ont été mesurés en passant, et restent
  dehors** : `deploiement.adoc:36` renvoie à `xref:API_SUBSONIC.adoc[]`, page qui n'existe
  pas — le contenu réel est `docs/API_SUBSONIC.md`, hors de l'arborescence Antora ; et
  `README.adoc:112` porte `xref:autre-page.adoc[]` comme exemple de syntaxe, ce qu'Antora
  signale comme une cible manquante. Les corriger est du contenu, et ce change n'en touche
  pas.
- La documentation d'API, qui a sa propre adresse depuis ce soir.
- Le renommage ou la réorganisation des fichiers. Les grouper dans `nav.adoc` suffit à les
  rendre atteignables ; les déplacer casserait les liens existants sans rien rendre.
