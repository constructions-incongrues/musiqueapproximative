# Désastre TTS (Text-to-Speech)

## Description

Le désastre TTS lit automatiquement le texte d'un élément de la page en utilisant l'API Web Speech Synthesis du navigateur.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "TTS" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(TTS).*/"
  recettes: [ttsrapper]
  probability: 1.0
```

## Configuration

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `selector` | string | `'div.descriptif p'` | Sélecteur CSS de l'élément dont le texte sera lu |
| `lang` | string | Aléatoire (`fr-FR`, `fr-CA`, `fr-BE`, `fr-CH`) | Code de langue BCP 47 |
| `rate` | number | Aléatoire (0.5-2.0) | Vitesse de lecture (0.1 à 10) |
| `pitch` | number | Aléatoire (0-2.0) | Hauteur de la voix (0 à 2) |
| `volume` | number | Aléatoire (0.3-1.0) | Volume (0 à 1) |
| `voices` | array | - | Liste de noms de voix parmi lesquelles choisir aléatoirement |

### Exemple de configuration

```yaml
ttsrapper:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
    lang: en-US
    rate: 1.0
    pitch: 1.0
    volume: 0.8
    voices:
      - "Google français"
      - "Thomas"
      - "Amélie"
```

### Configuration minimale

Sans options spécifiques, le désastre utilisera :
- Sélecteur par défaut : `div.descriptif p`
- Langue aléatoire parmi les variantes françaises
- Rate, pitch et volume aléatoires

```yaml
ttsrapper:
  enabled: true
  desastre: tts
  options:
    selector: div.descriptif p
```

## Fonctionnement

1. **Chargement** : Le script vérifie la présence des options TTS dans `window.DesastreOptions.tts`
2. **Sélection du texte** : Récupère le texte de l'élément correspondant au sélecteur
3. **Configuration** : Configure les paramètres de synthèse vocale (lang, rate, pitch, volume, voice)
4. **Sélection de la voix** : Si une liste de voix est fournie, en choisit une aléatoirement
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
