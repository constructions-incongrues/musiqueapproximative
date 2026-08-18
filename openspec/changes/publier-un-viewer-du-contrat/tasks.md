# Tâches

Pas de `design.md` : ni schéma, ni module, ni dépendance à l'exécution du côté serveur.
La règle du dépôt réserve cet artefact aux changements structurants.

## 1. Verser Redoc

- [x] 1.1 Télécharger `redoc.standalone.js` depuis une **URL épinglée à une version**, non
  depuis `latest`. Version retenue : **2.5.3**.
- [x] 1.2 Vérifier que l'URL épinglée et `latest` servent les mêmes octets au moment du
  versement. **Vérifié**, `cmp` identique.
- [x] 1.3 Verser le fichier, son `LICENSE.txt` (MIT, attributions complètes) et un
  `PROVENANCE.md` portant la source, la somme sha256, la taille, le motif du versement et la
  procédure de remplacement.
- [x] 1.4 Le placer sous `src/web/frontend/assets/javascripts/redoc/`, là où le site verse
  déjà jQuery, jPlayer et html5.js — plutôt que d'inventer un dossier `vendor/`.
- [x] 1.5 Exclure ce chemin de tous les linters dans `.trunk/trunk.yaml`, avec le motif :
  reformater du code tiers romprait la correspondance avec la somme de contrôle, qui est ce
  qui rend le versement vérifiable.

## 2. La page

- [x] 2.1 Créer `src/web/api.html` : bandeau reprenant l'inversion noir/blanc du site, avis
  que le document est descriptif et non normatif, lien vers le YAML brut, puis le rendu.
- [x] 2.2 Thème Redoc aligné sur `DESIGN.md` — primaire noir, Arvo pour les titres, Rambla
  pour le texte, la fonte à chasse fixe du site pour le code.
- [x] 2.3 **Ne charger aucune police depuis un tiers.** Les noms sont ceux du site ; si le
  visiteur arrive depuis celui-ci elles sont en cache, sinon les substituts système
  prennent le relais. Charger `fonts.googleapis.com` annulerait l'autonomie de la page.
- [x] 2.4 Prévoir l'échec : si le contrat ne se charge pas, la page le dit et renvoie vers
  `/openapi.yaml` au lieu de rester sur « Chargement… ».

## 3. Fichier plat, et c'est délibéré

- [x] 3.1 Servir la page comme `src/web/api.html`, non comme `src/web/api/index.html`.
  **Motif mesuré** : `/api/` répond **404** sur le serveur de développement, qui ne sert pas
  d'index de répertoire. La règle de `.htaccess` ne détourne que ce qui n'existe pas sur le
  disque (`!-f`, `!-d`), donc un fichier est servi à coup sûr, là où un répertoire dépend
  d'un `DirectoryIndex` qu'on ne peut pas vérifier avant la mise en ligne.
  C'est le même piège que le contrat servi en 404 ce matin : ne pas expédier une adresse
  qu'on n'a pas pu essayer.
- [x] 3.2 Ne pas toucher à `.htaccess`. Une erreur de syntaxe y met le site entier en 500,
  et l'objectif ne le justifie pas.

## 4. Le lien

- [x] 4.1 La section « API » de la barre latérale mène à `/api.html`. Le lien vers le YAML
  brut reste, porté par la page elle-même.

## 5. Vérification

- [x] 5.1 `/api.html` répond **200** en local, le bundle Redoc aussi.
- [x] 5.2 Redoc **rend effectivement le contrat** : les quatre familles de routes
  (`morceau`, `liste`, `lecteur`, `embarquement`) apparaissent en barre latérale, avec
  paramètres, réponses et schémas. Vérifié dans un navigateur, pas seulement au code HTTP.
- [x] 5.3 **Zéro erreur** en console.
- [x] 5.4 **Zéro requête vers un tiers** dans le source de la page.
- [x] 5.5 Rendu mobile (375 × 812) vérifié : le bandeau passe à la ligne, aucun débordement
  horizontal, Redoc replie sa barre latérale.

### Vérification manuelle — après la mise en ligne

- [ ] 5.6 Demander `https://www.musiqueapproximative.net/api.html`. Attendu : `200`, et les
  routes rendues — pas seulement un écran d'attente.
- [ ] 5.7 Depuis la barre latérale du site, suivre « contrat OpenAPI » et vérifier qu'on
  arrive sur la page rendue.
- [ ] 5.8 Dans l'onglet réseau du navigateur, vérifier qu'aucune requête ne sort du domaine.
