.PHONY: run

DOCKER_COMPOSE ?= docker compose
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "dev"

reset:
	@make -s clean
	@rm -rf vendor composer.lock node_modules var/cache var/log
	@rm -rf compose.override.yml

install:
	yarn install || true
	#yarn run || true
	@make init
	@make install-database
	@make frontend-clear
	echo "Setup completed! You can now access the application at http://localhost"

install-database:
	@make database-init
	@make load-fixtures
	@make load-demo-content
	echo "Data installation completed!"

# Run QA tools
publish:
	@make phpstan
	@make ecs
	@make phpunit
	@make behat

init:
	@make -s docker-compose-check
	@make -s ddev-check
	@if [ ! -e compose.override.yml ]; then \
		cp compose.override.dist.yml compose.override.yml; \
	fi
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php composer install --no-interaction --no-scripts
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php composer require symfony/maker-bundle --dev --no-interaction --no-scripts
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) up -d
	# Set up Sylius Test Application with preview route firewall
	rm vendor/sylius/test-application/config/packages/security.yaml || true
	cp config/packages/tpl/_security.yaml vendor/sylius/test-application/config/packages/security.yaml

frontend-clear:
	cp -R assets/controllers/* vendor/sylius/test-application/node_modules/@agence-adeliom/sylius-happy-cms-plugin/controllers
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs "cd vendor/sylius/test-application && yarn install" || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs "cd vendor/sylius/test-application && yarn run build" || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console assets:install
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php ln -sf /srv/sylius/public/bundles vendor/sylius/test-application/public


plugin-asset-watch:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console assets:install --symlink
	yarn run watch

plugin-asset-build:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console assets:install --symlink
	yarn run build

run:
	@make -s up

debug:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) -f compose.yml -f compose.override.yml -f compose.debug.yml up -d

up:
	@make -s ddev-check
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) up -d

down:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) down

clean:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) down -v

php-shell:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) exec php sh

node-shell:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs sh

node-watch:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs "npm run watch"

docker-compose-check:
	@$(DOCKER_COMPOSE) version >/dev/null 2>&1 || (echo "Please install docker compose binary or set DOCKER_COMPOSE=\"docker-compose\" for legacy binary" && exit 1)
	@echo "You are using \"$(DOCKER_COMPOSE)\" binary"
	@echo "Current version is \"$$($(DOCKER_COMPOSE) version)\""

ddev-check:
	@$(DDEV) poweroff >/dev/null 2>&1 && echo "You are using ddev, we need to stop it to free needed ports" || (echo "DDEV is not installed, no port conflicts detected")

database-init:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:drop -n --force --if-exists
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:create -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i php rm vendor/sylius/test-application/migrations/*.php || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:migrate -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:diff -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:migrate -n

database-reset:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:drop -n --force --if-exists
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:create -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:migrate -n

load-fixtures:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console sylius:fixtures:load -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console dbal:run-sql 'INSERT INTO sylius_channel_locales SET locale_id = 2, channel_id = 1' -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console dbal:run-sql 'INSERT INTO sylius_channel_locales SET locale_id = 3, channel_id = 1' -n

load-demo-content:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console happycms:starter:create-demo-pages -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console happycms:starter:create-demo-menu
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console happycms:migrate:content-to-blocks

phpstan:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpstan analyse -c phpstan.neon

ecs:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/ecs check src

ecs-fix:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/ecs check src --fix --clear-cache

phpunit:
	@ENV=test DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpunit

behat:
	@ENV=test DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/behat
