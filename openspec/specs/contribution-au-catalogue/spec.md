# Spécification : contribution-au-catalogue

## Purpose

Décrit ce que le site doit au contributeur qui alimente le catalogue — celui sans qui il n'y
a pas de morceau du jour. `catalogue-morceaux` dit ce qu'un visiteur y trouve ; cette
capacité dit ce que celui qui l'a rempli peut y faire.

Elle ne couvre pour l'instant qu'une moitié de la question. **Poster** fonctionne depuis
2008 et n'est décrit nulle part ; **retrouver** est spécifié ici parce que c'est ce qui ne
fonctionnait pas. La capacité est ouverte, pas remplie.

## Requirements

### Requirement: Un contributeur retrouve un morceau qu'il a posté

Le contributeur SHALL pouvoir restreindre la liste de ses morceaux sur les champs qui
servent à reconnaître un morceau : son titre, son artiste, le message écrit sous lui, sa
date de publication et sa mise en ligne.

Parcourir n'est pas retrouver. Sans restriction, retrouver un morceau ancien exige de
faire défiler la liste page par page, et le coût croît avec ce qu'un contributeur a
publié : le plus prolifique du collectif a posté 993 morceaux, soit cinquante pages à vingt
par page. Le mécanisme punit exactement les contributeurs les plus fidèles.

La restriction NE SHALL PAS élargir ce qu'un contributeur peut voir. Un contributeur sans
le droit d'éditer les morceaux d'autrui SHALL continuer de ne voir que les siens, la
restriction s'appliquant à l'intérieur de ce périmètre.

#### Scénario : Retrouver par le titre

- **QUAND** un contributeur restreint sa liste sur une portion de titre
- **ALORS** seuls ses morceaux dont le titre contient cette portion sont listés

#### Scénario : Retrouver par le message

- **QUAND** un contributeur restreint sa liste sur une portion du message écrit sous un morceau
- **ALORS** seuls ses morceaux dont le message contient cette portion sont listés

#### Scénario : La restriction n'ouvre pas la liste

- **QUAND** un contributeur sans le droit d'éditer les morceaux d'autrui restreint sa liste
- **ALORS** aucun morceau d'un autre contributeur n'apparaît dans les résultats

#### Scénario : Une restriction sans résultat

- **QUAND** une restriction ne correspond à aucun morceau
- **ALORS** la liste est vide et le dit
- **ET** la restriction reste affichée, de sorte qu'on puisse la corriger plutôt que la ressaisir
