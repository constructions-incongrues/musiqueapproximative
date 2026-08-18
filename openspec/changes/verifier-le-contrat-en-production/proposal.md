## Why

Le 18 août 2026, `openapiContractTest` a déclaré le contrat conforme à chaque exécution de
la suite pendant que `https://www.musiqueapproximative.net/openapi.yaml` répondait **404**.
Le document a passé une journée entière pour publié sans l'être.

Le test n'a rien manqué : il fait ce qu'il annonce. Il lit le fichier de l'instance de test
et confronte le site de test à ce fichier. **Il ne demande jamais rien à la production.**

C'est une classe de défaut, pas un incident. La suite prouve que le *code* sert le contrat ;
elle ne prouve rien de ce que le *déploiement* met en ligne. Entre les deux il y a
`make configure`, des fichiers gitignorés et un `git pull` — et le contrat a déjà disparu
dans cet intervalle. La story 24 a montré que la même cécité vaut pour la configuration :
`databases.yml` n'expose aucune valeur observable, et sa dérive ne serait vue par personne.

## What Changes

- Ajout d'un contrôle **contre la production** au rendez-vous nocturne existant
  (`nightly.yml`), qui tourne déjà en semaine à 8 h.
- Il vérifie ce que **seul le déploiement peut casser** : le contrat est-il servi, à son
  adresse, avec le bon type de média, sans motif `${…}` restant, et déclare-t-il encore ses
  routes. Il ne refait pas le travail de la suite, qui confronte déjà chaque route à
  l'instance de test.
- Il distingue deux échecs, et c'est le cœur de sa conception : une production
  **injoignable** n'est pas un contrat **faux**.

## La question ouverte de la story, tranchée

Elle demandait : tâche planifiée qui alerte, ou commande à lancer à la main après une mise
en ligne ? La première se périme sans qu'on la regarde, la seconde ne s'exécute pas.

**Tâche planifiée**, pour une raison qui tient au déploiement de ce projet : Plesk tire
`main` à chaque poussée, **il n'existe aucune étape de mise en ligne** où accrocher une
commande manuelle. Un geste sans moment est un geste qui n'a pas lieu.

Reste le mode de défaillance de l'alerte planifiée : celle qui crie au loup se désactive en
trois semaines. Le contrôle **ne signale donc que ce qu'il peut affirmer** — un contrat
présent et faux. Une erreur réseau, un 5xx ou un délai dépassé sont réessayés, et s'ils
persistent, le contrôle le **dit sans échouer** : le site est en panne, ce n'est pas la
question posée, et l'exploitation le sait déjà.

## Hors périmètre

- **Confronter chaque route de la production au contrat.** La suite le fait contre
  l'instance de test ; le refaire en ligne exercerait le site depuis GitHub tous les jours
  pour une information que le déploiement ne peut pas fausser.
- **Surveiller autre chose que le contrat** — disponibilité, temps de réponse, certificat.
  C'est de la supervision, pas de la vérification de contrat, et ça ne se loge pas ici.
- **Vérifier `databases.yml`**, dont la story 24 a montré qu'il n'expose rien d'observable.
  Le rendre vérifiable demande de l'instrumenter, ce qui est un autre travail.
