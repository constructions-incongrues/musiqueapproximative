include .env
export $(shell sed 's/=.*//' .env)

help: ## Affiche ce message d'aide
	@for MKFILE in $(MAKEFILE_LIST); do \
		grep -E '^[a-zA-Z0-9\._-]+:.*?## .*$$' $$MKFILE | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; \
	done

configure: ## Configure l'application en fonction de l'environnement
	@# Le 2026-08-18, cette cible a mis toutes les pages du site en 500 : elle a
	@# reecrit databases.yml avec ${DATABASE_HOST} au lieu de l'hote, et la
	@# connexion a echoue.
	@#
	@# Le mecanisme, reproduit sous Linux avant d'etre corrige :
	@#
	@#   .env ABSENT   -> `include .env` ligne 1 arrete make. RIEN n'est ecrit.
	@#                    C'est le cas SUR, contrairement a ce qu'on a longtemps cru.
	@#   .env VIDE     -> l'include reussit, VARS devient la chaine vide, et
	@#                    `envsubst ''` ne substitue AUCUNE variable : il recopie
	@#                    le gabarit verbatim. C'est l'incident.
	@#   .env correct  -> rendu correct.
	@#
	@# Le garde porte donc sur la LISTE DE VARIABLES, pas sur l'existence du
	@# fichier — celle-ci est deja couverte, et garder dessus laisserait passer
	@# exactement le cas qui a casse le site.
	@#
	@# Il s'execute AVANT la boucle : la cible ecrit gabarit par gabarit, et un
	@# garde place dans la boucle laisserait une configuration a moitie rendue,
	@# plus difficile a diagnostiquer qu'une configuration entierement fausse.
	@VARS=`cat ./.env | cut -d '=' -f1 | sed 's/^/$$/' | tr "\n" " "`; \
	if [ -z "`echo $${VARS} | tr -d '[:space:]'`" ]; then \
		echo "make configure : REFUSE, aucun fichier n'a ete touche." >&2; \
		echo "" >&2; \
		echo "  `pwd`/.env ne fournit aucune variable." >&2; \
		echo "  Sans elles, envsubst recopierait les gabarits tels quels et la" >&2; \
		echo "  configuration rendue porterait le nom de la variable au lieu de sa" >&2; \
		echo "  valeur — dsn: mysql:host=<DATABASE_HOST> — et le site tombe." >&2; \
		echo "" >&2; \
		echo "  Cette commande se lance en developpement, dans le conteneur, ou" >&2; \
		echo "  etc/<profil>/.env est monte sur ce chemin :" >&2; \
		echo "" >&2; \
		echo "    make configure PROFILE=musiqueapproximative.localhost" >&2; \
		echo "" >&2; \
		echo "  Elle ne se lance PAS sur le serveur : voir" >&2; \
		echo "  docs/modules/ROOT/pages/fichiers-de-configuration.adoc" >&2; \
		exit 1; \
	fi; \
	for DIST in `find . -iname '*-dist'`; do \
		cat $${DIST} | envsubst "`echo "'$${VARS}'"`" > `dirname $${DIST}`/`basename $${DIST} -dist`; \
	done
