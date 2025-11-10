# Désastre Musique

## Description

Le désastre "musique" est le plus complexe du système. Il retire toutes les lettres "M" et "m" du texte de la page, puis les fait défiler sur une portée musicale ondulée en SVG. Les lettres suivent un chemin sinusoïdal et sont animées avec anime.js, créant un effet visuel musical.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "musique" ou "music" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(musique|music).*/i"
  recettes: [musique]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. Tous les paramètres d'animation sont définis en dur dans le code JavaScript.

### Exemple de configuration

```yaml
musique:
  enabled: true
  desastre: musique
```

## Fonctionnement

### Phase 1 : Extraction des lettres M
1. **TreeWalker** : Parcourt tous les nœuds texte du DOM
2. **Filtrage** : Exclut les éléments `<script>` et `<style>`
3. **Extraction** : Retire tous les "M" et "m" du texte
4. **Réduction progressive** : Conserve environ 1/8 des lettres trouvées (3 réductions successives)
5. **Stockage** : Les lettres retenues sont stockées dans `window.musiqueLettresM`

### Phase 2 : Création de la scène
1. **Conteneur** : Création d'un stage `#musique-lettres-stage` (100px de hauteur)
2. **SVG Path** : Portée ondulée définie par un path complexe
3. **Layer** : Couche dédiée pour les lettres animées

### Phase 3 : Animation
1. **anime.path()** : Utilise le path SVG comme trajectoire
2. **Création des lettres** : Chaque lettre devient un `<span>` animé
3. **Animation** :
   - Translation X/Y le long du path
   - Rotation selon l'angle du path
   - Durée : 8000ms
   - Délai : 250ms entre chaque lettre
   - Boucle infinie

## Path SVG

Le path de la portée musicale est une onde sinusoïdale complexe :
```
M0,2c46.43,0,46.43,94,92.86,94S139.28,2,185.71,2s46.43,94,92.86,94...
```
- **ViewBox** : 0 0 1300 98
- **Stroke** : rgba(0,0,0,0.1)
- **Amplitude** : ~94px
- **Longueur** : 1300px

## Éléments visuels

### Stage
- Position : absolute, top 100px
- Taille : 100% width, 100px height
- Pointer-events : none

### Lettres
- Taille : 32px
- Font-weight : 700
- Color : #000
- Position initiale : -16px, -16px
- Transform-origin : center

### Rangée et tempo (CSS)
Le script crée également des divs `.rangee` et `.tempo` avec des `.ligne` et `.mesure` pour un effet de partition musicale (3 rangées de 5 lignes + 4 mesures).

## Bibliothèque

Ce désastre utilise **anime.js v3.2.2** (bundlé dans `musique.js`) pour :
- Animer les lettres le long du path SVG (`anime.path()`)
- Gérer les rotations et translations
- Synchroniser les délais

## Variables globales

```javascript
window.musiqueLettresM = [...];  // Lettres retenues
window.musiqueAnimeInstances = [...];  // Instances d'animation
```

## Fichiers

- `javascript/musique.js` : Script principal + logique complexe
- `javascript/animejs.js` : Bibliothèque anime.js bundlée
- `stylesheets/style.css` : Styles de la portée et des lettres
- `README.md` : Cette documentation

## Logs de débogage

```javascript
console.log("Lettres 'M' retenues pour l'animation :", lettresM);
console.warn("anime.js n'est pas disponible pour animer les lettres retirées.");
```

## Compatibilité

- **TreeWalker** : Supporté par tous les navigateurs modernes
- **SVG Path** : Compatible tous navigateurs
- **anime.js** : Compatible tous navigateurs
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Retire TOUTES les lettres M/m du texte (peut affecter la lisibilité)
- L'algorithme de réduction est agressif (garde seulement ~12.5% des lettres)
- Le path SVG est codé en dur (non configurable)
- Pas de gestion du responsive (viewBox fixe)
- Les lettres ne sont pas remises dans le texte après l'animation
- La modification du DOM peut casser des fonctionnalités JavaScript
- Stage positionné à 100px du haut (peut chevaucher du contenu)
- Les paramètres d'animation ne sont pas configurables
