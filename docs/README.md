# Documentation Musique Approximative

Cette documentation est générée avec [Antora](https://antora.org/) et déployée automatiquement sur GitHub Pages.

## 📚 Accéder à la documentation

**Documentation en ligne** : https://constructions-incongrues.github.io/musiqueapproximative

## 🏗️ Structure

```
docs/
├── antora.yml              # Descripteur du composant Antora
├── modules/
│   └── ROOT/
│       ├── nav.adoc        # Navigation
│       └── pages/          # Pages de documentation
│           ├── index.adoc
│           ├── guide-demarrage.adoc
│           ├── contribution.adoc
│           ├── architecture.adoc
│           ├── docker.adoc
│           ├── deploiement.adoc
│           ├── developpement/
│           │   ├── environnement.adoc
│           │   └── commandes.adoc
│           └── cicd/
│               ├── github-actions.adoc
│               ├── release-please.adoc
│               └── dependabot.adoc
└── .gitignore
```

## 🔧 Générer localement

### Prérequis

- Node.js 20+
- npm

### Installation

```bash
npm install -g @antora/cli@latest @antora/site-generator@latest
```

### Génération

```bash
# Depuis la racine du projet
antora antora-playbook.yml

# Le site sera généré dans ./build/site
```

### Prévisualisation

```bash
# Avec un serveur HTTP simple
npx http-server build/site -p 8000

# Ou avec Python
cd build/site
python3 -m http.server 8000
```

Puis ouvrir http://localhost:8000

## ✍️ Contribuer à la documentation

### Format

La documentation utilise [AsciiDoc](https://asciidoc.org/), un format de markup plus puissant que Markdown.

### Syntaxe de base

```asciidoc
= Titre de la page
:description: Description de la page

== Section niveau 2

=== Section niveau 3

Paragraphe de texte.

[source,bash]
----
# Bloc de code
echo "Hello"
----

* Liste à puces
* Item 2

.Tableau
[cols="1,2"]
|===
|Colonne 1 |Colonne 2

|Cellule 1
|Cellule 2
|===

xref:autre-page.adoc[Lien vers une autre page]

https://example.com[Lien externe]
```

### Ajouter une page

1. Créer un fichier `.adoc` dans `docs/modules/ROOT/pages/`
2. Ajouter une entrée dans `docs/modules/ROOT/nav.adoc`
3. Commiter et pousser

Le site sera automatiquement régénéré et déployé.

### Références croisées

```asciidoc
xref:guide-demarrage.adoc[Guide de démarrage]
xref:developpement/commandes.adoc[Commandes utiles]
```

## 🚀 Déploiement

Le déploiement est automatique via GitHub Actions :

1. **Trigger** : Push sur `main` modifiant `docs/` ou `antora-playbook.yml`
2. **Build** : Génération du site avec Antora
3. **Deploy** : Publication sur GitHub Pages

Workflow : `.github/workflows/documentation.yml`

## 📖 Ressources

- [Documentation Antora](https://docs.antora.org/)
- [Syntaxe AsciiDoc](https://docs.asciidoctor.org/asciidoc/latest/)
- [Antora UI](https://gitlab.com/antora/antora-ui-default)
