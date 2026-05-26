.PHONY: init up down restart build shell migrate fresh seed test

# Development
init: build up composer-install key-generate migrate
	@echo "merchant-platform is ready at http://localhost:8090"

up:
	docker compose up -d

down:
	docker compose down

restart: down up

build:
	docker compose build

# Production
init-prod:
	docker compose -f docker-compose.prod.yml build
	docker compose -f docker-compose.prod.yml up -d

up-prod:
	docker compose -f docker-compose.prod.yml up -d

down-prod:
	docker compose -f docker-compose.prod.yml down

# Laravel
shell:
	docker compose exec app bash

composer-install:
	docker compose exec app composer install

key-generate:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

test:
	docker compose exec app php artisan test

tinker:
	docker compose exec app php artisan tinker

cache-clear:
	docker compose exec app php artisan optimize:clear

# Logs
logs:
	docker compose logs -f app

logs-nginx:
	docker compose logs -f nginx
