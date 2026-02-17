.PHONY: docker-init docker-up docker-down docker-down-clear docker-restart docker-rebuild \
        composer-install composer-update composer-dumpautoload composer \
        lint-all lint-cs-fixer lint-cs-fixer-fix lint-phpstan lint-rector lint-rector-fix \
        test-unit test-integration php-cli-bash php-cli-root clear-cache show-env

# =============================================================================
# Docker commands
# =============================================================================

## Инициализация проекта (первый запуск)
docker-init: docker-up composer-install

## Запуск контейнеров в фоновом режиме
docker-up:
	docker compose up -d

## Остановка контейнеров
docker-down:
	docker compose down

## Остановка контейнеров и удаление volumes
docker-down-clear:
	docker compose down -v

## Перезапуск контейнеров
docker-restart: docker-down docker-up

## Пересборка образов без кэша
docker-rebuild:
	docker compose build --no-cache

# =============================================================================
# Composer commands
# =============================================================================

## Установка зависимостей
composer-install:
	docker compose exec php-cli composer install

## Обновление зависимостей
composer-update:
	docker compose exec php-cli composer update

## Перегенерация autoload
composer-dumpautoload:
	docker compose exec php-cli composer dumpautoload

## Произвольная команда Composer (usage: make composer args="require some/package")
composer:
	docker compose exec php-cli composer $(args)

# =============================================================================
# Linting commands
# =============================================================================

## Запуск всех линтеров
lint-all: lint-cs-fixer lint-phpstan lint-rector

## Проверка стиля кода (dry-run)
lint-cs-fixer:
	docker compose exec php-cli vendor/bin/php-cs-fixer fix --dry-run --diff

## Автоматическое исправление стиля кода
lint-cs-fixer-fix:
	docker compose exec php-cli vendor/bin/php-cs-fixer fix

## Статический анализ PHPStan
lint-phpstan:
	docker compose exec php-cli vendor/bin/phpstan analyse --memory-limit=1G

## Проверка Rector (dry-run)
lint-rector:
	docker compose exec php-cli vendor/bin/rector process --dry-run

## Применение рефакторинга Rector
lint-rector-fix:
	docker compose exec php-cli vendor/bin/rector process

# =============================================================================
# Testing commands
# =============================================================================

## Запуск unit-тестов
test-unit:
	docker compose exec php-cli vendor/bin/phpunit --testsuite=unit --no-coverage

## Запуск интеграционных тестов
test-integration:
	docker compose exec php-cli vendor/bin/phpunit --testsuite=integration --no-coverage

## Запуск всех тестов
test:
	docker compose exec php-cli vendor/bin/phpunit --no-coverage

## Запуск тестов с покрытием
test-coverage:
	docker compose exec php-cli vendor/bin/phpunit --coverage-html coverage

# =============================================================================
# Utility commands
# =============================================================================

## Доступ к shell контейнера PHP
php-cli-bash:
	docker compose exec php-cli bash

## Root-доступ к контейнеру PHP
php-cli-root:
	docker compose exec -u root php-cli bash

## Очистка кэша и временных файлов
clear-cache:
	docker compose exec php-cli rm -rf var/cache/* || true
	docker compose exec php-cli rm -rf .phpunit.result.cache || true
	docker compose exec php-cli rm -rf .php-cs-fixer.cache || true

## Показать переменные окружения
show-env:
	docker compose exec php-cli env | sort

# =============================================================================
# Help
# =============================================================================

## Показать справку
help:
	@echo "Kinescope PHP SDK - Makefile commands"
	@echo ""
	@echo "Docker:"
	@echo "  make docker-init       - Initialize project (build + install deps)"
	@echo "  make docker-up         - Start containers"
	@echo "  make docker-down       - Stop containers"
	@echo "  make docker-restart    - Restart containers"
	@echo "  make docker-rebuild    - Rebuild images without cache"
	@echo ""
	@echo "Composer:"
	@echo "  make composer-install  - Install dependencies"
	@echo "  make composer-update   - Update dependencies"
	@echo "  make composer args=... - Run arbitrary composer command"
	@echo ""
	@echo "Linting:"
	@echo "  make lint-all          - Run all linters"
	@echo "  make lint-cs-fixer     - Check code style (dry-run)"
	@echo "  make lint-cs-fixer-fix - Fix code style"
	@echo "  make lint-phpstan      - Run static analysis"
	@echo ""
	@echo "Testing:"
	@echo "  make test-unit         - Run unit tests"
	@echo "  make test-integration  - Run integration tests"
	@echo "  make test-coverage     - Run tests with coverage"
	@echo ""
	@echo "Utilities:"
	@echo "  make php-cli-bash      - Access PHP container shell"
	@echo "  make clear-cache       - Clear cache files"
