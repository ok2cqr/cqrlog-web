SHELL := /bin/sh

COMPOSE ?= $(shell if docker compose version >/dev/null 2>&1; then printf '%s' 'docker compose'; elif docker-compose --version >/dev/null 2>&1; then printf '%s' 'docker-compose'; else printf '%s' 'docker compose'; fi)

DEV_COMPOSE_FILE := docker-compose.yml
PROD_COMPOSE_FILE := docker-compose.prod.yml
PROD_ENV_FILE := .env.prod

.PHONY: help dev dev-down dev-logs prod prod-down prod-logs prod-config test test-backend test-db-reset check-prod-env

help:
	@printf '%s\n' \
		'make dev        Build and start the development stack' \
		'make dev-down   Stop the development stack' \
		'make dev-logs   Tail development logs' \
		'make test       Reset the local test database and run backend tests' \
		'make prod       Build and start the production stack' \
		'make prod-down  Stop the production stack' \
		'make prod-logs  Tail production logs' \
		'make prod-config Render the production Compose config'

dev:
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) up -d --build

dev-down:
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) down

dev-logs:
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) logs -f --tail=100

test: test-backend

test-backend: test-db-reset
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) up -d app
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) exec -T app vendor/bin/phpunit

test-db-reset:
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) up -d db
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) exec -T db sh -lc 'mariadb -uroot -p"$$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS cqrlog002_test; CREATE DATABASE cqrlog002_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'
	$(COMPOSE) -f $(DEV_COMPOSE_FILE) exec -T db sh -lc 'mariadb -uroot -p"$$MARIADB_ROOT_PASSWORD" cqrlog002_test' < schema.sql

prod: check-prod-env
	$(COMPOSE) --env-file $(PROD_ENV_FILE) -f $(PROD_COMPOSE_FILE) up -d --build

prod-down: check-prod-env
	$(COMPOSE) --env-file $(PROD_ENV_FILE) -f $(PROD_COMPOSE_FILE) down

prod-logs: check-prod-env
	$(COMPOSE) --env-file $(PROD_ENV_FILE) -f $(PROD_COMPOSE_FILE) logs -f --tail=100

prod-config: check-prod-env
	$(COMPOSE) --env-file $(PROD_ENV_FILE) -f $(PROD_COMPOSE_FILE) config

check-prod-env:
	@test -f $(PROD_ENV_FILE) || { \
		echo "$(PROD_ENV_FILE) not found. Copy .env.prod.example first."; \
		exit 1; \
	}
