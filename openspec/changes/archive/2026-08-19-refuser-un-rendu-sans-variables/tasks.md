# Tâches

Pas de `specs/` : `skip_specs` est déclaré. Aucun comportement observable du site ne change —
c'est une commande d'exploitation qui cesse de détruire.

## 1. Reproduire avant de corriger

- [x] 1.1 Reproduire dans un répertoire jetable, **sous Linux** : la reproduction sur macOS
  ne dit rien, `make` y échoue pour une autre raison.
- [x] 1.2 Établir les trois cas. **`.env` absent → `make` refuse, rien n'est écrit.**
  **`.env` vide → le gabarit est recopié verbatim, `${DATABASE_HOST}` compris.**
  `.env` correct → rendu correct.
- [x] 1.3 **Corriger le diagnostic du plan**, qui attribuait l'incident à l'absence du
  fichier. C'est l'inverse : l'absence est le cas sûr, `include .env` en ligne 1 arrête make.
- [x] 1.4 Constater que le piège n'est pas réservé au serveur : **`src/.env` fait 0 octet sur
  le poste de développement**, où le vrai fichier est un montage Docker fourni au conteneur.

## 2. Le garde-fou

- [x] 2.1 Vérifier que **la liste blanche de variables n'est pas vide** — et non que le
  fichier existe. C'est le vide de la liste qui produit le dégât, et `include` couvre déjà
  l'absence.
- [x] 2.2 Placer la vérification **avant la boucle**. La cible écrit gabarit par gabarit ;
  un garde dans la boucle laisserait une configuration à moitié rendue, plus difficile à
  diagnostiquer qu'une configuration entièrement fausse.
- [x] 2.3 Le message dit **ce qui a été refusé et que rien n'a été touché**, **ce qui
  manque**, et **où le lire**. Un message qui dit seulement « erreur » renvoie à la
  documentation — or l'incident a eu lieu parce qu'elle n'a pas été lue.
- [x] 2.4 Sortir en code d'erreur, pour qu'un script appelant s'arrête aussi.

## 3. Ne pas gêner le cas nominal

- [x] 3.1 `make configure` dans le conteneur, avec le `.env` du profil monté, rend les
  fichiers comme avant. C'est le geste quotidien : il ne doit rien perdre.
- [x] 3.2 **Empreintes identiques** avant/après sur `databases.yml` (`2c5c3f65…`) et
  `app.yml` (`8258dc84…`), zéro motif non substitué. Le rendu ne perd rien.
- [x] 3.3 `make deploy` à la racine appelle `make configure` dans le conteneur : vérifier
  que la chaîne complète n'est pas cassée.

## 4. La documentation dit aujourd'hui le mauvais cas

- [x] 4.1 `fichiers-de-configuration.adoc` attribue l'incident à un `.env` absent. Corriger :
  c'est le fichier **vide ou sans variables** qui détruit, et l'absence est sûre.
- [x] 4.2 Dire que le garde-fou existe désormais, et ce qu'il refuse.

## 5. Vérification

- [x] 5.1 **Le garde-fou mord** : `.env` vide → refus, code de sortie non nul, message
  nommant le chemin du `.env` fautif et la commande correcte.
- [x] 5.2 **Aucun rendu partiel** : deux gabarits dans deux répertoires, `.env` vide →
  **0 fichier écrit**. Le garde est bien avant la boucle.
- [x] 5.3 Cas absent : `make` refuse comme avant, le comportement ne régresse pas.
- [x] 5.4 Cas nominal : rendu identique, empreintes comparées.
- [x] 5.5 `test:all` : **24 fichiers, 666 tests, verts** — la suite ne touche pas au Makefile, mais elle dépend des fichiers
  qu'il rend.

## 6. Ce que ce change ne ferme pas

- [ ] 6.1 **`make configure` reste inutilisable sur le serveur.** Il refusera proprement au
  lieu de détruire, ce qui est le but ; le rendre utilisable demande de décider où vit le
  `.env` de production.
- [ ] 6.2 **`src/.env` reste vide sur le poste de développement.** Le garde-fou le rend
  inoffensif, il ne le remplit pas — et le remplir demanderait de choisir un profil par
  défaut, ce qui n'est pas anodin quand il en existe trois.
