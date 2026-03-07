.PHONY: up down restart logs ps shell composer-install key migrate seed fresh test lint format stan quality health

up:
	docker compose up -d --build

down:
	docker compose down --remove-orphans

restart:
	docker compose down --remove-orphans
	docker compose up -d --build

logs:
	docker compose logs -f --tail=200

ps:
	docker compose ps

shell:
	docker compose exec app sh

composer-install:
	docker compose exec app composer install

key:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

test:
	docker compose exec app php artisan test

lint:
	docker compose exec app vendor/bin/pint --test

format:
	docker compose exec app vendor/bin/pint

stan:
	docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M

quality:
	docker compose exec app composer quality

health:
	curl -fsS http://localhost:8080/api/v1/health
