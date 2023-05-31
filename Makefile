export COMPOSE_PROJECT_NAME=boilerplate

ifndef IS_DOCKER_CONTAINER
	IS_DOCKER_CONTAINER = 0
endif

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
	docker compose -f docker-compose.yml build
else
	$(ERROR_ONLY_FOR_HOST)
endif

start: ## Start dev environment.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	docker compose -f docker-compose.yml $(PROJECT_NAME) up -d
else
	$(ERROR_ONLY_FOR_HOST)
endif

stop: ## Stop dev environment.
ifeq ($(IS_DOCKER_CONTAINER), 0)
	docker compose -f docker-compose.yml $(PROJECT_NAME) down
else
	$(ERROR_ONLY_FOR_HOST)
endif

restart: stop start ## Stop and start dev environment.

env-dev: ## Creates config for dev environment.
	cp ./.env.dev ./.env
