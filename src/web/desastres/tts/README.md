# Désastre TTS (Text-to-Speech)

## Description

Le désastre TTS lit automatiquement le texte d'un élément de la page en utilisant l'API Web Speech Synthesis du navigateur.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "TTS" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(TTS).*/"
  recettes: [tts_rapper]
  probability: 1.0
```

## Configuration

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `selector` | string | `'div.descriptif p'` | Sélecteur CSS de l'élément dont le texte sera lu |
| `texts` | array | - | Liste de textes parmi lesquels choisir aléatoirement |
| `url` | string | - | URL qui reçoit le contexte (POST JSON) et retourne un tableau de textes |
| `lang` | string | Aléatoire (`fr-FR`, `fr-CA`, `fr-BE`, `fr-CH`) | Code de langue BCP 47 |
| `rate` | number ou array | Aléatoire (0.5-2.0) | **Nombre** : valeur fixe. **Array** `[min, max]` : valeur aléatoire dans la plage |
| `pitch` | number ou array | Aléatoire (0-2.0) | **Nombre** : valeur fixe. **Array** `[min, max]` : valeur aléatoire dans la plage |
| `volume` | number ou array | Aléatoire (0.3-1.0) | **Nombre** : valeur fixe. **Array** `[min, max]` : valeur aléatoire dans la plage |
| `voices` | array | Toutes les voix disponibles | Liste de noms de voix parmi lesquelles choisir aléatoirement |

### Exemples de configuration

#### Configuration avec valeurs fixes

```yaml
tts_rapper:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    lang: en-US
    rate: 1.0        # Vitesse fixe normale
    pitch: 1.0       # Hauteur fixe normale
    volume: 0.8      # Volume fixe à 80%
    voices:
      - "Google français"
      - "Thomas"
      - "Amélie"
```

#### Configuration avec plages aléatoires

```yaml
tts_robot:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [0.5, 1.5]    # Vitesse aléatoire entre 0.5x et 1.5x
    pitch: [0.8, 1.2]   # Hauteur aléatoire entre 0.8 et 1.2
    volume: [0.6, 1.0]  # Volume aléatoire entre 60% et 100%
```

#### Configuration mixte

```yaml
tts_chipmunk:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [1.5, 2.0]    # Vitesse rapide aléatoire
    pitch: 2.0          # Hauteur très aiguë fixe
    volume: 0.9         # Volume fixe à 90%
```

#### Configuration extrême

```yaml
tts_slowmo:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [0.1, 0.5]    # Très lent
    pitch: [0, 0.5]     # Très grave
    volume: [0.8, 1.0]  # Fort
```

### Configuration minimale

Sans options spécifiques, le désastre utilisera :
- Sélecteur par défaut : `div.descriptif p`
- Langue aléatoire parmi les variantes françaises
- Rate, pitch et volume aléatoires dans les plages par défaut

```yaml
tts_rapper:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
```

## Sources de texte

Le désastre TTS peut obtenir le texte à lire de **3 façons différentes**, avec cet ordre de priorité :

### 1. URL avec contexte (Priorité 1)

L'URL reçoit le contexte complet en POST JSON et retourne un tableau de textes.

**Configuration :**
```yaml
tts_jinglist:
  enabled: true
  desastre: tts
  options:
    url: https://api.example.com/tts/jingles
```

**Contexte envoyé (POST JSON) :**
```json
{
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
    "title": "Nom du morceau",
    "artist": "Nom de l'artiste",
    "contributor": "Nom du contributeur"
  },
  "options": {
    "url": "https://api.example.com/tts/jingles",
    "selector": ".title"
  },
  "allDesastreOptions": {
    "tts": { /* ... */ }
  },
  "page": {
    "url": "https://musiqueapproximative.net/post/123",
    "pathname": "/post/123",
    "title": "Ma Playlist"
  },
  "audio": {
    "duration": 234.5,
    "currentTime": 0
  }
}
```

**Note** : Les champs `query.title`, `query.artist` et `query.contributor` sont extraits automatiquement depuis le DOM de la page. Ils peuvent être absents si les éléments correspondants ne sont pas trouvés.

**Réponse attendue (JSON) :**
```json
[
  "Première phrase possible",
  "Deuxième phrase possible",
  "Troisième phrase possible"
]
```

Le désastre choisira une phrase au hasard dans ce tableau.

### 2. Liste de textes (Priorité 2)

Une liste de textes définie directement dans la configuration.

**Configuration :**
```yaml
tts_jinglist:
  enabled: true
  desastre: tts
  options:
    texts:
      - "Vous écoutez Musique Approximative"
      - "La radio qui déchire"
      - "Restez à l'écoute"
```

### 3. Sélecteur CSS (Priorité 3)

Le texte est extrait d'un élément de la page via un sélecteur CSS.

**Configuration :**
```yaml
tts_rapper:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
```

## Fonctionnement

1. **Chargement** : Le script vérifie la présence des options TTS dans `window.DesastreOptions.tts`
2. **Sélection du texte** :
   - Si `url` est défini : envoie le contexte et récupère la réponse
   - Sinon si `texts` est défini : choisit un texte au hasard
   - Sinon : utilise le sélecteur CSS
3. **Configuration** : Configure les paramètres de synthèse vocale (lang, rate, pitch, volume, voice)
4. **Sélection de la voix** : Choisit une voix au hasard (parmi `voices` si défini, sinon parmi toutes les voix)
5. **Lecture automatique** : Tente de lire automatiquement le texte
6. **Fallback** : Si l'autoplay est bloqué, attend une interaction utilisateur (click, keydown, touchstart)

## Autoplay et restrictions navigateurs

Les navigateurs modernes bloquent l'autoplay audio/vocal sans interaction utilisateur. Le désastre gère ce cas en :
- Tentant d'abord la lecture automatique
- Enregistrant des listeners sur les interactions utilisateur si l'autoplay échoue
- Lançant la lecture dès la première interaction (click, keydown, touchstart)

## Voix disponibles

Les voix disponibles dépendent du système d'exploitation et du navigateur. Pour lister les voix disponibles :

```javascript
speechSynthesis.getVoices().forEach(v => console.log(v.name))
```

## Logs de débogage

Le désastre génère des logs détaillés dans la console :
- `[desastres/tts] Loaded` : Chargement du script
- `[desastres/tts] Options loaded:` : Options chargées
- `[desastres/tts] Using selector:` : Sélecteur utilisé
- `[desastres/tts] Text found:` : Texte à lire (aperçu)
- `[desastres/tts] Using configured/random lang/rate/pitch/volume:` : Paramètres
- `[desastres/tts] Voice set:` : Voix sélectionnée
- `[desastres/tts] ▶ Speech STARTED` : Début de la lecture
- `[desastres/tts] ■ Speech ENDED` : Fin de la lecture
- `[desastres/tts] ✖ Speech ERROR:` : Erreur de lecture

## Fichiers

- `javascript/main.js` : Script principal de synthèse vocale
- `README.md` : Cette documentation

## Compatibilité

L'API Web Speech Synthesis est supportée par la plupart des navigateurs modernes :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Les voix disponibles varient selon le système d'exploitation et le navigateur
- L'autoplay peut être bloqué par les politiques du navigateur
- La qualité des voix synthétiques varie selon le moteur de synthèse
