SHELL := /bin/bash
.DEFAULT_GOAL := help

-include .make.env

COMPOSE := docker compose -f infra/docker/compose/compose.yaml

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

# -T отключает TTY у compose exec в неинтерактивной среде (CI);
# -it, наоборот, нужен docker run, когда терминал есть
EXEC_TTY := $(shell [ -t 0 ] || echo -T)
RUN_TTY  := $(shell [ -t 0 ] && echo -it)

API_EXEC := $(COMPOSE) exec $(EXEC_TTY) api
API_RUN  := $(COMPOSE) run --rm --no-deps $(EXEC_TTY) api
WEB_EXEC := $(COMPOSE) exec $(EXEC_TTY) web

NODE_IMAGE ?= node:24-alpine
NODE_PASSWD := $(CURDIR)/infra/docker/node/passwd

# UID хоста отсутствует в /etc/passwd образа, а Node зовёт os.userInfo()
$(NODE_PASSWD):
	@rm -rf $@
	@mkdir -p $(dir $@)
	@printf 'root:x:0:0:root:/root:/bin/sh\napp:x:$(HOST_UID):$(HOST_GID):app:/tmp:/bin/sh\n' > $@

# Для команд вне поднятого стека: свежий клон, генерация клиента, CI
PNPM = docker run --rm $(RUN_TTY) \
	-u $(HOST_UID):$(HOST_GID) \
	-e HOME=/tmp \
	-e COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
	-v $(CURDIR):/repo \
	-v $(NODE_PASSWD):/etc/passwd:ro \
	-w /repo \
	$(NODE_IMAGE) corepack pnpm

REDOCLY := docker run --rm \
	-u $(HOST_UID):$(HOST_GID) \
	-e HOME=/tmp \
	-e REDOCLY_TELEMETRY=off \
	-v $(CURDIR)/docs/api:/spec \
	-w /spec \
	redocly/cli:latest

.PHONY: help
help: ## Show this help
	@awk 'BEGIN {FS = ":.*## "} /^##@/ {printf "\n\033[1m%s\033[0m\n", substr($$0, 5)} /^[a-zA-Z0-9_-]+:.*## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

##@ Environment

.PHONY: init
init: $(NODE_PASSWD) ## First run: env, deps, build, up, migrate
	@test -f apps/api/.env || cp apps/api/.env.example apps/api/.env
	@test -f apps/web/.env.local || cp apps/web/.env.example apps/web/.env.local
	$(COMPOSE) build
	$(API_RUN) composer install
	@grep -q '^APP_KEY=base64' apps/api/.env || $(API_RUN) php artisan key:generate
	$(PNPM) install
	$(COMPOSE) up -d
	$(API_EXEC) php artisan migrate --force

.PHONY: up
up: $(NODE_PASSWD) ## Start stack
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop stack
	$(COMPOSE) down

.PHONY: restart
restart: down up ## Recreate containers (picks up .env and compose changes)

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
shell: ## Shell in the api container
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

.PHONY: psql
psql: ## psql into the app database
	$(COMPOSE) exec postgres psql -U app -d app

.PHONY: octane-reload
octane-reload: ## Reload Octane workers
	$(API_EXEC) php artisan octane:reload

.PHONY: horizon-restart
horizon-restart: ## Gracefully restart Horizon (after code changes)
	$(API_EXEC) php artisan horizon:terminate

.PHONY: relay-restart
relay-restart: ## Restart the outbox relay (after code changes)
	$(COMPOSE) restart outbox-relay

##@ Frontend

.PHONY: pnpm
pnpm: $(NODE_PASSWD) ## Run pnpm outside the stack: make pnpm c="install"
	$(PNPM) $(c)

.PHONY: web-shell
web-shell: ## Shell in the web container
	$(WEB_EXEC) sh

.PHONY: web-logs
web-logs: ## Follow web logs
	$(COMPOSE) logs -f --tail=200 web

.PHONY: shadcn
shadcn: ## Add shadcn components: make shadcn c="button input"
	$(WEB_EXEC) pnpm dlx shadcn@latest add $(c)

.PHONY: web-check
web-check: ## Typecheck and lint the frontend
	$(WEB_EXEC) pnpm typecheck
	$(WEB_EXEC) pnpm lint

.PHONY: web-test
web-test: ## Run frontend tests
	$(WEB_EXEC) pnpm test

##@ API contract

