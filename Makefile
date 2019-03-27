PROFILE := www.musiqueapproximative.net
RSYNC_PARAMETERS=--dry-run

include ./etc/$(PROFILE)/.env
export $(shell sed 's/=.*//' ./etc/$(PROFILE)/.env)

help: ## Affiche ce message d'aide
	@for MKFILE in $(MAKEFILE_LIST); do \
		grep -E '^[a-zA-Z0-9\._-]+:.*?## .*$$' $$MKFILE | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; \
	done

avatars-fetch:
	rsync -avz -e ssh musiqueapproxima@ftp.pastis-hosting.net:httpdocs/src/web/avatars/ ./src/web/avatars

avatars-compilation:
	cat ./src/web/avatars/*.png | ffmpeg -f image2pipe -i - -pix_fmt yuv420p ./src/web/avatars/vid.mp4

attach: ## Connexion au container hébergeant les sources
	docker-compose run --rm --entrypoint fixuid --label traefik.enable=false php /bin/bash

build: ## Génération de l'image Docker
	docker-compose build

clean: stop ## Suppression des containers de l'application
	docker-compose rm -f

database-import: ## Récupération de la base de donnée de production
	ssh musiqueapproxima@ftp.pastis-hosting.net mysqldump -h127.0.0.1 -umusiqueapproxima -pmusiqueapproxi musiqueapproxima > ./src/data/fixtures/musiqueapproximative.sql

deploy: ## Configure et déploie l'application
	PROFILE=$(PROFILE) docker-compose run --rm --entrypoint fixuid php make configure
	rsync -avzm $(RSYNC_PARAMETERS) --exclude-from=./etc/$(PROFILE)/rsync/exclude --include-from=./etc/$(PROFILE)/rsync/include -e "ssh -p $$RSYNC_SSH_PORT" "$$RSYNC_LOCAL_PATH" "$$RSYNC_REMOTE_USER@$$RSYNC_REMOTE_HOST:$$RSYNC_REMOTE_PATH"

start: build ## Démarrage de l'application
	docker-compose up -d

stop: ## Arrêt de l'application
	docker-compose stop

logs:
	docker-compose logs -f
