# Plages aléatoires pour rate, pitch et volume

## Description

Les options `rate`, `pitch` et `volume` du désastre TTS supportent maintenant **deux modes** :

1. **Valeur fixe** (nombre) : La valeur est utilisée telle quelle
2. **Plage aléatoire** (tableau) : Une valeur aléatoire est tirée entre min et max

## Syntaxe

### Valeur fixe (nombre)

```yaml
options:
  rate: 1.0      # Toujours 1.0
  pitch: 1.5     # Toujours 1.5
  volume: 0.8    # Toujours 0.8
```

### Plage aléatoire (tableau)

```yaml
options:
  rate: [0.5, 2.0]      # Aléatoire entre 0.5 et 2.0
  pitch: [0.8, 1.2]     # Aléatoire entre 0.8 et 1.2
  volume: [0.6, 1.0]    # Aléatoire entre 0.6 et 1.0
```

**Note** : Le tableau doit contenir au moins 2 éléments. Si le tableau contient plus de 2 éléments, seuls le **premier** et le **dernier** sont utilisés comme bornes.

```yaml
# Ces trois configurations sont équivalentes :
rate: [0.5, 2.0]
rate: [0.5, 1.0, 1.5, 2.0]
rate: [0.5, 999, 888, 777, 2.0]  # Seuls 0.5 et 2.0 sont utilisés
```

## Plages par défaut

Si une option n'est pas définie, les plages par défaut suivantes sont utilisées :

| Option | Plage par défaut |
|--------|------------------|
| `rate` | `[0.5, 2.0]` |
| `pitch` | `[0, 2.0]` |
| `volume` | `[0.3, 1.0]` |

## Exemples d'usage

### Robot avec voix variable

```yaml
tts_robot:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [0.8, 1.2]    # Légère variation de vitesse
    pitch: [0.5, 0.8]   # Voix grave de robot
    volume: 1.0         # Volume constant max
```

**Résultat** : À chaque lecture, une nouvelle vitesse et hauteur sont générées aléatoirement, mais le volume reste constant.

### Chipmunk (voix d'écureuil)

```yaml
tts_chipmunk:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [1.5, 2.0]    # Rapide
    pitch: [1.8, 2.0]   # Très aigu
    volume: [0.7, 0.9]  # Volume modéré
```

### Slow-mo dramatique

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

### Voix naturelle avec légères variations

```yaml
tts_natural:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [0.9, 1.1]    # Presque normale avec légère variation
    pitch: [0.9, 1.1]   # Presque normale avec légère variation
    volume: 0.85        # Volume fixe
```

### Configuration chaotique

```yaml
tts_chaos:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    rate: [0.5, 2.0]    # Pleine plage
    pitch: [0, 2.0]     # Pleine plage
    volume: [0.3, 1.0]  # Pleine plage
```

**Note** : C'est équivalent à ne rien configurer (valeurs par défaut).

## Implémentation technique

### Fonction JavaScript

```javascript
function getValueOrRandom(configValue, defaultMin, defaultMax) {
  if (configValue === undefined) {
    // Pas de config : utiliser les valeurs par défaut
    return defaultMin + Math.random() * (defaultMax - defaultMin);
  } else if (Array.isArray(configValue) && configValue.length >= 2) {
    // Config est un tableau [min, max] : valeur aléatoire dans cette plage
    const min = configValue[0];
    const max = configValue[configValue.length - 1];
    return min + Math.random() * (max - min);
  } else if (typeof configValue === 'number') {
    // Config est un nombre : utiliser cette valeur exacte
    return configValue;
  } else {
    // Config invalide : utiliser les valeurs par défaut
    console.warn("[desastres/tts] WARNING: Invalid config value, using default random range");
    return defaultMin + Math.random() * (defaultMax - defaultMin);
  }
}

// Utilisation
utterance.rate = getValueOrRandom(window.DesastreOptions.tts.rate, 0.5, 2);
utterance.pitch = getValueOrRandom(window.DesastreOptions.tts.pitch, 0, 2);
utterance.volume = getValueOrRandom(window.DesastreOptions.tts.volume, 0.3, 1);
```

### Logs console

Le système log automatiquement le mode utilisé :

```javascript
// Valeur fixe configurée
[desastres/tts] Using configured rate: 1.5

// Plage aléatoire configurée
[desastres/tts] Using random rate from range [0.5, 2] : 1.23

// Plage par défaut (pas de config)
[desastres/tts] Using default random rate: 1.42
```

## Limites des valeurs

### Rate (vitesse)
- **API Web Speech** : 0.1 à 10
- **Recommandé** : 0.5 à 2.0
- **Naturel** : 0.8 à 1.2

### Pitch (hauteur)
- **API Web Speech** : 0 à 2
- **Recommandé** : 0.5 à 1.5
- **Naturel** : 0.9 à 1.1

### Volume
- **API Web Speech** : 0 à 1
- **Recommandé** : 0.3 à 1.0
- **Naturel** : 0.7 à 0.9

## Cas d'usage créatifs

### Progression dramatique

Créer plusieurs désastres qui se déclenchent selon le contexte :

```yaml
# Intro calme
tts_intro:
  enabled: true
  desastre: tts
  options:
    rate: [0.8, 1.0]
    pitch: [0.9, 1.1]
    volume: [0.5, 0.7]

# Action intense
tts_action:
  enabled: true
  desastre: tts
  options:
    rate: [1.5, 2.0]
    pitch: [1.2, 1.8]
    volume: [0.8, 1.0]
```

### Effets spéciaux selon l'heure

```yaml
# Nuit : voix grave et lente
tts_night:
  enabled: true
  desastre: tts
  options:
    rate: [0.6, 0.9]
    pitch: [0.4, 0.7]
    volume: [0.4, 0.6]

# Matin : voix énergique
tts_morning:
  enabled: true
  desastre: tts
  options:
    rate: [1.1, 1.4]
    pitch: [1.1, 1.4]
    volume: [0.7, 0.9]
```

### Selon l'artiste

```yaml
# Kraftwerk : voix robotique
tts_kraftwerk:
  enabled: true
  desastre: tts
  options:
    rate: [0.9, 1.1]
    pitch: [0.5, 0.7]
    volume: 0.9

# Punk : voix rapide et énergique
tts_punk:
  enabled: true
  desastre: tts
  options:
    rate: [1.4, 1.8]
    pitch: [1.2, 1.6]
    volume: 1.0
```

## Compatibilité

✅ Compatible avec tous les navigateurs supportant Web Speech API
✅ Rétrocompatible : Les configurations existantes avec nombres fonctionnent toujours
✅ Fallback automatique sur les plages par défaut en cas de configuration invalide

## Test rapide

Pour tester différentes configurations, utilisez le trigger dans l'URL :

```
# Voir README-TRIGGER.md dans le plugin sfDesastrePlugin
https://musiqueapproximative.net/post/123?jinglist
```

Puis modifiez la configuration dans `desastres.yml` et rechargez la page avec le trigger.

## Voir aussi

- [README.md](./README.md) - Documentation complète du désastre TTS
- [N8N-WORKFLOW-NO-CODE.md](./N8N-WORKFLOW-NO-CODE.md) - Workflow n8n pour jingles contextuels
- [README-TRIGGER.md](../../plugins/sfDesastrePlugin/README-TRIGGER.md) - Système de triggers par URL

---

**Créé avec ❤️ pour Musique Approximative**
