# Désastre "Mange-Lettres"

## Description

Le désastre "Mange-Lettres" supprime aléatoirement certaines lettres du texte affiché sur la page. Il utilise GSAP SplitText pour décomposer le texte en caractères individuels et applique un filtre personnalisable pour "manger" les lettres spécifiées.

## Fonctionnement

1. Au chargement de la page, le script attend le `DOMContentLoaded`
2. GSAP SplitText est enregistré comme plugin
3. Le texte de `.grid-container` est décomposé en caractères individuels
4. Chaque caractère est comparé à la liste des lettres cibles (`target`)
5. Si un caractère correspond à une lettre cible, il est supprimé avec une probabilité définie par `rate`
6. Le texte filtré est affiché à la place du texte original

## Dépendances

Ce désastre nécessite les bibliothèques GSAP suivantes :

- **GSAP Core** : https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js
- **SplitText Plugin** : https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js

Ces dépendances doivent être déclarées dans la section `scripts` de la recette (voir Configuration).

## Configuration

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `target` | string | (requis) | Chaîne de caractères contenant les lettres à supprimer (ex: "aeiouy" pour les voyelles) |
| `rate` | float | 1.0 | Probabilité de suppression (0.0 = jamais, 1.0 = toujours). Permet un échantillonnage aléatoire |
| `case` | string | 'insensitive' | Sensibilité à la casse : 'sensitive' ou 'insensitive' |

### Exemple de configuration dans `desastres.yml`

```yaml
# Règle : déclencher le désastre pour un artiste spécifique
regles:
  - query: "query.artist ~ /.*(kubin).*/i"
    recettes: [voyelliste]
    probability: 1.0

# Recettes
recettes:
  # L'infâme Consonnard - Supprime les voyelles
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

  # Le Voyelliste sournois - Supprime les consonnes
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
```

## Recettes pré-configurées

### Consonnard

Supprime les voyelles (a, e, i, o, u, y) avec un taux de 80%.

**Effet** : Le texte devient difficile à lire, mais les consonnes permettent encore de deviner les mots.

**Exemple** :
- Original : "Musique Approximative"
- Résultat : "Msq pprxmtv" (environ 80% des voyelles supprimées)

### Voyelliste

Supprime les consonnes avec un taux de 50%.

**Effet** : Le texte devient très fragmenté, seules les voyelles restent visibles de manière aléatoire.

**Exemple** :
- Original : "Musique Approximative"
- Résultat : "uiue oiae" (environ 50% des consonnes supprimées)

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

## Logs de débogage

Le script génère des logs dans la console pour faciliter le débogage :

```
[desastres/mangelettres] Loaded
[desastres/mangelettres] Original: "Musique Approximative" -> Filtered: "Msq pprxmtv" (rate: 0.8, case: insensitive)
```

Les logs indiquent :
- Le texte original avant filtrage
- Le texte après filtrage
- Le taux de suppression utilisé (`rate`)
- Le mode de sensibilité à la casse utilisé

## Sélecteur cible

Le désastre s'applique sur l'élément `.grid-container`. Si vous souhaitez cibler un autre élément, modifiez le sélecteur dans le fichier `mangelettre.js` :

```javascript
let split = SplitText.create('.votre-selecteur', {
    // ...
});
```

## Notes techniques

- Le filtrage est effectué **avant** la création des éléments DOM pour chaque caractère
- La fonction `prepareText` est appelée par SplitText pour chaque bloc de texte
- Les caractères non présents dans `target` sont toujours conservés
- Les espaces et la ponctuation sont préservés (sauf s'ils sont explicitement dans `target`)
- L'algorithme utilise `Math.random()` pour l'échantillonnage aléatoire

## Cas d'usage

- **Effet artistique** : Créer un texte partiellement illisible pour un effet esthétique
- **Jeu** : Challenge de lecture avec des lettres manquantes
- **Humour** : Simuler une écriture dégradée ou "mangée"
- **Performance artistique** : Associer à un artiste ou un titre spécifique pour créer une expérience unique

## Compatibilité

- Nécessite GSAP 3.13.0 ou supérieur
- Compatible avec tous les navigateurs modernes supportant ES6
- Nécessite JavaScript activé
