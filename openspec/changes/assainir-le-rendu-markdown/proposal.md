## Why

Le premier rendu Markdown de chaque processus PHP émettait un avertissement
`E_DEPRECATED`. L'environnement de test déclare `error_reporting: (E_ALL | E_STRICT) ^
E_NOTICE`, qui laisse passer `E_DEPRECATED` : l'avertissement atterrissait **dans le corps
de la réponse**, avant l'accolade ouvrante, et le JSON cessait d'être analysable.

Ce n'était pas un désagrément de test. C'est ce qui rendait erratiques les sondes de
plusieurs changes de cette session, selon l'ordre des requêtes — un défaut qui se déplace
est plus coûteux qu'un défaut qui se voit. Deux fichiers de test portaient une **requête de
chauffe** pour l'absorber, nommée comme telle et documentée comme un contournement.

**Le plan avait diagnostiqué le mauvais coupable.** Il désignait `$matches[2]{0}` à la
ligne 910, syntaxe d'accès de chaîne en accolades. La mesure désigne autre chose :

```
Deprecated: Methods with the same name as their class will not be constructors
in a future version of PHP; Markdown_Parser has a deprecated constructor
in markdown.php on line 199
```

C'est le constructeur nommé comme sa classe, à la mode PHP 4. Les deux syntaxes sont
**supprimées en PHP 8** ; seule la seconde émettait.

## What Changes

- `function Markdown_Parser()` devient `function __construct()`. C'est la correction qui
  fait taire l'avertissement.
- Les cinq accès de chaîne en accolades deviennent des crochets. Ils n'émettaient rien ici,
  mais la syntaxe est supprimée en PHP 8 tout autant, et la laisser aurait fait croire le
  verrou levé alors qu'il ne l'aurait été qu'à moitié.
- Les **deux requêtes de chauffe** sont retirées de `representationJsonTest` et
  `unicodeTest`, avec les commentaires qui les justifiaient.
- L'en-tête de la bibliothèque porte la modification locale, son motif et sa vérification.

## Hors périmètre

- **La montée en PHP 8.** Ce change lève un verrou, il ne franchit pas la porte. Un autre
  reste ouvert et il est nommé ci-dessous.
- **`error_reporting` de l'environnement de test.** Masquer `E_DEPRECATED` aurait fait taire
  le symptôme en gardant la cause — et le prochain code déprécié serait passé inaperçu.
  Le réglage reste tel quel, exprès.
- **Mettre à jour PHP-Markdown.** La bibliothèque est versionnée depuis longtemps et son
  remplacement est un travail à part, avec son propre risque de rendu.
- **`src/vendor/james-heinrich/getid3`**, qui porte la même syntaxe supprimée. Il est tiré
  par Composer et gitignoré : ce n'est pas un fichier de ce dépôt. C'est un second verrou
  PHP 8, distinct, et il est nommé ici pour ne pas être découvert le jour de la montée.
