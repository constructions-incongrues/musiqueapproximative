## ADDED Requirements

### Requirement: Le visiteur sait quelle version il consulte

Le site SHALL indiquer, sur chaque page, la version qu'il sert, et SHALL offrir un lien vers
les notes de publication correspondantes.

Lorsque aucune version nommée n'est disponible — une installation servie hors publication —
le site SHALL le dire plutôt que d'afficher une version fausse, et SHALL renvoyer vers
l'ensemble des notes de publication plutôt que vers une notice inexistante.

#### Scénario : Version nommée servie

- **QUAND** un visiteur ouvre une page du site servie depuis une version publiée
- **ALORS** le numéro de cette version lui est présenté
- **ET** un lien mène aux notes de publication de cette version précise

#### Scénario : Installation sans version nommée

- **QUAND** le site est servi sans version publiée
- **ALORS** il l'indique comme telle au lieu d'afficher un numéro
- **ET** le lien mène à l'ensemble des notes de publication, non à une notice qui n'existe
  pas
