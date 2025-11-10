# Désastre "Mange-Lettres"

## Description

Le désastre "Mange-Lettres" fait disparaître progressivement certaines lettres du texte affiché sur la page. Il utilise GSAP SplitText pour décomposer le texte en caractères individuels et anime la disparition des lettres ciblées.

## Fonctionnement

1. Au chargement de la page, le script attend le `DOMContentLoaded`
2. GSAP SplitText est enregistré comme plugin
3. Le texte de `section.content` est décomposé en caractères individuels
4. Chaque caractère est comparé à la liste des lettres cibles (`target`)
5. Si un caractère correspond à une lettre cible, il est marqué pour disparition avec une probabilité définie par `rate`
6. Les lettres marquées disparaissent progressivement avec une animation GSAP (opacity + scale)

## Dépendances

Ce désastre nécessite les bibliothèques GSAP suivantes :

- **GSAP Core** : https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
- **SplitText Plugin** : https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js

Ces dépendances doivent être déclarées dans la section `scripts` de la recette (voir Configuration).

## Configuration

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `target` | string | (requis) | Chaîne de caractères contenant les lettres à faire disparaître (ex: "aeiouy" pour les voyelles) |
| `rate` | float | 1.0 | Probabilité de disparition (0.0 = jamais, 1.0 = toujours). Permet un échantillonnage aléatoire |
| `case` | string | 'insensitive' | Sensibilité à la casse : 'sensitive' ou 'insensitive' |
| `duration` | float | 2 | Durée de l'animation de disparition en secondes |
| `stagger` | float | 0.02 | Délai entre la disparition de chaque lettre en secondes |
| `onSplit` | object | (optionnel) | Configuration d'une animation GSAP additionnelle à appliquer à tous les caractères. Voir [Animation avec onSplit](#animation-avec-onsplit) |

### Exemple de configuration dans `desastres.yml`

```yaml
# Règle : déclencher le désastre pour un artiste spécifique
regles:
  - query: "query.artist ~ /.*(kubin).*/i"
    recettes: [voyelliste]
    probability: 1.0

# Recettes
recettes:
  # L'infâme Consonnard - Fait disparaître les voyelles progressivement
  consonnard:
    enabled: true
    desastre: mangelettres
    scripts:
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js
    options:
      rate: 0.8
      target: aeiouy
      case: insensitive
      duration: 3
      stagger: 0.05

  # Le Voyelliste sournois - Fait disparaître les consonnes avec animation additionnelle
  voyelliste:
    enabled: true
    desastre: mangelettres
    scripts:
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
      - https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js
    options:
      rate: 0.5
      target: bcdfghjklmnpqrstvwxz
      case: insensitive
      duration: 2
      stagger: 0.03
      onSplit:
        yPercent: 10
        opacity: 1
        stagger: 0.05
        duration: 15
```

## Recettes pré-configurées

### Consonnard

Fait disparaître progressivement les voyelles (a, e, i, o, u, y) avec un taux de 80%.

**Effet** : Les voyelles s'estompent une par une. Le texte devient difficile à lire, mais les consonnes permettent encore de deviner les mots.

**Paramètres** :
- Durée : 3 secondes
- Délai entre lettres : 0.05s (50ms)

**Exemple** :
- Original : "Musique Approximative"
- Résultat final : "Msq pprxmtv" (environ 80% des voyelles disparues)

### Voyelliste

Fait disparaître progressivement les consonnes avec un taux de 50%, accompagné d'une animation verticale.

**Effet** : Les consonnes s'estompent et rétrécissent une par une de manière plus rapide. Le texte devient très fragmenté, seules les voyelles restent visibles de manière aléatoire. Une animation additionnelle fait bouger verticalement tous les caractères.

**Paramètres** :
- Durée de disparition : 2 secondes
- Délai entre lettres : 0.03s (30ms)
- Animation additionnelle : mouvement vertical lent (15s)

**Exemple** :
- Original : "Musique Approximative"
- Résultat final : "uiue oiae" (environ 50% des consonnes disparues)

## Paramètre `rate` : Échantillonnage aléatoire

Le paramètre `rate` permet de contrôler la probabilité de suppression de chaque lettre cible :

- `rate: 0.0` : Aucune lettre n'est supprimée (désastre désactivé)
- `rate: 0.5` : Environ 50% des lettres cibles sont supprimées
- `rate: 0.8` : Environ 80% des lettres cibles sont supprimées
- `rate: 1.0` : Toutes les lettres cibles sont systématiquement supprimées

Chaque caractère est évalué indépendamment, ce qui crée une variabilité naturelle dans le résultat.

## Paramètre `case` : Sensibilité à la casse

Le paramètre `case` définit si la comparaison des caractères est sensible à la casse :

- `case: 'insensitive'` (défaut) : 'A' et 'a' sont considérés identiques
  - Si `target: 'a'`, supprime à la fois 'a' et 'A'

- `case: 'sensitive'` : 'A' et 'a' sont traités différemment
  - Si `target: 'a'`, supprime uniquement 'a' (pas 'A')
  - Si `target: 'aA'`, supprime 'a' et 'A'

**Exemple avec `target: 'a'` et `rate: 1.0`** :
- Original : "La Banane est jaune"
- Avec `case: 'insensitive'` : "L Bnne est june"
- Avec `case: 'sensitive'` : "L Bnne est june" (le 'L' majuscule reste)

