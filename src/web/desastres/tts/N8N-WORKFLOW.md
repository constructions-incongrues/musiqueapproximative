# Workflow n8n pour le désastre TTS

Ce document explique comment utiliser le workflow n8n fourni pour créer un webhook dynamique qui retourne des jingles TTS personnalisés en fonction du contexte.

## Fichier

- `n8n-workflow-example.json` : Workflow n8n complet prêt à importer

## Installation

### 1. Importer le workflow dans n8n

1. Ouvrez votre instance n8n
2. Cliquez sur le menu "Workflows" > "Import from File"
3. Sélectionnez le fichier `n8n-workflow-example.json`
4. Le workflow "TTS Jingles Webhook" sera importé

### 2. Activer le workflow

1. Ouvrez le workflow importé
2. Cliquez sur le nœud "Webhook" pour voir l'URL du webhook
3. Notez l'URL (format : `https://votre-instance-n8n.com/webhook/tts-jingles`)
4. Activez le workflow en cliquant sur le bouton "Active" en haut à droite

### 3. Configurer le désastre TTS

Dans votre fichier `desastres.yml`, configurez la recette TTS pour utiliser l'URL du webhook :

```yaml
tts_jinglist:
  enabled: true
  desastre: tts
  options:
    url: https://votre-instance-n8n.com/webhook/tts-jingles
```

## Architecture du workflow

Le workflow est composé de 3 nœuds :

```
[Webhook] → [Code - Sélection Jingles] → [Respond to Webhook]
```

### 1. Webhook (n8n-nodes-base.webhook)

- Méthode : POST
- Path : `tts-jingles`
- Reçoit le contexte envoyé par le désastre TTS

### 2. Code - Sélection Jingles (n8n-nodes-base.code)

Ce nœud contient la logique de sélection des jingles basée sur :

#### Contexte temporel
- **Heure de la journée** : Jingles différents pour matin (6h-12h), après-midi (12h-18h), soirée (18h-22h), nuit (22h-6h)
- **Heure pile** : Jingles spéciaux à chaque heure pleine (ex: "Il est 15 heures sur Musique Approximative")
- **Quarts d'heure** : Jingles génériques pour les autres moments
- **Week-end** : Jingles spéciaux samedi/dimanche
- **Décembre** : Jingles de fêtes

#### Données reçues
Le nœud reçoit le contexte complet du désastre TTS :
```javascript
{
  date: {
    day: 15,
    month: 11,
    year: 2025,
    hour: 14,
    minute: 30,
    weekday: 5,
    timestamp: 1731679800000
  },
  query: {
    title: "Nom du morceau",
    artist: "Nom de l'artiste",
    contributor: "Nom du contributeur"
  },
  options: { /* options de la recette */ },
  allDesastreOptions: { /* tous les désastres actifs */ },
  page: {
    url: "https://...",
    pathname: "/post/123",
    title: "Ma Playlist"
  },
  audio: {
    duration: 234.5,
    currentTime: 0
  }
}
```

### 3. Respond to Webhook (n8n-nodes-base.respondToWebhook)

- Retourne la liste de jingles au format JSON
- Format : `["Jingle 1", "Jingle 2", "Jingle 3", ...]`

## Personnalisation

### Ajouter vos propres jingles

Éditez le nœud "Code - Sélection Jingles" et modifiez les tableaux de jingles :

```javascript
const jinglesByHour = {
  morning: [
    "Votre jingle du matin 1",
    "Votre jingle du matin 2",
    // ...
  ],
  afternoon: [
    "Votre jingle d'après-midi 1",
    // ...
  ],
  // etc.
};
```

### Ajouter une logique personnalisée

Vous pouvez ajouter des conditions basées sur n'importe quelle donnée du contexte :

```javascript
// Exemple : Jingles basés sur l'artiste
if (context.query.artist && context.query.artist.toLowerCase().includes('beatles')) {
  availableJingles = availableJingles.concat([
    "Les Beatles sur Musique Approximative !",
    "Un classique des Beatles pour vous !"
  ]);
}

// Exemple : Jingles basés sur le titre du morceau
if (context.query.title && context.query.title.toLowerCase().includes('love')) {
  availableJingles = availableJingles.concat([
    "De l'amour dans l'air !",
    "Un titre qui parle d'amour !"
  ]);
}

// Exemple : Jingles basés sur le contributeur
if (context.query.contributor === 'john') {
  availableJingles = availableJingles.concat([
    "Sélection de John !",
    "Un morceau choisi par John !"
  ]);
}

// Exemple : Jingles spéciaux pour une page particulière
if (page.pathname.includes('/special')) {
  availableJingles = availableJingles.concat([
    "Page spéciale détectée !",
    "Vous êtes sur la page spéciale !"
  ]);
}

// Exemple : Jingles basés sur la durée du morceau
if (context.audio && context.audio.duration > 300) {
  availableJingles = availableJingles.concat([
    "Un long morceau pour vous !",
    "Plus de 5 minutes de pur bonheur musical !"
  ]);
}

// Exemple : Jingles pour un jour spécifique
if (date.day === 1 && date.month === 1) {
  availableJingles = availableJingles.concat([
    "Bonne année avec Musique Approximative !",
    "Première journée de l'année sur MA !"
  ]);
}
```

