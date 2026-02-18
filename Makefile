.DEFAULT_GOAL := help

help: ## List all available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers in detached mode
	docker compose up -d

down: ## Stop and remove containers
	docker compose down

build: ## Build or rebuild service images
	docker compose build

restart: ## Restart the app container
	docker compose restart app

shell: ## Open a bash shell in the app container
	docker compose exec app bash

logs: ## Tail app container logs
	docker compose logs -f app

migrate: ## Run database migrations
	docker compose exec app php artisan migrate --force

fresh: ## Drop all tables and re-run migrations
	docker compose exec app php artisan migrate:fresh --force

test: ## Run the test suite against the test database
	docker compose exec app php artisan test --compact --configuration phpunit.docker.xml

setup: ## First-time setup: copy .env, build, install deps, generate key, migrate
	@if [ ! -f .env ]; then cp .env.example .env; echo "Copied .env.example -> .env"; fi
	docker compose up -d --build
	docker compose exec app composer install --no-interaction
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate:fresh --force

clean: ## Stop containers and remove volumes
	docker compose down -v
