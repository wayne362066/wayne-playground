COMPOSE := docker compose
PHP := $(COMPOSE) exec php
NODE := $(COMPOSE) exec node

.PHONY: up down build restart logs ps shell composer-install npm-install key-generate migrate migrate-fresh seed test queue-restart

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f --tail=200

ps:
	$(COMPOSE) ps

shell:
	$(PHP) sh

composer-install:
	$(PHP) composer install

npm-install:
	$(NODE) npm install

key-generate:
	$(PHP) php artisan key:generate --show

migrate:
	$(PHP) php artisan migrate

migrate-fresh:
	$(PHP) php artisan migrate:fresh --seed

seed:
	$(PHP) php artisan db:seed

test:
	$(PHP) php artisan test

queue-restart:
	$(PHP) php artisan queue:restart
