# Désastre Fish

## Description

Le désastre "fish" affiche un poisson animé (via Lottie) qui suit le curseur de la souris sur toute la page. Le poisson est légèrement décalé verticalement pour un meilleur rendu visuel.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "fish" ou "poisson" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(fish|poisson).*/i"
  recettes: [fish]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. L'animation Lottie et le comportement de suivi sont définis en dur.

### Exemple de configuration

```yaml
fish:
  enabled: true
  desastre: fish
```

## Fonctionnement

1. **Création de l'élément** : Une div `.fish` est créée et ajoutée au `<body>`
2. **Intégration Lottie** : Un iframe Lottie est intégré depuis `lottie.host`
3. **Positionnement** :
   - Position absolute
   - Taille : 100x100px
4. **Suivi du curseur** : Listener `mousemove` met à jour la position du poisson
5. **Décalage vertical** : Le poisson est placé 20px au-dessus du curseur (`y - 20`)

## Animation Lottie

- **Source** : `https://lottie.host/embed/fd9ece5c-3319-49ea-a84a-a42eaba41839/77Hxq4ZgJ4.lottie`
- **Format** : iframe Lottie (animation vectorielle)
- **Taille** : 100x100px

## Logs de débogage

Le script génère un log console à chaque mouvement de souris :
```javascript
console.log(`Position du curseur: X=${x}, Y=${y}`);
```

**Attention** : Ce log peut générer beaucoup de messages dans la console lors des mouvements de souris.

## Fichiers

- `javascript/fish.js` : Script principal de suivi du curseur
- `stylesheets/style.css` : Styles du poisson (si présents)
- `README.md` : Cette documentation

## Compatibilité

- **Lottie** : Supporté par tous les navigateurs modernes via iframe
- **MouseMove Event** : Compatible tous navigateurs
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- L'animation dépend de la disponibilité du service externe `lottie.host`
- Le poisson ne s'adapte pas à l'orientation/direction du curseur
- Logs console activés en permanence (performance)
- Taille fixe de 100x100px (non responsive)
- Pas de gestion tactile (touchmove) pour mobile
- Le poisson peut sortir des limites de la fenêtre
