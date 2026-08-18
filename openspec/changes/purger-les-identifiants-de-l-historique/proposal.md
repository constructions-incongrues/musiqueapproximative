## Why

Le dépôt est **public**, et son historique porte **207 empreintes de mots de passe SHA1
distinctes et 171 adresses courriel réelles**, réparties sur cinq versions de deux dumps de
production.

Le dump d'amorçage a été anonymisé le 2026-08-18. Cela règle l'avenir et **rien du passé** :
chaque version antérieure reste accessible par son empreinte de commit, et clonée sur tous
les postes qui ont déjà tiré le dépôt.

## L'exposition, mesurée

*Inventaire refait fichier par fichier le 2026-08-18, après qu'un galop d'essai a montré que
le premier relevé était incomplet.*

| fichier | empreintes | courriels |
| --- | --- | --- |
| `src/data/fixtures/musiqueapproximative.sql` | 197 | 171 |
| `src/data/fixtures/net_musiqueapproximative_www.dump.sql` | 114 | 102 |
| `src/data/fixtures/vagrant.dump.sql` | **104** | **102** |
| `src/data/fixtures/subsonic.sql` | 0 | 2 — fictifs, `example.net` réservé par la RFC 2606 |
| **union sur tout l'historique** | **207** | **173** |

**Trois fichiers, non deux.** `vagrant.dump.sql` avait échappé au premier relevé : une
réécriture sur les deux premiers seulement aurait laissé 104 empreintes et 104 courriels en
place, en donnant l'impression d'avoir purgé.

`sf_guard_user.algorithm` vaut `sha1`. Ce sont donc des empreintes **SHA1 salées**, sans
étirement de clé : une empreinte dont on connaît le sel — et le sel est dans le même dump —
se casse à la vitesse du matériel, pas à celle d'un algorithme conçu pour résister.

## Ce que ce changement fait, et l'ordre compte

**1. Considérer les mots de passe comme compromis, et les invalider.** C'est le premier
geste, pas le second. Réécrire l'historique sans changer les mots de passe ne fait que
compliquer l'accès à des données déjà copiées ; celles-ci circulent depuis des années, et
personne ne sait où.

**2. Prévenir les 207 personnes concernées.** Elles n'ont jamais été informées que leur
empreinte et leur adresse figuraient dans un dépôt public.

**3. Réécrire l'historique**, pour que l'exposition cesse de s'ajouter à chaque nouveau
clone.

**4. Demander à GitHub de purger les objets devenus inatteignables.** Une réécriture les
laisse accessibles par leur empreinte tant que le ramasse-miettes du service ne les a pas
retirés ; seul le support peut le déclencher.

## Hors périmètre

- **Changer l'algorithme de hachage** de sfGuard. C'est un travail à part, et il ne répare
  pas ce qui est déjà sorti.
- **Les dumps hors du dépôt** — sauvegardes, copies locales, forks. Ils ne sont pas
  atteignables depuis ici, et il faut le dire plutôt que de laisser croire à une purge
  complète.
- **Le dump d'amorçage actuel**, déjà anonymisé.

## Capabilities

Aucune. Rien du comportement observable du site ne change. `skip_specs` est déclaré.

## Impact

- **Réécrit** : l'historique complet du dépôt. Toute empreinte de commit change, tous les
  clones existants deviennent divergents, toutes les branches et PR ouvertes doivent être
  reprises.
- **Modifié** : rien dans l'arbre de travail final — le contenu actuel est déjà propre.
- **Coordination requise** : c'est la partie la plus coûteuse, et elle n'est pas technique.
