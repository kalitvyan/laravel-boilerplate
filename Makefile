SHELL := /bin/bash
.DEFAULT_GOAL := help

COMPOSE := docker compose -f infra/docker/compose/compose.yaml

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

EXEC_TTY := $(shell [ -t 0 ] || echo -T)

API_EXEC := $(COMPOSE) exec $(EXEC_TTY) api
API_RUN  := $(COMPOSE) run --rm --no-deps $(EXEC_TTY) api

.PHONY: help
help: ## Show this help
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Environment

.PHONY: init
init: ## First run: env, build, deps, up, migrate
	@test -f apps/api/.env || cp apps/api/.env.example apps/api/.env
	$(COMPOSE) build
	$(API_RUN) composer install
	@grep -q '^APP_KEY=base64' apps/api/.env || $(API_RUN) php artisan key:generate
	$(COMPOSE) up -d
	$(API_EXEC) php artisan migrate --force

.PHONY: up
up: ## Start stack
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop stack
	$(COMPOSE) down

.PHONY: destroy
destroy: ## Stop stack and REMOVE volumes
	$(COMPOSE) down -v --remove-orphans

.PHONY: build
build: ## Rebuild images
	$(COMPOSE) build

.PHONY: ps
ps: ## Service status
	$(COMPOSE) ps

.PHONY: logs
logs: ## Logs: make logs s=api
	$(COMPOSE) logs -f --tail=200 $(s)

##@ API

.PHONY: shell
shell: ## Shell in running api container
	$(API_EXEC) bash

.PHONY: composer
composer: ## Composer: make composer c="require vendor/pkg"
	$(API_RUN) composer $(c)

.PHONY: artisan
artisan: ## Artisan: make artisan c="route:list"
	$(API_EXEC) php artisan $(c)

.PHONY: migrate
migrate: ## Run migrations
	$(API_EXEC) php artisan migrate

.PHONY: fresh
fresh: ## Drop all tables and re-run migrations with seeders
	$(API_EXEC) php artisan migrate:fresh --seed

.PHONY: octane-reload
octane-reload: ## Reload Octane workers
	$(API_EXEC) php artisan octane:reload

.PHONY: horizon-restart
horizon-restart: ## Gracefully restart Horizon (after code changes)
	$(API_EXEC) php artisan horizon:terminate

.PHONY: psql
psql: ## psql into app database
	$(COMPOSE) exec postgres psql -U app -d app

##@ Quality

.PHONY: lint
lint: ## Pint check
	$(API_EXEC) composer lint

.PHONY: fix
fix: ## Rector + Pint
	$(API_EXEC) composer fix

.PHONY: rector
rector: ## Rector dry-run
	$(API_EXEC) composer rector

.PHONY: stan
stan: ## PHPStan
	$(API_EXEC) composer stan

.PHONY: deptrac
deptrac: ## Architecture rules
	$(API_EXEC) composer deptrac

.PHONY: test
test: spec ## Tests: make test f="--filter=Health"
	$(API_EXEC) composer test -- $(f)

.PHONY: qa
qa: spec ## All checks
	$(API_EXEC) composer qa

##@ API contract

REDOCLY := docker run --rm \
	-u $(HOST_UID):$(HOST_GID) \
	-e HOME=/tmp \
	-e REDOCLY_TELEMETRY=off \
	-v $(CURDIR)/docs/api:/spec \
	-w /spec \
	redocly/cli:latest

.PHONY: spec-lint
spec-lint: ## Lint OpenAPI spec
	$(REDOCLY) lint

.PHONY: spec
spec: spec-lint ## Lint and bundle spec into docs/api/dist/openapi.yaml
	$(REDOCLY) bundle main --output dist/openapi.yaml

.PHONY: spec-docs
spec-docs: spec ## Build static HTML docs into docs/api/dist/index.html
	$(REDOCLY) build-docs dist/openapi.yaml --output dist/index.html
