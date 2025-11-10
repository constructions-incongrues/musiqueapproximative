# Désastre Splitouine

## Description

Le désastre "splitouine" dompte le texte avec panache en utilisant le plugin [GSAP SplitText](https://gsap.com/docs/v3/Plugins/SplitText/).

Il découpe le texte de la page en caractères, mots ou lignes, puis les anime avec des effets configurables (rotation, opacité, déplacement, rebonds, etc.). L'animation peut être synchronisée avec la durée du morceau audio en cours de lecture.

## Fonctionnement

1. **Découpage du texte** : SplitText transforme le texte sélectionné en éléments animables
2. **Synchronisation audio** : Attend que l'élément audio soit prêt via `DesastreAudio.onReady()`
3. **Animation** : GSAP anime les éléments découpés avec les effets configurés
4. **Durée automatique** : Si aucune durée n'est spécifiée, l'animation utilise la durée du morceau audio

## Dépendances

Ce désastre nécessite les scripts suivants :

```yaml
scripts:
  - /desastres/shared/desastre-audio.js
  - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
  - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js
```

## Configuration

### Structure YAML

```yaml
recettes:
  ma_recette_splitouine:
    enabled: true
    desastre: splitouine
    scripts:
      - /desastres/shared/desastre-audio.js
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js
    options:
      split:
        selector: "p.title, h1, h2"
        configuration:
          type: "words, lines, chars"
          mask: lines
          autoSplit: false
          wordDelimiter: " "
          wordsClass: "word"
      tween:
        type: "chars"  # ou "words" ou "lines"
        configuration:
          opacity: 0
          rotation: 90
          y: 100
          ease: "elastic"
          stagger: 0.05
          # duration: 5  # Optionnel, sinon = durée audio
```

### Options `split`

Configuration du découpage du texte via SplitText.

| Propriété | Type | Description |
|-----------|------|-------------|
| `selector` | string | Sélecteur CSS des éléments à animer |
| `configuration.type` | string | Types d'éléments à créer : `"chars"`, `"words"`, `"lines"` ou combinaison |
| `configuration.mask` | string | Masque pour limiter l'animation : `"chars"`, `"words"`, `"lines"` |
| `configuration.autoSplit` | boolean | Découpage automatique (généralement `false`) |
| `configuration.wordDelimiter` | string | Délimiteur de mots (par défaut `" "`) |
| `configuration.wordsClass` | string | Classe CSS appliquée aux mots créés |

### Options `tween`

Configuration de l'animation GSAP.

| Propriété | Type | Description |
|-----------|------|-------------|
| `type` | string | Type d'éléments à animer : `"chars"`, `"words"`, ou `"lines"` |
| `configuration.opacity` | number | Opacité de départ (0 à 1) |
| `configuration.rotation` | number | Rotation de départ (en degrés) |
| `configuration.x` | number | Décalage horizontal de départ (en pixels) |
| `configuration.y` | number | Décalage vertical de départ (en pixels) |
| `configuration.scale` | number | Échelle de départ (1 = taille normale) |
| `configuration.ease` | string | Fonction d'accélération : `"elastic"`, `"bounce"`, `"power2"`, etc. |
| `configuration.stagger` | number | Délai entre chaque élément (en secondes) |
| `configuration.duration` | number | Durée totale (secondes). Si omis, utilise la durée de l'audio |

## Exemples

### Animation explosive sur les caractères

```yaml
options:
  split:
    selector: "h1.title"
    configuration:
      type: "chars"
  tween:
    type: "chars"
    configuration:
      opacity: 0
      rotation: 360
      scale: 0
      ease: "back.out(1.7)"
      stagger: 0.02
```

### Animation par mots avec rebond

```yaml
options:
  split:
    selector: "p.description"
    configuration:
      type: "words"
      wordDelimiter: " "
  tween:
    type: "words"
    configuration:
      y: -100
      opacity: 0
      ease: "bounce.out"
      stagger: 0.1
      duration: 3
```

### Animation synchronisée avec l'audio

```yaml
options:
  split:
    selector: ".lyrics"
    configuration:
      type: "words, chars"
  tween:
    type: "chars"
    configuration:
      rotation: 90
      x: -50
      opacity: 0
      ease: "elastic.out(1, 0.3)"
      stagger: 0.01
      # Pas de duration = synchronisation automatique avec l'audio
```

## Logs console

Le désastre affiche plusieurs logs pour faciliter le débogage :

```
[desastres/splitouine] Loaded
[desastres/splitouine] Text split complete: {chars: 42, words: 8, lines: 2}
[desastres/splitouine] Auto-matched duration to audio: 234.5s
[desastres/splitouine] Animation started on: chars
```

## Références

- [GSAP Documentation](https://gsap.com/docs/v3/)
- [SplitText Plugin](https://gsap.com/docs/v3/Plugins/SplitText/)
- [GSAP Easing Visualizer](https://gsap.com/docs/v3/Eases)
- [Recette actuelle](../../apps/frontend/config/desastres/recettes/splitouine.yml)

## Notes techniques

- L'animation utilise `gsap.from()`, ce qui signifie que les valeurs configurées sont l'**état de départ**, le texte revient ensuite à son état normal
- Si vous voulez l'inverse (partir de l'état normal vers un état final), modifiez le code pour utiliser `gsap.to()` au lieu de `gsap.from()`
- Le découpage du texte crée des éléments DOM supplémentaires, ce qui peut impacter les performances sur de très longs textes
- SplitText nécessite une licence GSAP Club GreenSock pour un usage commercial