.PHONY: spec-lint
spec-lint: ## Lint the OpenAPI spec
	$(REDOCLY) lint

.PHONY: spec
spec: spec-lint ## Lint and bundle the spec into docs/api/dist/openapi.yaml
	$(REDOCLY) bundle main --output dist/openapi.yaml

.PHONY: spec-docs
spec-docs: spec ## Build static HTML docs into docs/api/dist/index.html
	$(REDOCLY) build-docs dist/openapi.yaml --output dist/index.html

.PHONY: client
client: spec $(NODE_PASSWD) ## Regenerate the typed API client from the spec
	$(PNPM) --filter @laravel-boilerplate/api-client generate

##@ Quality

.PHONY: autoload-check
autoload-check: ## Verify PSR-4 compliance
	$(API_EXEC) composer autoload-check

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
test: spec ## API tests: make test f="--filter=Health"
	$(API_EXEC) composer test -- $(f)

.PHONY: qa
qa: spec ## All API checks
	$(API_EXEC) composer qa

.PHONY: check
check: qa web-check ## All checks, API and frontend

##@ Production image

REGISTRY ?=
IMAGE_NAME ?= laravel-boilerplate-api
TAG ?= $(shell git rev-parse --short HEAD 2>/dev/null || echo dev)
PLATFORM ?= linux/amd64

API_IMAGE_PROD := $(if $(REGISTRY),$(REGISTRY)/,)$(IMAGE_NAME):$(TAG)
API_IMAGE_LATEST := $(if $(REGISTRY),$(REGISTRY)/,)$(IMAGE_NAME):latest

COMPOSE_PROD := API_IMAGE=$(API_IMAGE_PROD) docker compose -f infra/docker/compose/compose.prod.yaml

.PHONY: prod-image
prod-image: ## Build the production image (REGISTRY=ghcr.io/you to tag for a registry)
	docker build \
		-f infra/docker/api/Dockerfile \
		--target prod \
		--platform $(PLATFORM) \
		-t $(API_IMAGE_PROD) \
		-t $(API_IMAGE_LATEST) \
		.
	@echo "built $(API_IMAGE_PROD)"

.PHONY: prod-image-nocache
prod-image-nocache: ## Rebuild the production image ignoring the layer cache
	docker build \
		-f infra/docker/api/Dockerfile \
		--target prod \
		--platform $(PLATFORM) \
		--no-cache \
		--pull \
		-t $(API_IMAGE_PROD) \
		-t $(API_IMAGE_LATEST) \
		.
	@echo "built $(API_IMAGE_PROD) (no cache)"

.PHONY: prod-image-multiarch
prod-image-multiarch: ## Build and push a multi-arch image (requires REGISTRY)
	@test -n "$(REGISTRY)" || { echo "REGISTRY is not set"; exit 1; }
	docker buildx build \
		-f infra/docker/api/Dockerfile \
		--target prod \
		--platform linux/amd64,linux/arm64 \
		-t $(API_IMAGE_PROD) \
		-t $(API_IMAGE_LATEST) \
		--push \
		.

.PHONY: prod-login
prod-login: ## Log in to GHCR (expects GHCR_TOKEN with write:packages)
	@test -n "$(GHCR_TOKEN)" || { echo "GHCR_TOKEN is not set"; exit 1; }
	@echo "$(GHCR_TOKEN)" | docker login ghcr.io -u kalitvyan --password-stdin

.PHONY: prod-push
prod-push: ## Push the image (requires REGISTRY)
	@test -n "$(REGISTRY)" || { echo "REGISTRY is not set: make prod-push REGISTRY=ghcr.io/kalitvyan"; exit 1; }
	docker push $(API_IMAGE_PROD)
	docker push $(API_IMAGE_LATEST)

.PHONY: prod-key
prod-key: ## Generate an APP_KEY for the prod-like stack
	@docker run --rm $(API_IMAGE_PROD) artisan key:generate --show

.PHONY: prod-up
prod-up: ## Start the prod-like stack
	$(COMPOSE_PROD) up -d

.PHONY: prod-down
prod-down: ## Stop the prod-like stack and remove volumes
	$(COMPOSE_PROD) down -v

.PHONY: prod-logs
prod-logs: ## Logs: make prod-logs s=api
	$(COMPOSE_PROD) logs -f --tail=200 $(s)

.PHONY: prod-shell
prod-shell: ## Shell in the running prod api container
	$(COMPOSE_PROD) exec api sh
