#!/bin/bash

echo "🛑 Arrêt de l'environnement de développement Musique Approximative..."

# Arrêter les services
echo "📦 Arrêt des services Docker..."
docker-compose down

echo "✅ Services arrêtés avec succès !"
echo ""
echo "💡 Pour redémarrer : ./start-dev.sh"
