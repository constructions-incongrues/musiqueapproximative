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
	@echo ""
	@echo "  ATTENTION : une migration de schéma doit avoir été exécutée sur la base"
	@echo "  de production AVANT cette synchronisation, faute de quoi tout le site"
	@echo "  lève « Unknown column 'p.track_duration' » — pages, flux et écritures"
	@echo "  de l'administration comprises."
	@echo "  Voir docs/modules/ROOT/pages/deploiement.adoc"
	@echo ""
	PROFILE=$(PROFILE) docker-compose run --rm --entrypoint fixuid php make configure
	# src/vendor est gitignoré mais part bien par rsync, les fichiers
	# d'exclusion étant vides. Sans cette étape le déploiement n'emporte les
	# dépendances que si quelqu'un a pensé à les construire localement.
	PROFILE=$(PROFILE) docker-compose run --rm --entrypoint fixuid php composer install --no-dev --optimize-autoloader
	rsync -avzm $(RSYNC_PARAMETERS) --exclude-from=./etc/$(PROFILE)/rsync/exclude --include-from=./etc/$(PROFILE)/rsync/include -e "ssh -p $$RSYNC_SSH_PORT" "$$RSYNC_LOCAL_PATH" "$$RSYNC_REMOTE_USER@$$RSYNC_REMOTE_HOST:$$RSYNC_REMOTE_PATH"

start: build ## Démarrage de l'application
	docker-compose up -d

stop: ## Arrêt de l'application
	docker-compose stop

test-init: ## Prépare la base de test — PROFILE=musiqueapproximative.localhost requis
	@test "$(DATABASE_HOST)" = "db" || { \
		echo "test-init vise l'environnement Docker local, or PROFILE=$(PROFILE) pointe sur $(DATABASE_HOST)."; \
		echo "Relancer avec : make test-init PROFILE=musiqueapproximative.localhost"; \
		exit 1; \
	}
	docker-compose exec -T db mysql -u$(DATABASE_USER) -p$(DATABASE_PASSWORD) -e "CREATE DATABASE IF NOT EXISTS $(DATABASE_NAME_TEST) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	docker-compose exec -T php php symfony doctrine:insert-sql --env=test

logs:
	docker-compose logs -f
