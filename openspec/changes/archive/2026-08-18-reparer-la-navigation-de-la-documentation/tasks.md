# Tâches

Pas de `design.md` : ni schéma, ni module, ni dépendance. Un fichier de navigation et un
contrôle de sept lignes. La règle du dépôt réserve cet artefact aux changements structurants.

## 1. Mesurer avant de corriger

- [x] 1.1 Relevé : **16 pages publiées**, `docs/modules/ROOT/nav.adoc` **absent** alors que
  `docs/antora.yml` le déclare, **7 pages atteignables** depuis le sommaire manuel,
  **8 orphelines**.
- [x] 1.2 Nommer les orphelines plutôt que les compter : `README`, `cicd/github-actions`,
  `cicd/release-please`, `developpement/commandes`, `developpement/environnement`,
  `developpement/tests`, `ghcr`, `migration-utf8mb4`.
- [x] 1.3 Constater que la dérive est en cours et non historique : `migration-utf8mb4.adoc`,
  écrit le matin même, était déjà orphelin le soir.

## 2. La navigation

- [x] 2.1 Créer `docs/modules/ROOT/nav.adoc` avec les seize pages, groupées comme la
  documentation l'est déjà : Démarrer, Développer, Déployer et exploiter, Contribuer.
- [x] 2.2 Vérifier la couverture : **16 pages, 16 entrées, 0 manquante.**

## 3. La page d'accueil cesse de doubler la navigation

- [x] 3.1 Retirer d'`index.adoc` la liste « Documentation technique », qui redoublait la
  navigation et **est précisément ce qui a dérivé**. La remplacer par un renvoi à la
  navigation.
- [x] 3.2 Conserver « Comment participer ? » : ces trois liens sont un parcours choisi, pas
  un inventaire. Une page d'accueil a le droit d'orienter ; elle n'a pas à recenser.

## 4. Empêcher la dérive de revenir

- [x] 4.1 Ajouter un contrôle à `Validation du code` (`ci.yml`), qui tourne sur chaque
  proposition et fait partie des contextes exigés par le ruleset `main`.
- [x] 4.2 Le contrôle **nomme** chaque page absente via une annotation `::error file=`,
  plutôt que de signaler un écart global. Une erreur qu'il faut ensuite chercher soi-même
  se contourne ; une erreur qui pointe le fichier se corrige.
- [x] 4.3 Il échoue aussi si `nav.adoc` disparaît — c'est l'état de départ, et rien ne
  l'avait signalé.
- [x] 4.4 **Vérifier que le contrôle mord.** Une page piège a été déposée : détectée et
  nommée, code de sortie 1. Piège retiré, vérification faite qu'il ne reste pas.

## 5. Ce que la construction Antora a révélé, et qui reste dehors

- [x] 5.1 Le site se construit et se déploie : les cinq dernières exécutions du workflow
  `Documentation` sont vertes. Le défaut n'était pas la publication, c'était l'accès.
- [x] 5.2 Deux liens internes cassés relevés en passant, **non corrigés ici** :
  `deploiement.adoc:36` vers `API_SUBSONIC.adoc`, page inexistante dont le contenu réel est
  `docs/API_SUBSONIC.md` hors arborescence Antora ; et `README.adoc:112`, exemple de syntaxe
  qu'Antora prend pour un lien. Ils relèvent du contenu, que ce change n'ouvre pas.

## 6. Vérification

- [x] 6.1 Le contrôle passe en local sur l'état corrigé : « 16 entrées de navigation, aucune
  page orpheline ».
- [x] 6.2 Le contrôle échoue sur un état fautif, en nommant le fichier.

### Vérification manuelle — après la mise en ligne

- [x] 6.3 Site publié vérifié : la barre latérale porte **19 entrées** réparties dans les
  quatre groupes.
- [x] 6.4 `developpement/tests.html` répond **200** depuis le site publié — l'une des trois
  orphelines qui expliquent comment lancer la suite. C'est le cas d'usage qui a motivé la
  story, et il est servi.
- [x] 6.5 La page d'accueil ne porte plus de liste : sous « Documentation technique »,
  elle renvoie à la navigation et dit pourquoi elle n'en tient pas une seconde.
