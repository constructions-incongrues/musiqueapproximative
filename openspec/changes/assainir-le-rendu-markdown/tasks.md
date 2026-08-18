# Tâches

Pas de `specs/` : `skip_specs` est déclaré. Le comportement observable ne change pas — le
JSON était censé être analysable, il l'est. Pas de `design.md`.

## 1. Mesurer avant de corriger, et ne pas croire le diagnostic hérité

- [x] 1.1 Lire le corps réel d'une réponse JSON dans l'environnement de test plutôt que de
  partir du diagnostic du plan.
- [x] 1.2 **Le plan se trompait de coupable.** Il désignait `$matches[2]{0}` ligne 910.
  L'avertissement émis est : « `Markdown_Parser` has a deprecated constructor
  in markdown.php on line 199 ». C'est le constructeur nommé comme sa classe.
- [x] 1.3 Vérifier que la correction supposée n'aurait pas suffi : les cinq accès en
  accolades ont été convertis d'abord, seuls — **la suite échouait encore**, 24 assertions
  sur 625. C'est ce qui a imposé de regarder le corps de la réponse.

## 2. Corriger les deux syntaxes supprimées en PHP 8

- [x] 2.1 `function Markdown_Parser()` devient `function __construct()`.
- [x] 2.2 Les cinq accès `$chaine{0}` deviennent `$chaine[0]`. Ils n'émettaient rien, mais
  la syntaxe disparaît en PHP 8 : les laisser aurait fait croire le verrou levé à moitié.
- [x] 2.3 Vérifier qu'il n'en reste aucun dans du code — la seule occurrence restante est
  dans le commentaire qui explique la modification.
- [x] 2.4 Porter en tête de la bibliothèque la modification locale, son motif et sa
  vérification. Un fichier tiers modifié sans trace redevient un fichier tiers à la
  prochaine mise à jour.

## 3. Retirer les contournements

- [x] 3.1 Retirer la requête de chauffe de `representationJsonTest.php`.
- [x] 3.2 Retirer celle d'`unicodeTest.php`, ainsi que la variable qui ne servait qu'à elle.
- [x] 3.3 Vérifier qu'aucune mention de « chauffe » ne subsiste dans `src/test/`.

## 4. Vérification

- [x] 4.1 Le corps d'une réponse JSON commence par `{` et s'analyse. Mesuré directement,
  pas déduit d'un test qui passe.
- [x] 4.2 **`test:all` : 21 fichiers, 625 tests, tous verts**, sans aucune requête de
  chauffe.
- [x] 4.3 **Le rendu Markdown est inchangé.** Empreinte md5 comparée avant/après sur un
  échantillon couvrant titres soulignés, emphases simples, doubles et triples, entités
  HTML, listes, code, HTML en ligne, citation, lien et image : **identique**.
- [x] 4.4 `php -l` sur les trois fichiers touchés.

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **La montée en PHP 8.** Un second verrou subsiste :
  `src/vendor/james-heinrich/getid3/getid3/getid3.lib.php` porte la même syntaxe supprimée.
  Il est tiré par Composer et gitignoré — ce n'est pas un fichier de ce dépôt, et le
  corriger sur place serait effacé au prochain `composer install`. À traiter par une montée
  de version de la dépendance, le jour de la montée en PHP 8.
- [ ] 5.2 `error_reporting` de l'environnement de test reste inchangé, exprès : masquer
  `E_DEPRECATED` aurait fait taire le symptôme en gardant la cause, et le prochain code
  déprécié serait passé inaperçu.

### Vérification manuelle — après la mise en ligne

- [ ] 5.3 Demander une page de morceau au corps riche en Markdown et vérifier que le rendu
  est celui d'avant. La production n'émettait pas l'avertissement — son
  `error_reporting` diffère — donc c'est le rendu qu'il faut regarder, pas les en-têtes.
