# Tâches

Pas de `specs/` : `skip_specs` déclaré. Le site ne change pas de comportement — c'est
l'appareil de vérification du projet qui gagne un point de vue. Pas de `design.md`.

## 1. Trancher la question ouverte de la story

- [x] 1.1 La story demandait : tâche planifiée qui alerte, ou commande manuelle après une
  mise en ligne ?
- [x] 1.2 **Tâche planifiée**, et la raison tient au déploiement de ce projet : Plesk tire
  `main` à chaque poussée, **il n'existe aucune étape de mise en ligne** où accrocher une
  commande. Un geste sans moment est un geste qui n'a pas lieu.
- [x] 1.3 Assumer le mode de défaillance qui vient avec : une alerte planifiée qui crie au
  loup se désactive en trois semaines. La conception y répond au point 3.

## 2. Le contrôle, et son emplacement

- [x] 2.1 **Décision d'abord prise, puis renversée par une mesure.** Le contrôle avait été
  logé dans `nightly.yml`, au motif qu'un second rendez-vous serait un second endroit à
  surveiller. La première exécution a montré que ce workflow **échoue en continu depuis le
  16 juin 2026** — deux mois — sur un `jq not installed` de `trunk-action`.
  Une alerte posée dans un rendez-vous rouge en permanence est **une alerte déjà éteinte** :
  son signal ne se distingue pas du bruit auquel on s'est habitué. C'était exactement le
  mode de défaillance que ce change prétendait éviter, et je l'avais reproduit en le
  décrivant.
  Le contrôle a donc son propre workflow, `contrat-production.yml`, pour pouvoir passer au
  rouge tout seul.
- [x] 2.2 Ne vérifier **que ce que le déploiement peut casser** : le contrat est-il servi,
  à son adresse, avec un type de média YAML, sans motif `${…}` restant, et déclare-t-il
  encore ses neuf routes.
- [x] 2.3 Ne pas refaire le travail de la suite. Elle confronte déjà chaque route à
  l'instance de test ; le rejouer en ligne exercerait le site depuis GitHub tous les jours
  pour une information que le déploiement ne peut pas fausser.

## 3. Distinguer deux échecs, et c'est le cœur

- [x] 3.1 Une production **injoignable** n'est pas un contrat **faux**. Réseau, 5xx et
  délai dépassé sont réessayés trois fois, à vingt secondes.
- [x] 3.2 S'ils persistent, le contrôle le **dit sans échouer** : le site est en panne, ce
  n'est pas la question posée, et l'exploitation le sait déjà. Une alerte de plus sur un
  fait connu est du bruit, et le bruit fait désactiver l'alerte.
- [x] 3.3 Il ne signale donc que ce qu'il peut **affirmer** : un contrat présent et faux.

## 4. Vérification

- [x] 4.1 Répétition du contrôle contre la **vraie production** : statut `200`, type
  `application/yaml`, **0 variable restante, 9 routes**. Il passe.
- [x] 4.2 Cas fautif « variable non substituée » : injectée dans une copie, **détectée et
  nommée**, code de sortie 1.
- [x] 4.3 Cas fautif « contrat amputé de ses routes » : **détecté**, 0 au lieu de 9, code
  de sortie 1.
- [x] 4.4 Le fichier de workflow passe le linter.

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **`databases.yml` reste invérifiable.** La story 24 l'a montré : il n'expose
  aucune valeur observable depuis l'extérieur, et sa ligne `encoding: utf8mb4` ne se
  contrôle pas depuis un navigateur. Le rendre vérifiable demande de l'instrumenter.
- [ ] 5.2 La supervision — disponibilité, temps de réponse, certificat. Ce n'est pas de la
  vérification de contrat et ça ne se loge pas ici.
- [ ] 5.3 **Le délai reste d'une nuit.** Ce contrôle raccourcit la journée du 18 août à une
  nuit ouvrée ; il ne la supprime pas. Le supprimer demanderait un contrôle après
  déploiement, donc une étape de déploiement, qui n'existe pas.

### Vérification manuelle

- [x] 5.4 Lancé à la main après la fusion. Le job **passe** contre la production réelle et
  écrit : « Contrat servi : statut 200, type application/yaml, 9 routes, aucune variable
  restante. » C'est cette exécution qui a révélé l'état de `nightly.yml`.
- [ ] 5.5 **`nightly.yml` reste rouge**, et ce change ne le répare pas : la panne est dans
  `trunk-action`, sans rapport avec le contrat. Elle est signalée à part. Un workflow rouge
  depuis deux mois est un signal que plus personne ne lit, et c'est un problème en soi.
