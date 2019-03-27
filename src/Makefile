include .env
export $(shell sed 's/=.*//' .env)

help: ## Affiche ce message d'aide
	@for MKFILE in $(MAKEFILE_LIST); do \
		grep -E '^[a-zA-Z0-9\._-]+:.*?## .*$$' $$MKFILE | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; \
	done

configure: ## Configure l'application en fonction de l'environnement
	for DIST in `find . -iname '*-dist'`; do \
		VARS=`cat ./.env | cut -d '=' -f1 | sed 's/^/$$/' | tr "\n" " "`; \
		cat $${DIST} | envsubst "`echo "'$${VARS}'"`" > `dirname $${DIST}`/`basename $${DIST} -dist`; \
	done
