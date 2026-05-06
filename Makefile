.PHONY: run

DOCKER_COMPOSE ?= docker compose
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "dev"

# Auto-detect whether the PHP container is already running.
# If yes, use "exec" (zero container startup overhead).
# If no, fall back to "run --rm" (creates an ephemeral container).
PHP_RUNNING := $(shell $(DOCKER_COMPOSE) ps --status=running php 2>/dev/null | grep -c 'php')
ifeq ($(strip $(PHP_RUNNING)),0)
  PHP_CMD      = ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php
  PHP_CMD_TEST = ENV=test DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php
  PHP_CMD_ROOT = ENV=test DOCKER_USER=root $(DOCKER_COMPOSE) run --rm php
else
  PHP_CMD      = $(DOCKER_COMPOSE) exec -T php
  PHP_CMD_TEST = $(DOCKER_COMPOSE) exec -T -e APP_ENV=test php
  PHP_CMD_ROOT = $(DOCKER_COMPOSE) exec -T -u root -e APP_ENV=test php
endif

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
	@make configure-preview-mode
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) up -d

frontend-clear:
#	cp -R assets/controllers/* vendor/sylius/test-application/node_modules/@agence-adeliom/sylius-happy-cms-plugin/controllers
#	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs "cd vendor/sylius/test-application && yarn install" || true
#	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs "cd vendor/sylius/test-application && yarn run build" || true
	@$(PHP_CMD) vendor/bin/console assets:install
	@$(PHP_CMD) ln -sf /srv/sylius/public/bundles vendor/sylius/test-application/public

configure-preview-mode:
	# Set up Sylius Test Application with preview route firewall
	rm vendor/sylius/test-application/config/packages/security.yaml || true
	cp config/packages/tpl/_security.yaml vendor/sylius/test-application/config/packages/security.yaml

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

node-dev:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs "npm run dev"

node-build:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs "npm run build"

node-watch:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs "npm run watch"

docker-compose-check:
	@$(DOCKER_COMPOSE) version >/dev/null 2>&1 || (echo "Please install docker compose binary or set DOCKER_COMPOSE=\"docker-compose\" for legacy binary" && exit 1)
	@echo "You are using \"$(DOCKER_COMPOSE)\" binary"
	@echo "Current version is \"$$($(DOCKER_COMPOSE) version)\""

ddev-check:
	@$(DDEV) poweroff >/dev/null 2>&1 && echo "You are using ddev, we need to stop it to free needed ports" || (echo "DDEV is not installed, no port conflicts detected")

database-init:
	@$(PHP_CMD) vendor/bin/console doctrine:database:drop -n --force --if-exists
	@$(PHP_CMD) vendor/bin/console doctrine:database:create -n
	@$(PHP_CMD) rm -f vendor/sylius/test-application/migrations/*.php || true
	@$(PHP_CMD) vendor/bin/console doctrine:migrations:migrate -n
	@$(PHP_CMD) vendor/bin/console doctrine:migrations:diff -n || true
	@$(PHP_CMD) vendor/bin/console doctrine:migrations:migrate -n || true

database-reset:
	@$(PHP_CMD) vendor/bin/console doctrine:database:drop -n --force --if-exists
	@$(PHP_CMD) vendor/bin/console doctrine:database:create -n
	@$(PHP_CMD) vendor/bin/console doctrine:migrations:migrate -n

load-fixtures:
	@$(PHP_CMD) vendor/bin/console sylius:fixtures:load -n
	@$(PHP_CMD) vendor/bin/console dbal:run-sql 'INSERT INTO sylius_channel_locales SET locale_id = 2, channel_id = 1' -n
	@$(PHP_CMD) vendor/bin/console dbal:run-sql 'INSERT INTO sylius_channel_locales SET locale_id = 3, channel_id = 1' -n

load-demo-content:
	@$(PHP_CMD) vendor/bin/console happycms:starter:create-demo-pages -n
	@$(PHP_CMD) vendor/bin/console happycms:starter:create-demo-menu
	@$(PHP_CMD) vendor/bin/console happycms:migrate:content-to-blocks

phpstan:
	@$(PHP_CMD) vendor/bin/phpstan analyse -c phpstan.neon

ecs:
	@$(PHP_CMD) vendor/bin/ecs check src

ecs-fix:
	@$(PHP_CMD) vendor/bin/ecs check src --fix --clear-cache

phpunit:
	rm -rf tests/Entity
	rm -rf tests/Repository
	rm -rf tests/Admin
	rm -rf tests/Controller
	@$(PHP_CMD_TEST) vendor/bin/phpunit

behat:
	@$(PHP_CMD_TEST) vendor/bin/console doctrine:database:drop -n --force --if-exists
	@$(PHP_CMD_TEST) vendor/bin/console doctrine:database:create -n
	@$(PHP_CMD_TEST) vendor/bin/console doctrine:migrations:migrate -n
	@$(PHP_CMD_ROOT) vendor/bin/behat
