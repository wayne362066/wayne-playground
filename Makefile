COMPOSE := docker compose
PHP := $(COMPOSE) exec php
NODE := $(COMPOSE) exec node

.PHONY: help up down build restart logs ps shell composer-install npm-install key-generate migrate migrate-fresh seed seed-permissions seed-admin test queue-restart

help:
	@echo "Wayne's Playground 可用指令："
	@echo "  make up                啟動 Docker 服務"
	@echo "  make down              停止 Docker 服務"
	@echo "  make build             建置 Docker images"
	@echo "  make restart           重新啟動 Docker 服務"
	@echo "  make logs              持續顯示最近 200 行服務日誌"
	@echo "  make ps                顯示 Docker 服務狀態"
	@echo "  make shell             進入 PHP container shell"
	@echo "  make composer-install  安裝 PHP dependencies"
	@echo "  make npm-install       安裝 frontend dependencies"
	@echo "  make key-generate      產生 Laravel application key"
	@echo "  make migrate           執行尚未套用的 migrations"
	@echo "  make migrate-fresh     重建資料庫並執行完整 Seeder（會清除資料）"
	@echo "  make seed              執行完整 DatabaseSeeder（包含權限同步）"
	@echo "  make seed-permissions  掃描各模組 permissions.php 並同步權限"
	@echo "  make seed-admin        建立／更新開發管理員 admin（僅限非 production）"
	@echo "  make test              執行 Laravel tests"
	@echo "  make queue-restart     重新啟動 Laravel queue workers"

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

seed-permissions:
	$(PHP) php artisan db:seed --class=PermissionSeeder

seed-admin:
	$(PHP) php artisan db:seed --class=AdminAccountSeeder

test:
	$(PHP) php artisan test

queue-restart:
	$(PHP) php artisan queue:restart
