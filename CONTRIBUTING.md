# Guide de contribution

Merci de votre intérêt pour contribuer à Musique Approximative ! 🎵

## Conventional Commits

Ce projet utilise [Conventional Commits](https://www.conventionalcommits.org/) pour la gestion automatique des versions et la génération du changelog.

### Format des commits

```
<type>(<scope>): <description>

[corps optionnel]

[footer(s) optionnel(s)]
```

### Types de commits

- **feat**: Nouvelle fonctionnalité
- **fix**: Correction de bug
- **docs**: Modification de la documentation
- **style**: Changements de style (CSS, formatage du code)
- **refactor**: Refactoring du code
- **perf**: Amélioration de performance
- **test**: Ajout ou modification de tests
- **chore**: Maintenance et tâches diverses

### Exemples

```bash
# Nouvelle fonctionnalité
git commit -m "feat: ajout du support des playlists utilisateur"

# Correction de bug
git commit -m "fix: correction du lecteur audio sur mobile"

# Documentation
git commit -m "docs: mise à jour du README avec instructions Docker"

# Style
git commit -m "style: amélioration du design du lecteur audio"

# Breaking change
git commit -m "feat!: migration vers PHP 8.0

BREAKING CHANGE: PHP 7.4 n'est plus supporté"
```

## Processus de développement

### 1. Cloner le dépôt

```bash
git clone git@github.com:constructions-incongrues/net.musiqueapproximative.www.git
cd net.musiqueapproximative.www
```

### 2. Configurer l'environnement

Suivez les instructions dans le [README.md](README.md) pour configurer Docker et démarrer l'application.

### 3. Créer une branche

```bash
git checkout -b feat/ma-nouvelle-fonctionnalite
```

### 4. Développer et tester

- Faites vos modifications
- Testez localement avec `./start-dev.sh`
- Vérifiez que le code fonctionne correctement

### 5. Commiter avec Conventional Commits

```bash
git add .
git commit -m "feat: description de ma fonctionnalité"
```

### 6. Pousser et créer une Pull Request

```bash
git push origin feat/ma-nouvelle-fonctionnalite
```

Créez ensuite une Pull Request sur GitHub.

## Processus de release

Les releases sont gérées automatiquement par [Release Please](https://github.com/googleapis/release-please) :

1. **Commits** : Utilisez Conventional Commits pour tous vos commits
2. **PR automatique** : Release Please crée automatiquement une PR de release
3. **Changelog** : Le CHANGELOG.md est généré automatiquement
4. **Merge** : Quand la PR de release est mergée, une nouvelle version est créée avec un tag Git

### Versioning

Le projet suit [Semantic Versioning](https://semver.org/) :

- **MAJOR** (1.0.0) : Breaking changes (commits avec `!` ou `BREAKING CHANGE`)
- **MINOR** (0.1.0) : Nouvelles fonctionnalités (commits `feat`)
- **PATCH** (0.0.1) : Corrections de bugs (commits `fix`)

## Standards de code

### PHP

- PHP 7.4+
- Suivre les conventions Symfony 1.5
- Utiliser Doctrine pour les requêtes de base de données

### CSS

- CSS vanilla (pas de préprocesseur)
- Organiser par composants
- Support des thèmes

### JavaScript

- jQuery pour la compatibilité
- Code commenté et lisible

## Tests

Avant de soumettre une PR :

1. Vérifiez la syntaxe PHP : `find src -name "*.php" -exec php -l {} \;`
2. Testez l'application localement
3. Vérifiez que Docker build fonctionne : `docker-compose build`

## Questions ?

N'hésitez pas à ouvrir une issue pour poser vos questions !

---

Merci de contribuer à Musique Approximative ! 🎶
