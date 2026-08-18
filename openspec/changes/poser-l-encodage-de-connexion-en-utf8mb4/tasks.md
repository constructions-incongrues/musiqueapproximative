> Pas de `design.md`. Une ligne de configuration, et toutes les décisions qui la fondent
> sont dans `openspec/changes/archive/2026-08-18-migrer-la-base-en-utf8mb4/design.md`.

## 1. Poser l'encodage

- [x] 1.1 `encoding: utf8mb4` sur le bloc `all` de `src/config/databases.yml-dist`, avec le
  même commentaire que le bloc `test` : le `charset=` du DSN n'a aucun effet, Doctrine 1
  analysant le DSN lui-même ; c'est `encoding` qui produit le `SET NAMES`.
- [x] 1.2 `make configure`, et vérifier que le `databases.yml` rendu porte bien la ligne sur
  les deux blocs.

## 2. Vérification locale

- [x] 2.1 Vérifier que la connexion négocie `utf8mb4` en environnement de développement,
  qui reproduit désormais la production — MariaDB 10.11, tables converties.
- [x] 2.2 Insérer un morceau portant un emoji **par le chemin applicatif**, non par le
  client SQL, et le relire. C'est ce que fait un contributeur.
- [x] 2.3 `docker-compose exec php php symfony test:all` — la suite passe.

### Mesures locales du 2026-08-18

| | avant | après |
| --- | --- | --- |
| `character_set_client` | `utf8mb3` | **`utf8mb4`** |
| `character_set_connection` | `utf8mb3` | **`utf8mb4`** |

Essai par le **chemin applicatif** — un `Post` construit et sauvé par le modèle, comme le
fait le formulaire d'admin, non par le client SQL :

```
SAISI : Сергей Прокофьев 坂本龍一 | Пятое время года 🎵🔥
RELU  : Сергей Прокофьев 坂本龍一 | Пятое время года 🎵🔥
IDENTIQUE : OUI
```

Suite : 624 tests, verts.

### Deux choses rencontrées en vérifiant

**Le cache de configuration masquait le changement.** Après `make configure`, la connexion
négociait toujours `utf8mb3` : `databases.yml` est compilé dans `cache/`. Un
`symfony cache:clear` était nécessaire. À ne pas oublier en production — le déploiement
tire le code, il ne vide pas le cache.

**La base locale a dû être convertie et réparée.** Elle était restée en `latin1` — seules des
copies de répétition l'avaient été. Le script y a été appliqué, et l'environnement reproduit
de nouveau la production. Au passage, `track_duration` et `track_size` manquaient : le
réamorçage sur MariaDB a rechargé le dump du dépôt, **daté de 2021, antérieur à ces
colonnes**. Toute installation neuve rencontre ce défaut — voir plus bas.

## 3. Vérification en ligne, après déploiement

- [ ] 3.1 Poster depuis l'admin un morceau au titre cyrillique et un portant un emoji, comme
  un contributeur le ferait. **C'est le seul test qui exerce le chemin réel** — tout le
  reste n'en est qu'une approximation.
- [ ] 3.2 Vérifier qu'ils sont servis intacts dans la page, le JSON, le XSPF et le `max`.
- [ ] 3.3 Vérifier que la recherche les trouve.
- [ ] 3.4 Les retirer, ou les garder — mais le décider, pas l'oublier.

### Ce que ce change ne fait pas

- [ ] 3.5 Consigner : les **82 morceaux détruits le restent**. Le site cesse de détruire ;
  il ne répare rien de ce qui l'a été. C'est la story 20.

### Le défaut d'amorçage, corrigé dans le même geste

Le dump monté par `docker-compose` datait de 2021 et **précédait les colonnes
`track_duration` et `track_size`** que `schema.yml` déclare : toute installation neuve
obtenait une base sur laquelle chaque requête touchant `Post` échouait en 1054. Le défaut
avait mordu deux fois dans cette session.

Remplacé par un extrait de la production du 2026-08-18, **converti en `utf8mb4` comme elle**
et **anonymisé avant versement**, le dépôt étant public.

#### Ce que l'anonymisation a retiré, et pourquoi

Le dump versionné jusqu'ici exposait, en clair dans un dépôt public, **179 empreintes de mots
de passe et 173 adresses courriel**. Le dump frais en portait 210 et 200. Verser celui-ci tel
quel aurait étendu une exposition préexistante.

| donnée | traitement |
| --- | --- |
| `sf_guard_user.password` et `.salt` | remplacés — **1 seule empreinte** dans tout le fichier, contre 210 |
| `user_profile.email` | `profil<id>@exemple.invalid` — 201 neutralisés, 0 réel restant |
| `directus_users` | vidée, ainsi que sessions, activité, révisions |
| `sf_guard_remember_key` | vidée |
| **les 8 216 morceaux** | **conservés tels quels** — ils sont publics sur le site |

`.invalid` est réservé par la RFC 2606 : aucun de ces domaines ne peut exister.

Tous les comptes partagent désormais le mot de passe `motdepasse` avec le sel
`developpement`, ce qui laisse un poste de développement se connecter.

#### Vérifié sur une base neuve

| | |
| --- | --- |
| jeu de caractères de `post` | `utf8mb4` d'emblée |
| morceaux | 8 216 |
| colonnes du schéma | 2/2 présentes |
| accents | `Güyôm` préservé |

**Reste à décider, et ce n'est pas ce change** : l'historique git conserve les anciens dumps,
donc les 179 empreintes et 173 courriels de 2021 restent lisibles dans le dépôt public. Les
en retirer demande une réécriture d'historique — une décision qui appartient à l'auteur.
