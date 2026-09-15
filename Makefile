SHELL := /bin/bash
.DEFAULT_GOAL := help

PROJECT   := laravel-boilerplate
API_DIR   := apps/api
API_IMAGE := $(PROJECT)-api:dev

UID := $(shell id -u)
GID := $(shell id -g)
TTY := $(shell [ -t 0 ] && echo -it)

# Временный запуск через docker run; на этапе 2 заменим на docker compose exec
DOCKER_API = docker run --rm $(TTY) \
	-u $(UID):$(GID) \
	-e COMPOSER_HOME=/tmp/composer \
	-e HOME=/tmp \
	-v $(CURDIR)/$(API_DIR):/app \
	-w /app \
	$(API_IMAGE)

.PHONY: help
help: ## Show this help
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ API

.PHONY: api-image
api-image: ## Build API dev image
	docker build -f infra/docker/api/Dockerfile --target dev -t $(API_IMAGE) infra/docker/api

.PHONY: composer
composer: ## Run composer: make composer c="require vendor/pkg"
	$(DOCKER_API) composer $(c)

.PHONY: artisan
artisan: ## Run artisan: make artisan c="route:list"
	$(DOCKER_API) php artisan $(c)

.PHONY: api-shell
api-shell: ## Shell in API container
	$(DOCKER_API) bash
