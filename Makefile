export COMPOSE_PROJECT_NAME=boilerplate

ifndef INSIDE_DOCKER_CONTAINER
	INSIDE_DOCKER_CONTAINER = 0
endif

HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)

PROJECT_NAME := -p ${COMPOSE_PROJECT_NAME}

ERROR_ONLY_FOR_HOST = @printf "\033[33mThis command for host machine\033[39m\n"

help: ## Shows available commands with description
	@echo "\033[34mList of available commands:\033[39m"
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-27s[0m %s\n", $$1, $$2}'

build: ## Build dev environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose -f docker-compose.yml build
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start dev environment
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@HOST_UID=$(HOST_UID) HOST_GID=$(HOST_GID) docker compose -f docker-compose.yml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop dev environment
	docker compose -f docker-compose.yml $(PROJECT_NAME) down

restart: stop start ## Stop and start dev environment

logs: ## Shows logs from the laravel container. Use ctrl+c in order to exit
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-laravel
else
	$(ERROR_ONLY_FOR_HOST)
endif

logs-nginx: ## Shows logs from the nginx container. Use ctrl+c in order to exit
ifeq ($(INSIDE_DOCKER_CONTAINER), 0)
	@docker logs -f ${COMPOSE_PROJECT_NAME}-nginx
else
	$(ERROR_ONLY_FOR_HOST)
endif
