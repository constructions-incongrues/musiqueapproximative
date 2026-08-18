## ADDED Requirements

### Requirement: Servir une liste coûte un nombre de requêtes constant

Le coût en requêtes de base pour servir une liste de morceaux SHALL être indépendant du
nombre de morceaux servis.

Aucune donnée de contributeur nécessaire au rendu d'une liste SHALL être lue morceau par
morceau : ce que le rendu lit, la requête de liste SHALL l'avoir chargé.

Ce coût SHALL être vérifié automatiquement. Une régression qui le rend proportionnel au
nombre de morceaux SHALL faire échouer la suite de tests — sans quoi elle revient au premier
accès ajouté dans un gabarit, sans que rien ne le signale.

#### Scénario : Le coût ne suit pas la taille de la liste

- **QUAND** une liste de morceaux est servie
- **ALORS** le nombre de requêtes de base émises ne dépend pas du nombre de morceaux qu'elle
  contient
- **ET** demander deux fois plus de morceaux n'émet pas deux fois plus de requêtes

#### Scénario : Le contributeur est chargé avec la liste

- **QUAND** le rendu d'une liste lit le nom d'affichage, l'identifiant ou le site d'un
  contributeur
- **ALORS** cette lecture n'émet aucune requête supplémentaire

#### Scénario : Une régression est détectée

- **QUAND** un accès lu morceau par morceau réapparaît sur un chemin de liste
- **ALORS** la suite de tests échoue
- **ET** elle nomme le coût constaté et celui attendu

#### Scénario : Les consommateurs à projection restreinte ne paient pas plus

- **QUAND** un appelant demande une liste en ne réclamant qu'une partie des champs
- **ALORS** il ne reçoit pas de données qu'il n'a pas demandées
- **ET** son coût en requêtes n'augmente pas
