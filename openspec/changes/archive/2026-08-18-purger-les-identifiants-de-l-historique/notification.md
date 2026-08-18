# Texte de notification — brouillon

> À relire et à envoyer **par l'auteur**. Ce dépôt ne peut pas écrire à 173 personnes en
> votre nom, et ne doit pas.
>
> À envoyer **après** l'invalidation des mots de passe (tâches 1.x), non avant : prévenir
> quelqu'un d'un risque encore ouvert l'expose sans lui donner de quoi agir.

---

**Objet** : Tes identifiants Musique Approximative ont été exposés publiquement

Salut,

Je t'écris parce que tu as posté sur Musique Approximative, et qu'une donnée te concernant
a été exposée publiquement pendant des années. Je préfère te le dire directement.

## Ce qui a été exposé

Le code du site est public sur GitHub. Il contenait des copies de la base de données,
versées là pour permettre à quelqu'un de faire tourner le site en local. Ces copies
portaient :

- ton adresse courriel ;
- l'empreinte de ton mot de passe, avec le sel qui va avec.

Ce n'est pas ton mot de passe en clair. Mais l'algorithme employé — SHA1 — est rapide, et le
sel figurait dans le même fichier. **Il faut considérer que ce mot de passe est connu**, pas
qu'il est protégé.

## Depuis quand

Depuis plusieurs années. Je ne peux pas dire qui a pu le lire, ni si quelqu'un l'a fait.

## Ce que j'ai fait

- Les copies de base versées au dépôt sont désormais anonymisées.
- Les mots de passe du site ont été invalidés : le tien ne fonctionne plus, tu devras en
  choisir un nouveau.

## Ce que tu devrais faire

**Si tu as réemployé ce mot de passe ailleurs — messagerie, réseaux, banque, n'importe quoi —
change-le sur ces services.** C'est le point qui compte le plus, et c'est le seul que je ne
peux pas faire à ta place. Un mot de passe qui sort d'un site sort de tous ceux où il a
servi.

## Ce que je ne peux pas défaire

Le dépôt est cloné et dérivé ailleurs. Même après nettoyage, des copies subsistent hors de
ma portée. C'est pourquoi le changement de mot de passe compte davantage que le nettoyage.

Désolé. La faute est de mon côté, pas du tien.

—

---

## Notes pour l'envoi

- **Ne pas minimiser.** « Des empreintes, pas des mots de passe » est vrai et trompeur : du
  SHA1 salé sans étirement se casse. Le brouillon ci-dessus le dit ; ne pas l'adoucir.
- **Envoyer après l'invalidation**, jamais avant.
- **173 adresses**, dont certaines ont quinze ans et ne fonctionnent plus. Les retours en
  erreur sont attendus ; ils ne dispensent pas d'avoir essayé.
- **Envoi individuel ou copie cachée.** Mettre 173 adresses en copie visible serait une
  seconde fuite, de la même nature que la première.
