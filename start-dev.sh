#!/bin/bash

echo "🚀 Démarrage de l'environnement de développement Musique Approximative..."

# Vérifier que Docker est en cours d'exécution
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker n'est pas en cours d'exécution. Veuillez démarrer Docker Desktop."
    exit 1
fi

# Vérifier que le fichier .env existe
if [ ! -f "etc/musiqueapproximative.localhost/.env" ]; then
    echo "📝 Création du fichier de configuration .env..."
    mkdir -p etc/musiqueapproximative.localhost
    cat > etc/musiqueapproximative.localhost/.env << EOF
DATABASE_HOST=db
DATABASE_NAME=musiqueapproximative
DATABASE_USER=root
DATABASE_PASSWORD=root
EOF
    echo "✅ Fichier .env créé avec succès"
fi

# Démarrer les services
echo "📦 Démarrage des services Docker..."
docker-compose up -d

# Attendre que les services soient prêts
echo "⏳ Attente du démarrage des services..."
sleep 15

# Vérifier le statut
echo "🔍 Vérification du statut des services..."
docker-compose ps

echo ""
echo "✅ Environnement de développement prêt !"
echo ""
echo "🌐 Accès à votre application :"
echo "   - Direct : http://localhost:8001"
echo "   - Via Nginx : http://localhost:8080"
echo "   - Nom de domaine : http://www.musiqueapproximative.test:8080"
echo ""
echo "📝 N'oubliez pas d'ajouter dans /etc/hosts :"
echo "   127.0.0.1 www.musiqueapproximative.test"
echo ""
echo "🛑 Pour arrêter : docker-compose down"
echo ""
echo "🔧 Pour vider le cache Symfony : docker-compose exec php php symfony cache:clear"
