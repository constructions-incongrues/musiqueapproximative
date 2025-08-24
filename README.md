# Musique Approximative - Site Web

## 🚀 Démarrage rapide

### Prérequis
- Docker Desktop installé et en cours d'exécution
- Fichier `/etc/hosts` configuré avec :
  ```
  127.0.0.1 www.musiqueapproximative.test
  ```

### Démarrage automatique
```bash
./start-dev.sh
```

### Arrêt
```bash
./stop-dev.sh
```

### Accès à l'application
- **Direct** : http://localhost:8001
- **Via Nginx** : http://localhost:8080
- **Nom de domaine** : http://www.musiqueapproximative.test:8080

### Commandes utiles
```bash
# Vider le cache Symfony
docker-compose exec php php symfony cache:clear

# Voir les logs
docker-compose logs -f

# Accéder au container PHP
docker-compose exec php bash
```

## 📁 Structure du projet

## Développement

### Installer Docker et Docker Compose

- [Docker](https://docs.docker.com/install/#supported-platforms)
- [Docker Compose](https://docs.docker.com/compose/install/#install-as-a-container)

### Cloner le dépôt de sources

```sh
git clone git@github.com:constructions-incongrues/net.musiqueapproximative.www.git
```

### Ajouter au fichier `/etc/hosts`

```hosts
127.0.0.1   www.musiqueapproximative.test adminer.musiqueapproximative.test
```

### Démarrer l'application

```sh
cd net.musiqueapproximative.www
docker-compose up
```

### C'est prêt

- Frontend (prod) : <http://www.musiqueapproximative.test>
- Frontend (dev) : <http://www.musiqueapproximative.test/frontend_dev.php>
- Admin (prod) : <http://www.musiqueapproximative.test/admin_prod.php>
- Admin (dev) : <http://www.musiqueapproximative.test/admin_dev.php>
- Gestion de la base de données <http://adminer.musiqueapproximative.test> (identifiants de connexion : root / root)

## Publication d'une nouvelle version

### Création de la version dans le dépôt de sources

```sh
VERSION=<VERSION>
git hf release start ${VERSION}
git hf release finish ${VERSION}
```

### Déploiement vers le serveur de production

```sh
VERSION=<VERSION>
git checkout ${VERSION}
ant configure build deploy -Dprofile=pastishosting
```

## Recettes pour un désastre

Les recettes sont configurées dans le fichier `src/apps/frontend/config/desastre/recettes.yml`.

Le contenu des recettes se trouve dans le dossier `src/web/desastre/recettes`.
