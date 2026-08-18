## ADDED Requirements

### Requirement: Contrat consultable en page lisible

Le site SHALL publier, en plus du document lui-même, une page qui en présente le contenu
sous forme lisible : routes, paramètres, réponses et schémas parcourables sans ouvrir un
éditeur.

Cette page SHALL être servie par le site lui-même et SHALL n'émettre aucune requête vers un
tiers. Un visiteur qui consulte la description de l'API ne SHALL être annoncé à personne.

Elle SHALL conserver un accès au document brut, la présentation ne remplaçant pas la source.

#### Scénario : Page de consultation servie

- **QUAND** un visiteur demande la page de consultation du contrat
- **ALORS** elle est servie avec un code `200`
- **ET** les routes déclarées au contrat y sont présentées avec leurs paramètres et leurs
  réponses

#### Scénario : Aucun tiers sollicité

- **QUAND** la page de consultation se charge
- **ALORS** toutes les ressources qu'elle demande proviennent du site qui la sert
- **ET** aucune requête n'est émise vers un autre domaine, polices comprises

#### Scénario : La source reste atteignable

- **QUAND** un visiteur consulte la page
- **ALORS** un lien vers le document brut y figure
- **ET** le document reste servi à son adresse propre, indépendamment de cette page

#### Scénario : Le contrat indisponible ne casse pas la page

- **QUAND** le document ne peut pas être chargé
- **ALORS** la page l'indique et renvoie vers l'adresse du document brut
- **ET** elle ne reste pas sur un écran d'attente sans issue
