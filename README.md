# Musique Approximative

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
