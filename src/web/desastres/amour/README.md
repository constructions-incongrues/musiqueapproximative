# Désastre Amour

## Description

Le désastre "amour" affiche le mot "AMOUR" en grand avec une animation de tracé SVG, accompagné d'un cœur rose animé qui bat en rythme. Les éléments sont superposés sur l'article.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "amour" ou "love" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(amour|love).*/i"
  recettes: [amour]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. Les animations sont définies en dur dans le code JavaScript.

### Exemple de configuration

```yaml
amour:
  enabled: true
  desastre: amour
```

## Fonctionnement

1. **Chargement** : Le script vérifie la présence des options dans `window.DesastreOptions.amour`
2. **Création des éléments SVG** :
   - Div `.amour` : Contient le mot "AMOUR" en tracé SVG
   - Div `.coeur` : Contient un cœur rose en SVG
3. **Positionnement** :
   - Les éléments sont positionnés en absolute sur l'article
   - `.amour` : centré, taille max 1000px
   - `.coeur` : à gauche (10%), taille max 150px
4. **Animations** :
   - **Texte AMOUR** : Animation du tracé SVG avec anime.js (strokeDashoffset)
   - **Cœur** : Animation de battement (scale 1 → 1.1) avec CSS

## Animations

### Animation du texte (anime.js)
- **Effet** : Tracé progressif des lettres (stroke-dashoffset)
- **Durée** : 1500ms
- **Délai** : 250ms entre chaque lettre
- **Direction** : alternate (aller-retour)
- **Répétition** : infinie

### Animation du cœur (CSS)
- **Effet** : Battement (scale)
- **Durée** : 1s
- **Délai** : 1s avant démarrage
- **Répétition** : infinie
- **Easing** : ease-in-out

## Bibliothèque

Ce désastre utilise **anime.js v3.2.2** (bundlé dans `amour.js`) pour animer le tracé du texte SVG.

## Fichiers

- `javascript/amour.js` : Script principal + anime.js bundlé
- `javascript/anime.js` : Bibliothèque anime.js (référence)
- `stylesheets/style.css` : Styles et animation du cœur
- `README.md` : Cette documentation

## Logs de débogage

```javascript
console.log('Coeur');
console.log('Options du desastre amour:', window.DesastreOptions.amour);
```

## Compatibilité

- **SVG et anime.js** : Supporté par tous les navigateurs modernes
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Les SVG sont intégrés en dur dans le code (non configurables)
- Le positionnement est fixe (non responsive)
- L'animation du cœur morphing (code commenté) n'est pas activée
- Les éléments ont un z-index de 10000 (peuvent masquer le contenu)
- Pointer-events: none sur `.amour` (non cliquable)
