# Tâches

Pas de `design.md` : le changement ne porte ni schéma, ni dépendance, ni module nouveau.
La règle du dépôt réserve cet artefact aux changements structurants.

## 1. Le contrat cesse d'être un gabarit

- [x] 1.1 Renommer `src/web/openapi.yaml-dist` en `src/web/openapi.yaml` et le verser au
  dépôt. Retirer `/src/web/openapi.yaml` de `.gitignore`.
- [x] 1.2 Remplacer le bloc `servers` par `url: /`, adresse relative résolue contre
  l'emplacement du document, avec une description fixe.
- [x] 1.3 Réécrire l'en-tête du fichier : il disait « NE PAS ÉDITER LA VERSION GÉNÉRÉE ».
  Il dit maintenant pourquoi le document ne doit **pas** redevenir un gabarit — les rendus
  sont ignorés par git alors que le déploiement se fait par `git pull`.
- [x] 1.4 Vérifier qu'aucun `${...}` ne subsiste : `grep -c '\${' src/web/openapi.yaml`
  renvoie **0**, et les 23 clés `$ref` sont intactes.

## 2. Le garde-fou change d'objet

- [x] 2.1 Dans `openapiContractTest.php`, retirer la comparaison des `$ref` entre le rendu
  et le gabarit : le gabarit n'existe plus.
- [x] 2.2 La remplacer par le nouvel invariant — aucun motif `${...}` dans le document. Un
  tel motif signalerait qu'une étape de rendu est revenue, donc que le document
  n'arriverait plus en ligne.
- [x] 2.3 Corriger le message d'absence du fichier, qui renvoyait à `make configure`.
- [x] 2.4 Dans `.github/workflows/tests.yml`, remplacer la vérification « le contrat a reçu
  son domaine » par deux contrôles : `openapi.yaml-dist` n'est pas réapparu, et le document
  ne porte aucune variable.

## 2bis. Ce que le renommage a réveillé, non anticipé

Le fichier s'appelait `openapi.yaml-dist`, extension que Trunk ne reconnaissait pas. Devenu
`openapi.yaml`, il entre pour la première fois dans le champ des linters. Quatre griefs, et
aucun ne se corrige en changeant le contrat.

- [x] 2bis.1 **`prettier` casse le document.** Il éclate `required: [a, b, c]` en séquence
  de flux multiligne, forme que `sfYaml` — le parseur YAML de Symfony 1 — ne sait pas lire :
  « Unable to parse line 480 ([) ». Or c'est ce parseur qui lit le contrat dans le test qui
  le confronte au site. **Formater le document le rendrait illisible par ce qui le vérifie.**
  Constaté en exécutant `trunk fmt` puis la suite : 58 assertions vertes avant, échec de
  lecture après. Formatage annulé, `prettier` désactivé sur ce seul fichier.
- [x] 2bis.2 **`checkov` CKV_OPENAPI_4 et _5** exigent une section `security`. L'API est
  publique et non authentifiée. Les satisfaire demanderait de déclarer une authentification
  qui n'existe pas.
- [x] 2bis.3 **`checkov` CKV_OPENAPI_21** exige un `maxItems` sur le tableau de morceaux.
  Le site ne borne pas `/posts` — décision de l'auteur, la page entière sert au CTRL+F.
  Déclarer un plafond serait faux.
- [x] 2bis.4 **`yamllint/quoted-strings`** signale les guillemets de
  `version: "1.11.0" # x-release-please-version`. La ligne est réécrite par un automate ;
  on ne touche pas à la forme de ce qu'un automate édite.
- [x] 2bis.5 Les quatre sont désactivés sur ce seul chemin dans `.trunk/trunk.yaml`, chacun
  avec son motif écrit. **Aucun n'est masqué sans raison** : trois demandaient de faire
  mentir un document dont la raison d'être est de ne pas mentir.

## 3. Les renvois suivent

- [x] 3.1 `release-please-config.json` : `extra-files` pointe sur `src/web/openapi.yaml`.
- [x] 3.2 `CLAUDE.md` : le renvoi au contrat perd son suffixe `-dist`.
- [x] 3.3 Ne pas réécrire les changes archivés ni le packet de la story 10 dans
  `discovery.md` : ce sont des relevés de ce qui a été décidé, pas de la documentation
  vivante.

## 4. Vérification

- [x] 4.1 `make configure` ne touche plus au contrat — la cible itère sur `*-dist`, et git
  ne voit qu'un renommage après exécution. **Vérifié.**
- [x] 4.2 Le test de contrat passe : **58 assertions**, toutes vertes.
- [x] 4.3 Le garde-fou mord. Vérifié en réintroduisant `url: https://${APP_DOMAIN}` : le
  test échoue avec « Le contrat porte la variable non substituee ${APP_DOMAIN} ». Fichier
  restauré à l'identique après l'essai.
- [x] 4.4 Servi en local : `http://localhost:8001/openapi.yaml` répond **200**, et le
  document sorti porte `url: /`.

### Vérification manuelle — à faire après la mise en ligne

- [ ] 4.5 **Supprimer d'abord la copie manuelle présente sur le serveur.**
  `src/web/openapi.yaml` y est actuellement un fichier non suivi ; `git pull` refusera
  d'écraser un fichier non suivi et **le déploiement échouera** tant qu'il est là.
  Sur le serveur : `rm httpdocs/src/web/openapi.yaml` avant la mise en ligne.
- [ ] 4.6 Demander `https://www.musiqueapproximative.net/openapi.yaml`. Attendu : `200`, et
  le bloc `servers` porte `url: /` — plus aucun `${APP_DOMAIN}`.
- [ ] 4.7 Vérifier depuis la barre latérale du site : la section « API » mène au document
  et non à une page d'erreur.

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **Le contrat n'est toujours vérifié que contre l'instance de test.** C'est le trou
  qui a laissé un document 404 passer pour publié pendant une journée. Une vérification
  contre la production est une story à part ; elle est nommée ici pour ne pas être perdue.
- [ ] 5.2 Les autres fichiers `-dist` du dépôt sont exposés au même piège. Aucun n'a été
  mesuré. À traiter par une story qui commence par mesurer.
