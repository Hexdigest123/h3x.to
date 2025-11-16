-include .env

DB_HOST ?= db
DB_PORT ?= 5432
DB_NAME ?= mvc_db
DB_USER ?= mvc_user
ifdef DB_PASS
DB_PASSWORD ?= $(DB_PASS)
endif
DB_PASSWORD ?= mvc_password

export DB_HOST DB_PORT DB_NAME DB_USER DB_PASS DB_PASSWORD

.PHONY: help build up down restart logs shell db-shell composer-install clean reset-db

help: ## Zeigt diese Hilfe an
		@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Baut die Docker Container
		docker compose build

up: ## Startet die Container
		docker compose up -d

down: ## Stoppt die Container
		docker compose down

restart: ## Startet die Container neu
		docker compose restart

logs: ## Zeigt die Logs
		docker compose logs -f

logs-app: ## Zeigt nur App-Logs
		docker compose logs -f app

logs-nginx: ## Zeigt nur Nginx-Logs
		docker compose logs -f nginx

shell: ## Öffnet eine Shell im App-Container
		docker compose exec app sh

db-shell: ## Öffnet PostgreSQL Shell
		docker compose exec db env PGPASSWORD="$(DB_PASSWORD)" psql -U "$(DB_USER)" -d "$(DB_NAME)"

composer-install: ## Installiert Composer Dependencies
		docker compose exec app composer install

composer-update: ## Updated Composer Dependencies
		docker compose exec app composer update

clean: ## Entfernt alle Container und Volumes
		docker compose down -v

init: build up ## Initialisiert das Projekt (Build + Start)
	@echo "Projekt wurde initialisiert!"
	@echo "App läuft auf: http://localhost:8080"
	@echo "phpPgAdmin: http://localhost:8081"

reset-db: ## Wipes containers/volumes, recreates DB, seeds schema/data, and starts the stack
	@echo "Stopping and removing containers + volumes…"
	docker compose down -v
	@echo "Starting database container…"
	docker compose up -d db
	@echo "Applying db/init.sql seed…"
	sleep 10
	docker compose cp db/init.sql db:/tmp/init.sql
	docker compose exec db env PGPASSWORD="$(DB_PASSWORD)" psql -U "$(DB_USER)" -d "$(DB_NAME)" -f /tmp/init.sql
	@echo "Seeding test admin credentials…"
	docker compose cp db/seed_test_admin.sql db:/tmp/seed_test_admin.sql
	docker compose exec db env PGPASSWORD="$(DB_PASSWORD)" psql -U "$(DB_USER)" -d "$(DB_NAME)" -f /tmp/seed_test_admin.sql
	@echo "Starting full stack…"
	docker compose up -d
	@echo "All set. App on http://localhost:8080"
