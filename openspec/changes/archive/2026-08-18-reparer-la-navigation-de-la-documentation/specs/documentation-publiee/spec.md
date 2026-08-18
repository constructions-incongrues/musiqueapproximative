## ADDED Requirements

### Requirement: Toute page publiée est atteignable

Le site de documentation SHALL présenter une navigation qui mène à chacune de ses pages
publiées. Aucune page servie SHALL être atteignable seulement par son adresse directe.

La navigation SHALL être la source unique de cette liste. Une seconde liste tenue ailleurs
diverge de la première — c'est ce qui s'est produit.

Le projet SHALL détecter automatiquement toute page publiée absente de la navigation, et
SHALL nommer cette page plutôt que signaler un écart global.

#### Scénario : Une page nouvellement publiée entre dans la navigation

- **QUAND** une page est ajoutée à la documentation
- **ET** qu'elle n'est inscrite à aucune entrée de navigation
- **ALORS** la vérification automatisée échoue
- **ET** elle nomme la page absente

#### Scénario : Navigation complète

- **QUAND** un lecteur ouvre le site de documentation
- **ALORS** chaque page publiée est atteignable depuis la navigation
- **ET** aucune n'exige d'en connaître l'adresse à l'avance

#### Scénario : La page d'accueil ne redouble pas la navigation

- **QUAND** un lecteur ouvre la page d'accueil
- **ALORS** elle présente le projet et les façons d'y participer
- **ET** elle ne tient pas une seconde liste exhaustive des pages, que la navigation porte
  déjà
