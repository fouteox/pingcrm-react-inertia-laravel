# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
NPM      = $(PHP_CONT) npm
LARAVEL  = $(PHP) artisan

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh dep composer vendor npm dev a cc test

## —— 🎵 🐳 The Laravel Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

dep: ## Deploy the application
	DOMAIN_NAME=pingcrm-react.com SERVER_NAME=:80 IMAGES_PREFIX=pingcrm ./deploy.sh

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req laravel/breeze --dev'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Npm 🟩 ——————————————————————————————————————————————————————————————
npm: ## Run npm, pass the parameter "c=" to run a given command, example: make npm c='install'
	@$(eval c ?=)
	@$(NPM) $(c)

dev: ## Run npm run dev
	@$(NPM) run dev

## —— Laravel 🟥 ———————————————————————————————————————————————————————————————
a: ## List all Laravel commands or pass the parameter "c=" to run a given command, example: make a c=about
	@$(eval c ?=)
	@$(LARAVEL) $(c)

cc: c=cache:clear ## Clear the cache
cc: a
