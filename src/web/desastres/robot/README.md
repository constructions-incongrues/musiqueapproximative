# Désastre Robot

## Description

Le désastre "robot" transforme chaque occurrence du mot "robot" dans le titre de l'émission en lettres animées en 3D. Les lettres effectuent des rotations alternées sur les axes X et Y, créant un effet mécanique et robotique.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "robot" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*robot.*/i"
  recettes: [robot]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. Les animations 3D sont définies en dur dans le code JavaScript.

### Exemple de configuration

```yaml
robot:
  enabled: true
  desastre: robot
```

## Fonctionnement

1. **Sélection du titre** : Le script cible l'élément `article h2`
2. **Transformation du texte** : Chaque occurrence de "robot" (case insensitive) est décomposée en lettres individuelles
3. **Wrapping** : Chaque lettre est enveloppée dans deux `<span>` :
   ```html
   <span class="letter-wrapper">
     <span class="letter">R</span>
   </span>
   ```
4. **Animation 3D** :
   - **Lettres paires** (index 0, 2, 4...) : Rotation horizontale (Y-axis)
   - **Lettres impaires** (index 1, 3, 5...) : Rotation verticale (X-axis) avec délai de 2s
5. **Effet de perspective** : Utilisation de `perspective(40cm)` pour l'effet 3D

## Animations

### Lettres paires (horizontales)
- **Transformation** :
  - Départ : `translateX(-10px) rotateY(-60deg)`
  - Milieu : `translateX(20-30px) rotateY(60deg)`
  - Retour invisible : opacity 0
- **Durée** : 5000ms
- **Répétition** : infinie
- **Démarrage** : Immédiat (window.onload)

### Lettres impaires (verticales)
- **Transformation** :
  - Départ : `translateY(-50px) rotateX(-60deg)`
  - Milieu : `translateY(30-50px) rotateX(60deg)`
  - Retour invisible : opacity 0
- **Durée** : 5000ms
- **Répétition** : infinie
- **Démarrage** : Différé de 2000ms

## Styles appliqués

```css
.letter-wrapper {
  display: inline-block;
  overflow: hidden;
}

.letter {
  display: block | inline-block;
  perspective: 40cm;
  font-weight: 700;
  line-height: 1;
}
```

## Fichiers

- `javascript/robot.js` : Script principal d'animation 3D
- `stylesheets/style.css` : Styles des lettres
- `README.md` : Cette documentation

## Compatibilité

Les animations 3D CSS (rotateX, rotateY, perspective) sont supportées par tous les navigateurs modernes :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Ne cible que les occurrences de "robot" dans `article h2`
- Les animations ne sont pas configurables (durée, angles, délais fixes)
- Peut affecter la lisibilité du titre pendant l'animation
- Utilise l'API `Element.animate()` (pas de fallback pour anciens navigateurs)
- Le code contient des lignes commentées (boxShadow, backgroundColor) non utilisées
