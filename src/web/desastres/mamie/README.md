# Désastre Mamie

## Description

Le désastre "mamie" joue un jingle audio provenant du site "Allô c'est Mamie" avec une vitesse de lecture aléatoire.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "phone", "téléphone" ou "allo" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(phone|t[eé]l[eé]phone|allo).*/i"
  recettes: [mamie]
  probability: 0.5
```

## Configuration

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `volume` | number | `0.5` | Volume de lecture (0 à 1) |

### Exemple de configuration

```yaml
mamie:
  enabled: true
  desastre: mamie
  options:
    volume: 0.5
```

## Fonctionnement

1. **Chargement** : Le script vérifie la présence des options dans `window.DesastreOptions.mamie`
2. **Création de l'audio** : Crée un élément Audio avec l'URL du jingle
3. **Configuration** :
   - Volume configuré ou par défaut
   - Vitesse de lecture aléatoire entre 0.5x et 1.5x
4. **Lecture automatique** : Tente de lire automatiquement l'audio
5. **Fallback** : Si l'autoplay est bloqué, attend une interaction utilisateur

## Audio

- **Source** : `https://allocestmamie.partouze-cagoule.fr/assets/audio/jingle.mp3`
- **Vitesse** : Aléatoire entre 0.5x et 1.5x pour chaque déclenchement
- **Volume** : Configurable via les options (défaut: 0.5)

## Autoplay et restrictions navigateurs

Comme pour le désastre TTS, les navigateurs modernes bloquent l'autoplay audio. Le désastre gère ce cas en :
- Tentant d'abord la lecture automatique
- Enregistrant des listeners sur les interactions utilisateur si l'autoplay échoue
- Lançant la lecture dès la première interaction (click, keydown, touchstart)

## Logs de débogage

Le désastre génère des logs détaillés dans la console :
- `[desastres/mamie] Loaded` : Chargement du script
- `[desastres/mamie] Options loaded:` : Options chargées
- `[desastres/mamie] Creating audio from:` : URL de l'audio
- `[desastres/mamie] Audio settings:` : Volume et vitesse de lecture
- `[desastres/mamie] Audio loading...` : Début du chargement
- `[desastres/mamie] Audio loaded` : Audio chargé
- `[desastres/mamie] ▶ Audio playing automatically` : Lecture automatique
- `[desastres/mamie] 👆 User interaction detected` : Interaction détectée
- `[desastres/mamie] ■ Audio ENDED` : Fin de la lecture
- `[desastres/mamie] ✖ Audio/Play ERROR:` : Erreur

## Fichiers

- `javascript/main.js` : Script principal de lecture audio
- `README.md` : Cette documentation

## Compatibilité

L'API Audio HTML5 est supportée par tous les navigateurs modernes :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- L'autoplay peut être bloqué par les politiques du navigateur
- La vitesse de lecture est aléatoire et ne peut pas être configurée
- Le jingle doit être accessible depuis l'URL externe
