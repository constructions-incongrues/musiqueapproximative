# Désastre Mort

## Description

Le désastre "mort" transforme le bas de la page en cimetière en affichant progressivement 20 pierres tombales positionnées aléatoirement sur une grille. Les tombes apparaissent une par une avec un délai de 800ms entre chaque, créant un effet de "peuplement" du cimetière.

## Déclenchement

Le désastre est déclenché lorsque le titre de l'émission contient "mort", "morte", "morts", "death", "deaths" ou "dead" (insensible à la casse).

**Règle :**
```yaml
- query: "query.title ~ /.*(morte?s?|deaths?|dead).*/i"
  recettes: [mort]
  probability: 0.7
```

## Configuration

### Options disponibles

Aucune option configurable pour ce désastre. Le nombre de tombes, leur positionnement et les délais sont définis en dur.

### Exemple de configuration

```yaml
mort:
  enabled: true
  desastre: mort
```

## Fonctionnement

1. **Création du plateau** : Une div `.plateau` est créée en bas de la page (60vh de hauteur)
2. **Génération de positions** :
   - Grille adaptative basée sur le viewport (cellules de 120px)
   - 20 positions aléatoires uniques avec espacement minimal
   - Évite les chevauchements (distance min : 2 cellules)
3. **Création progressive** : Les tombes apparaissent une par une toutes les 800ms
4. **Chargement des images** : Chaque tombe affiche une image `cimetiere1.png` à `cimetiere20.png`

## Positionnement

### Plateau
- **Position** : absolute, bottom: 0
- **Hauteur** : 60vh
- **Largeur** : 100%
- **Z-index** : 1000

### Grille
- **Cellule** : 120x120px (adaptatif selon viewport)
- **Espacement** : Minimum 2 cellules entre tombes
- **Algorithme** : Placement aléatoire avec vérification de distance

### Tombes
- **Taille** : 120x120px fixe
- **Images** : `/desastres/mort/img/cimetiere1.png` à `cimetiere20.png`
- **ID** : `tombe-1` à `tombe-20`

## Gestion des images

Le script gère deux chemins d'images différents :
- Fonction principale : `/desastres/mort/img/cimetiere*.png`
- Fonction alternative : `/desastre/recettes/mort/img/cimetiere*.png`

Gestion des erreurs :
```javascript
image.onerror = function() {
  console.log(`Image cimetiere${index+1}.png non trouvée`);
  this.style.display = 'none';
}
```

## Timing

- **Délai entre tombes** : 800ms
- **Durée totale apparition** : ~16 secondes (20 * 800ms)
- **Tentatives placement** : Max 200 essais par tombe pour éviter chevauchement

## Fonctions disponibles

### creerTombes()
Fonction principale appelée au chargement de la page.

### creerTombesAvecImages(nombre)
Fonction alternative permettant de spécifier le nombre de tombes :
```javascript
creerTombesAvecImages(15);  // Crée 15 tombes au lieu de 20
```

## Fichiers

- `javascript/mort.js` : Script principal de génération du cimetière
- `stylesheets/style.css` : Styles du plateau et des tombes
- `img/cimetiere1.png` à `cimetiere20.png` : Images des pierres tombales
- `README.md` : Cette documentation

## Logs de débogage

```javascript
console.log("Mort");
console.log("20 divs 'tombe' seront créés sur la grille étalée du plateau");
console.log(`Image cimetiere${index+1}.png chargée avec succès`);
console.log(`Image cimetiere${index+1}.png non trouvée`);
```

## Compatibilité

JavaScript vanilla, compatible avec tous les navigateurs modernes :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Nombre de tombes fixé à 20 (sauf utilisation de la fonction alternative)
- Les images doivent être nommées exactement `cimetiere1.png` à `cimetiere20.png`
- Hauteur du plateau fixée à 60vh (non configurable)
- Le plateau peut masquer le contenu du bas de page (z-index: 1000)
- L'algorithme de placement peut échouer si trop de tombes sur petit viewport
- Pas de responsive sur la taille des tombes (120px fixe)
- Deux fonctions similaires dans le code (duplication)