## Exemples de réponse

Le workflow retournera un tableau JSON comme celui-ci :

```json
[
  "Bon après-midi sur Musique Approximative !",
  "L'après-midi continue sur Musique Approximative !",
  "Restez avec nous cet après-midi sur Musique Approximative !",
  "Vous écoutez Musique Approximative, la radio qui déchire !",
  "Musique Approximative, votre radio préférée !",
  "Restez branchés sur Musique Approximative !"
]
```

Le désastre TTS choisira **une phrase au hasard** dans ce tableau.

## Débogage

### Tester le webhook

Vous pouvez tester le webhook avec `curl` :

```bash
curl -X POST https://votre-instance-n8n.com/webhook/tts-jingles \
  -H "Content-Type: application/json" \
  -d '{
    "date": {
      "day": 15,
      "month": 11,
      "year": 2025,
      "hour": 14,
      "minute": 30,
      "weekday": 5,
      "timestamp": 1731679800000
    },
    "query": {
      "title": "Love Song",
      "artist": "The Beatles",
      "contributor": "john"
    },
    "options": {},
    "page": {
      "url": "https://test.com",
      "pathname": "/",
      "title": "Test"
    },
    "audio": {
      "duration": 234.5,
      "currentTime": 0
    }
  }'
```

### Consulter les logs

Dans n8n :
1. Ouvrez le workflow
2. Cliquez sur "Executions" en haut à droite
3. Vous verrez l'historique de toutes les exécutions
4. Cliquez sur une exécution pour voir les données reçues et renvoyées

### Logs dans le navigateur

Côté client, le désastre TTS logge toutes ses actions dans la console du navigateur :

```
[desastres/tts] Fetching text from URL: https://...
[desastres/tts] Sending context to URL: {...}
[desastres/tts] Text selected from URL (6 options): Bon après-midi...
```

## Alternatives

Si vous n'utilisez pas n8n, vous pouvez créer un endpoint similaire avec :

### Node.js + Express

```javascript
const express = require('express');
const app = express();

app.use(express.json());

app.post('/tts-jingles', (req, res) => {
  const context = req.body;
  const date = context.date;

  const jingles = [];

  // Votre logique ici
  if (date.hour >= 6 && date.hour < 12) {
    jingles.push("Bonjour !");
  }
  // ...

  res.json(jingles);
});

app.listen(3000);
```

### PHP

```php
<?php
header('Content-Type: application/json');

$context = json_decode(file_get_contents('php://input'), true);
$date = $context['date'];

$jingles = [];

// Votre logique ici
if ($date['hour'] >= 6 && $date['hour'] < 12) {
  $jingles[] = "Bonjour !";
}
// ...

echo json_encode($jingles);
```

### Python + Flask

```python
from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route('/tts-jingles', methods=['POST'])
def tts_jingles():
    context = request.json
    date = context['date']

    jingles = []

    # Votre logique ici
    if 6 <= date['hour'] < 12:
        jingles.append("Bonjour !")
    # ...

    return jsonify(jingles)

if __name__ == '__main__':
    app.run()
```

## Configuration avancée

### Utiliser plusieurs endpoints

Vous pouvez créer plusieurs workflows n8n pour différents types de jingles :

```yaml
# Jingles généraux toutes les 15 minutes
- query: "context.date.minute == 0 || context.date.minute == 15 || context.date.minute == 30 || context.date.minute == 45"
  recettes: [tts_jingles_general]
  probability: 1.0

# Jingles spéciaux les heures piles
- query: "context.date.minute == 0"
  recettes: [tts_jingles_hourly]
  probability: 1.0

recettes:
  tts_jingles_general:
    enabled: true
    desastre: tts
    options:
      url: https://n8n.com/webhook/tts-general

  tts_jingles_hourly:
    enabled: true
    desastre: tts
    options:
      url: https://n8n.com/webhook/tts-hourly
```

### Combiner URL et textes statiques

Vous pouvez aussi utiliser des textes statiques comme fallback :

```yaml
tts_jinglist:
  enabled: true
  desastre: tts
  options:
    url: https://n8n.com/webhook/tts-jingles
    # Si l'URL ne répond pas, utilisera ces textes
    texts:
      - "Fallback jingle 1"
      - "Fallback jingle 2"
```

Note : Actuellement le code utilise l'ordre de priorité URL > texts > selector, donc les `texts` ne seront utilisés que si l'URL échoue.

## Support

Pour plus d'informations :
- Documentation n8n : https://docs.n8n.io/
- Documentation du désastre TTS : voir `README.md`