## Animation de disparition (automatique)

Les lettres ciblées disparaissent automatiquement avec une animation GSAP qui combine :
- **Opacité** : passe de 1 à 0
- **Timing** : contrôlé par `duration` et `stagger`
- **Easing** : `power2.in` pour une accélération en fin d'animation

Cette animation est appliquée automatiquement, vous n'avez qu'à configurer `duration` et `stagger`.

## Animation additionnelle avec `onSplit`

Le paramètre `onSplit` (optionnel) permet d'appliquer une animation GSAP **additionnelle** à **tous** les caractères (pas seulement ceux qui disparaissent). Il utilise `gsap.from()` pour animer les caractères depuis un état initial vers leur état final.

### Structure de l'option `onSplit`

L'option `onSplit` accepte n'importe quelle propriété valide de GSAP :

```yaml
options:
  onSplit:
    # Propriétés de transformation
    yPercent: 10        # Décalage vertical (en pourcentage de la hauteur)
    xPercent: 0         # Décalage horizontal (en pourcentage de la largeur)
    rotation: 0         # Rotation en degrés
    scale: 1            # Échelle (1 = taille normale)

    # Propriétés d'apparence
    opacity: 1          # Opacité (0 = transparent, 1 = opaque)

    # Propriétés de timing
    duration: 15        # Durée de l'animation en secondes
    stagger: 0.05       # Délai entre chaque caractère (en secondes)
    ease: "power1.out"  # Fonction d'easing (optionnel)
    delay: 0            # Délai avant le début (optionnel)
```

### Exemples d'animations

**Apparition progressive depuis le bas** :
```yaml
onSplit:
  yPercent: 100
  opacity: 0
  stagger: 0.03
  duration: 1
```

**Effet de rotation** :
```yaml
onSplit:
  rotation: 360
  scale: 0
  stagger: 0.05
  duration: 2
  ease: "elastic.out(1, 0.3)"
```

**Effet de fondu simple** :
```yaml
onSplit:
  opacity: 0
  stagger: 0.02
  duration: 0.8
```

### Comportement

- Si `onSplit` n'est pas spécifié, aucune animation additionnelle n'est appliquée (seule l'animation de disparition est visible)
- L'animation s'applique à **tous** les caractères (ceux qui restent ET ceux qui vont disparaître)
- L'animation démarre dès que le texte est divisé en caractères
- Le log `[desastres/mangelettres] Applying additional onSplit animation` apparaît dans la console si une animation est configurée

### Propriétés GSAP courantes

Pour plus de détails sur les propriétés GSAP disponibles, consultez la [documentation GSAP](https://greensock.com/docs/v3/GSAP/gsap.from()).

Propriétés les plus utilisées :
- **x, y** : Position en pixels
- **xPercent, yPercent** : Position en pourcentage
- **rotation, rotationX, rotationY** : Rotation 2D et 3D
- **scale, scaleX, scaleY** : Échelle
- **opacity** : Opacité
- **duration** : Durée de l'animation
- **stagger** : Délai entre les éléments
- **ease** : Fonction d'accélération (ex: "power1.out", "elastic", "bounce")

## Logs de débogage

Le script génère des logs dans la console pour faciliter le débogage :

```
[desastres/mangelettres] Loaded
[desastres/mangelettres] Analyzing 234 characters
[desastres/mangelettres] Will remove 45 characters progressively
[desastres/mangelettres] Applying additional onSplit animation
[desastres/mangelettres] Disappearance complete
[desastres/mangelettres] SplitText initialized with progressive disappearance
```

Les logs indiquent :
- Le chargement du script
- Le nombre total de caractères analysés
- Le nombre de caractères qui vont disparaître
- Si une animation `onSplit` additionnelle est appliquée (log optionnel)
- La fin de l'animation de disparition
- L'initialisation complète

## Sélecteur cible

Le désastre s'applique sur l'élément `section.content`. Si vous souhaitez cibler un autre élément, modifiez le sélecteur dans le fichier `mangelettre.js` :

```javascript
SplitText.create('.votre-selecteur', {
    // ...
});
```

## Notes techniques

- Toutes les lettres sont affichées initialement, puis celles ciblées disparaissent avec une animation
- L'animation de disparition utilise `gsap.to()` avec `opacity: 0` et `scale: 0`
- Les caractères non présents dans `target` ne sont jamais affectés
- Les espaces et la ponctuation sont préservés (sauf s'ils sont explicitement dans `target`)
- L'algorithme utilise `Math.random()` pour l'échantillonnage aléatoire
- Le `stagger` crée un effet de cascade : chaque lettre commence à disparaître avec un délai par rapport à la précédente

## Cas d'usage

- **Effet artistique** : Créer un texte partiellement illisible pour un effet esthétique
- **Jeu** : Challenge de lecture avec des lettres manquantes
- **Humour** : Simuler une écriture dégradée ou "mangée"
- **Performance artistique** : Associer à un artiste ou un titre spécifique pour créer une expérience unique

## Compatibilité

- Nécessite GSAP 3.13.0 ou supérieur
- Compatible avec tous les navigateurs modernes supportant ES6
- Nécessite JavaScript activé
