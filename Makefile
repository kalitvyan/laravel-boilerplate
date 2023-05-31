export COMPOSE_PROJECT_NAME=boilerplate

ifndef IS_DOCKER_CONTAINER
	IS_DOCKER_CONTAINER = 0
endif

HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
PHP_USER := -u www-data
INTERACTIVE := $(shell [ -t 0 ] && echo 1)
PROJECT_NAME := -p ${COMPOSE_PROJECT_NAME}

ERROR_ONLY_FOR_HOST = @printf "\033[33mThis command for host only.\033[39m\n"

ifneq ($(INTERACTIVE), 1)
	OPTION_T := -T
endif

help: ## Shows available commands with description.
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

build: ## Build dev environment.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose -f docker-compose.yml build
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start dev environment.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose -f docker-compose.yml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop dev environment.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose -f docker-compose.yml $(PROJECT_NAME) down
else
	$(ERROR_ONLY_FOR_HOST)
endif

restart: stop start ## Stop and start dev environment.

env-dev: ## Creates config for dev environment.
	cp ./.env.dev ./.env

exec-bash:
ifeq ($(IS_DOCKER_CONTAINER), 1)
	@bash -c "$(cmd)"
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) laravel bash -c "$(cmd)"
endif

composer-install-no-dev: ## Installs composer no-dev dependencies.
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-dev"

composer-install: ## Installs composer dependencies.
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader"

composer-update: ## Updates composer dependencies.
	@make exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"
