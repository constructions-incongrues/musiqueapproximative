## Context

Trois manques distincts produisent le même symptôme. Les traiter comme un seul conduirait à
n'en corriger qu'un.

```
  start-dev.sh écrit .env ──▶ make configure ──▶ databases.yml
        │ manque                                      │ dsn littéral
        │ DATABASE_NAME_TEST                          ▼
        │                                    bootstrap/database.php
        │                                             │ garde-fou
        ▼                                             ▼
  base musiqueapproximative_test         « refus de charger les fixtures »
        │ n'existe pas
        │ schéma non construit
        ▼
```

## Goals / Non-Goals

**But.** Qu'un poste neuf puisse lancer `php symfony test:all` après une commande.

**Non-buts.** Charger les fixtures, qui est déjà fait par le bootstrap. Traiter `getID3`.
Toucher aux profils de déploiement.

## Decisions

### Une cible `make`, pas une extension de `start-dev.sh`

`start-dev.sh` est un script de premier démarrage : il crée le `.env`, lance Docker,
attend. Y ajouter la préparation d'une base de test allongerait un chemin que tout le monde
emprunte, pour un besoin qu'ont seulement ceux qui lancent les tests.

`make test-init` est appelable quand on en a besoin, et rejouable. `start-dev.sh` n'est
corrigé que sur la variable manquante, qui est un vrai défaut de son modèle.

### La cible est idempotente, et ne présume pas de l'état

`CREATE DATABASE IF NOT EXISTS` puis `doctrine:insert-sql --env=test`. La première ne fait
rien si la base est là ; la seconde reconstruit les tables, ce qui est sans effet de bord
puisque les fixtures les vident à chaque fichier de test de toute façon.

Écarté : détecter finement ce qui manque pour n'agir que là-dessus. Le gain est nul et le
code de détection serait plus fragile que les deux commandes qu'il éviterait.

### `docker compose exec`, et non `run --rm`

La cible suppose l'environnement démarré : c'est le cas de quiconque s'apprête à lancer des
tests. `exec` échoue avec un message clair si les conteneurs sont à l'arrêt, là où
`run --rm` en démarrerait un silencieusement et laisserait croire que tout va bien alors
que la base n'est pas jointe.

## Risks / Trade-offs

- **`make test-init` ne corrige pas un `.env` déjà écrit.** Un poste installé avant ce
  changement garde son `.env` à quatre variables. La documentation le dit, et le
  diagnostic est immédiat : le DSN porte le littéral `${DATABASE_NAME_TEST}`.
- **La cible construit le schéma sans demander confirmation.** Sur la base de test
  uniquement, dont le nom est vérifié par le garde-fou du bootstrap à chaque exécution.

## Open Questions

Aucune.
