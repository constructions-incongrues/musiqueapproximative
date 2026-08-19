## 1. Exposer la date

- [x] 1.1 Ajouter la date de publication du morceau dans la page, sous forme normalisée et
      lisible par une machine. Elle n'y figure aujourd'hui nulle part.
- [x] 1.2 Vérifier qu'elle apparaît sur une page servie, et qu'elle correspond bien à
      `publish_on` en base.

## 2. Calculer l'intensité

- [x] 2.1 Lire la date dans le script du désastre et en déduire l'âge.
- [x] 2.2 Appliquer la courbe : plancher, puis exposant 2 sur l'âge rapporté à la référence.
- [x] 2.3 Poser la référence en constante — dix-huit ans — et non depuis l'étendue du
      catalogue, qui grandit chaque jour et ferait dériver l'usure d'un morceau donné sans
      décision.
- [x] 2.4 Passer par l'`AudioParam` `intensite` déjà exposé par le processeur, plutôt que
      d'ajouter un chemin parallèle.
- [x] 2.5 Retomber sur l'intensité de la recette si la date est absente ou illisible. Un
      désastre est un ornement : une date manquante ne doit pas le faire échouer.

## 3. Vérifier par la mesure

- [x] 3.1 Mesurer l'amplitude obtenue pour au moins trois âges — un morceau récent, un
      morceau médian, un des débuts — et constater qu'elle croît avec l'âge.
- [x] 3.2 Constater que le morceau le plus récent reste au-dessus du seuil de perception.
      Un désastre annoncé par l'en-tête et inaudible se lit comme un désastre cassé.
- [x] 3.3 Vérifier que le contrepoint visuel suit l'intensité. **Cette tâche a trouvé un
      défaut** : le processeur postait le modulateur brut, sans le pondérer. Le titre aurait
      dérivé autant sur un morceau d'hier que sur un de 2008, alors que l'oreille entend
      sept fois moins — l'œil aurait contredit l'oreille au lieu de la confirmer, ce qui est
      exactement ce que les contrepoints existent pour éviter. Corrigé, et vérifié : rapport
      de 6,67× entre les deux, identique à celui de l'audible.

## 4. Vérifier que rien d'autre ne bouge

- [x] 4.1 Le tirage, la règle et la probabilité sont inchangés.
- [x] 4.2 L'invariance et l'en-tête `X-Desastre` sont inchangés.
- [x] 4.3 La suite passe sous PHP 7.4 et 8.1.

## 5. Documenter et clore

- [x] 5.1 Documenter la courbe, ses chiffres sur le catalogue réel, et pourquoi la référence
      est une constante.
- [x] 5.2 Cocher la story 35 dans `openspec/discovery.md` et y déclarer ce change.
