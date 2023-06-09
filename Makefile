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

shell: ## Get bash inside laravel docker container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) laravel bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

shell-root: ## Get bash as root user inside laravel docker container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec $(OPTION_T) laravel bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

shell-pgsql: ## Get bash inside postgresql docker container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec pgsql bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

shell-supervisord: ## Get bash inside supervisord docker container (cron jobs running there, etc...).
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec supervisord bash
else
	$(ERROR_ONLY_FOR_HOST)
endif

logs: ## Shows logs from the laravel container. Use ctrl+c in order to exit.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-laravel
else
	$(ERROR_ONLY_FOR_HOST)
endif

logs-nginx: ## Shows logs from the nginx container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-nginx
else
	$(ERROR_ONLY_FOR_HOST)
endif

logs-pgsql: ## Shows logs from the postgresql container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-postrgesql
else
	$(ERROR_ONLY_FOR_HOST)
endif

logs-supervisord: ## Shows logs from the supervisord container.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-supervisord
else
	$(ERROR_ONLY_FOR_HOST)
endif

exec:
ifeq ($(IS_DOCKER_CONTAINER), 1)
	@$$cmd
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) laravel $$cmd
endif

exec-bash:
ifeq ($(IS_DOCKER_CONTAINER), 1)
	@bash -c "$(cmd)"
else
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose $(PROJECT_NAME) exec $(OPTION_T) $(PHP_USER) laravel bash -c "$(cmd)"
endif

composer-install-no-dev: ## Installs composer no-dev dependencies.
	@make -s exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-dev"

composer-install: ## Installs composer dependencies.
	@make -s exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader"

composer-update: ## Updates composer dependencies.
	@make -s exec-bash cmd="COMPOSER_MEMORY_LIMIT=-1 composer update"

key-gen: ## Sets Laravel application key.
	@make -s exec cmd="php artisan key:generate"

migrate: ## Runs all migrations for main database.
	@make -s exec cmd="php artisan migrate --force"

migrate-with-test: ## Runs all migrations for main/test databases.
	@make -s exec cmd="php artisan migrate --force"
	# @make -s exec cmd="php artisan migrate --force --env=test"

migrate-fresh: ## Drops databases and runs all migrations for the main/test databases.
	@make -s exec cmd="php artisan migrate:fresh"
	# @make -s exec cmd="php artisan migrate:fresh --env=test"

seed: ## Runs all seeds.
	@make -s exec cmd="php artisan db:seed --force"

info: ## Shows PHP and Laravel version in container.
	@make -s exec cmd="php artisan --version"
	@make -s exec cmd="php artisan env"
	@make -s exec cmd="php --version"
