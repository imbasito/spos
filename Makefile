setup:
	@make docker-up-build
	@make composer-install
	@make set-permissions
	@make setup-env
	@make generate-key
	@make migrate-fresh-seed
	@make npm-install-build
	@make npm-run-dev

docker-stop:
	docker compose stop

docker-up-build:
	docker compose up -d --build

composer-install:
	docker compose exec app sh -lc "composer install"

composer-update:
	docker compose exec app sh -lc "composer update"

set-permissions:
	docker compose exec app sh -lc "chmod -R 777 /var/www/storage"
	docker compose exec app sh -lc "chmod -R 777 /var/www/bootstrap"

setup-env:
	docker compose exec app sh -lc "cp .env.docker .env"

npm-install-build:
	docker compose exec node sh -lc "npm install"
	docker compose exec node sh -lc "npm run build"

npm-run-dev:
	docker compose exec node sh -lc "npm run dev -- --host 0.0.0.0 --port 5173"

npm-run-build:
	docker compose exec node sh -lc "npm run build"

generate-key:
	docker compose exec app sh -lc "php artisan key:generate"

migrate-fresh-seed:
	docker compose exec app sh -lc "php artisan migrate:fresh --seed"
