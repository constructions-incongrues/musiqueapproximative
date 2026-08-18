## Why

La migration en `utf8mb4` a arrêté l'hémorragie. Elle n'a rien soigné : les métadonnées
déjà détruites le restent, et **la destruction a lieu à l'écriture**, donc aucune
sauvegarde ne porte une version intacte. Ce qui est perdu l'est dans tout le système.

Relevé sur la production depuis le catalogue public : **85 morceaux sur 8 098** portent un
titre ou un auteur mutilé — 1,05 % — chez **37 contributeurs**, du 19 juin 2008 au
27 juillet 2026. Du cyrillique, du japonais, du polonais, du vietnamien, du letton, du
hongrois, du turc.

Les laisser sans inventaire, c'est décider de les oublier sans le dire. Cette story
produit la liste et pose la question aux seules personnes qui peuvent y répondre : celles
qui ont saisi ces titres.

Deux faits mesurés changent la façon de mener la conversation. **23 des 85 datent de 2022
ou après, chez 9 contributeurs** : ceux-là ont une chance réelle d'être reconstitués. Les
62 autres remontent jusqu'à 2008 — se souvenir de ce qu'on a saisi en 2009 n'est pas se
souvenir de la semaine dernière, et il faut le dire plutôt que d'entretenir un espoir.

## What Changes

- Ajout de `docs/modules/ROOT/pages/morceaux-alteres.adoc` : le relevé, la méthode qui le
  rend refaisable, les deux populations séparées, la liste complète, et ce qui est demandé
  aux contributeurs.
- Inscription de la page à la navigation.
- La page dit aussi ce qui n'est **pas** fait : marquer les morceaux comme altérés dans
  l'interface. C'est le minimum si personne ne répond, et ce n'est pas fait à ce jour.

Aucun code n'est touché. Aucune donnée n'est modifiée.

## Hors périmètre

- **Deviner les titres.** Reconstituer « ??????????? ????? » sans le contributeur
  reviendrait à inventer des données et à les présenter comme retrouvées. La page dit ce
  qui manque ; elle ne le remplit pas.
- **Marquer les morceaux comme altérés** dans l'interface publique. C'est un travail de
  données et d'affichage, à mener une fois la conversation close — pas avant, sinon on
  marque comme perdu ce qui allait être retrouvé.
- **Écrire aux 37 contributeurs.** La page porte la demande et l'adresse ; l'envoi est un
  geste du collectif, pas un commit.
