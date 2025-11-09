# Désastre Noir

## Description

Le désastre "noir" ajoute un overlay sombre à la page en créant une div `.noir` dans le conteneur `.content`. L'effet visuel est principalement géré par CSS.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "noir" ou "black" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(noir|black).*/i"
  recettes: [noir]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. L'effet visuel est entièrement défini en CSS.

### Exemple de configuration

```yaml
noir:
  enabled: true
  desastre: noir
```

## Fonctionnement

1. **Sélection du conteneur** : Le script cible l'élément `.content`
2. **Création de l'overlay** : Une div avec la classe `.noir` est créée
3. **Ajout au DOM** : La div est ajoutée comme enfant de `.content`
4. **Effet visuel** : Les styles CSS (définis dans `style.css`) s'appliquent automatiquement

## Code commenté

Le script contient du code commenté qui permettrait d'ajouter un second overlay sur le `<body>` :
```javascript
// let newDiv2 = document.createElement('div');
// newDiv2.className = 'overlay-hue';
// body.appendChild(newDiv2);
```

## Bibliothèque

Ce désastre inclut **anime.js v3.2.2** (non utilisé actuellement) dans le fichier `noir.js`, suggérant qu'une animation pourrait être ajoutée dans le futur.

## Fichiers

- `javascript/noir.js` : Script principal + anime.js bundlé
- `javascript/anime.js` : Bibliothèque anime.js (référence)
- `stylesheets/style.css` : Définition des styles de l'overlay noir
- `README.md` : Cette documentation

## Logs de débogage

```javascript
console.log('noir.js loaded');
```

## Compatibilité

Les overlays CSS sont supportés par tous les navigateurs :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- L'effet dépend entièrement du CSS (non fourni dans le code analysé)
- anime.js est chargé mais non utilisé (surcharge inutile)
- Pas d'options de configuration (opacité, couleur, animations)
- Overlay limité à `.content` (ne couvre pas toute la page)
- Le code pour un overlay sur `<body>` est commenté
