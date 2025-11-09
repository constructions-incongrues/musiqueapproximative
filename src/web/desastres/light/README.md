# Désastre Light

## Description

Le désastre "light" crée un effet de rayons lumineux traversant la page. Trois éléments lumineux animés se déplacent horizontalement de gauche à droite avec une rotation de 45°, créant un effet de projecteur ou de rayons de soleil.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "light" ou "lumiere" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(light|lumiere).*/i"
  recettes: [light]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. Les animations sont définies en dur dans le code JavaScript.

### Exemple de configuration

```yaml
light:
  enabled: true
  desastre: light
```

## Fonctionnement

1. **Sélection du conteneur** : Le script cible l'élément `.content`
2. **Création de 3 rayons** : Trois div avec la classe `.light` sont créées
3. **Ajout au DOM** : Les divs sont ajoutées comme enfants de `.content`
4. **Animation** : Chaque rayon est animé avec des paramètres légèrement différents

## Animation

### Paramètres
- **Transformation** :
  - Départ : `translateX(-100%) rotate(45deg)`, opacity 0.5
  - Arrivée : `translateX(800%)`, opacity 0
- **Durée** : 4000ms
- **Répétition** : infinie
- **Délai** : Aléatoire pour chaque rayon (10 + index * Math.random())
- **Fill** : forwards
- **Easing** : ease-in-out

### Randomisation
Chaque rayon a un délai légèrement différent grâce à `index * Math.random()`, créant un effet désynchronisé et plus naturel.

## Bibliothèque

Ce désastre inclut **anime.js v3.2.2** (référencé) mais utilise l'API native `Element.animate()` au lieu d'anime.js.

## Fichiers

- `javascript/light.js` : Script principal d'animation
- `javascript/anime.js` : Bibliothèque anime.js (non utilisée)
- `stylesheets/style.css` : Styles des rayons lumineux
- `README.md` : Cette documentation

## Compatibilité

L'API `Element.animate()` est supportée par tous les navigateurs modernes :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- anime.js est référencé mais non utilisé
- Les paramètres d'animation ne sont pas configurables (durée, distances, opacité)
- Nombre de rayons fixé à 3
- L'effet dépend des styles CSS (non fournis dans le code analysé)
- Le délai aléatoire est basique (10 + index * random)
- Pas de contrôle sur l'angle de rotation (fixé à 45°)
- Les rayons se déplacent toujours de gauche à droite
