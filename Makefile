.DEFAULT_GOAL := help

.PHONY: docker-init docker-up docker-down docker-down-clear docker-restart docker-rebuild \
        composer-install composer-update composer-dumpautoload composer \
        lint-all lint-cs-fixer lint-cs-fixer-fix lint-phpstan lint-rector lint-rector-fix \
        test-unit test-integration test-integration-fast test-integration-download openspec openspec-init openspec-list openspec-list-specs openspec-validate \
        kinescope kinescope-project-list kinescope-project-show kinescope-folder-list kinescope-folder-show kinescope-video-list kinescope-video-show kinescope-video-asset-list \
        php-cli-bash php-cli-root clear-cache show-env

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

## Быстрая часть интеграционных тестов (без скачивания файлов)
test-integration-fast:
	docker compose exec php-cli vendor/bin/phpunit --testsuite=integration --exclude-group=download --no-coverage

## Тяжёлая часть интеграционных тестов (только скачивание файлов; opt-in через TESTS_VIDEO_DOWNLOADER_ENABLED=1)
test-integration-download:
	docker compose exec -e TESTS_VIDEO_DOWNLOADER_ENABLED=1 php-cli vendor/bin/phpunit --testsuite=integration --group=download --no-coverage

## Запуск всех тестов
test:
	docker compose exec php-cli vendor/bin/phpunit --no-coverage

## Запуск тестов с покрытием
test-coverage:
	docker compose exec php-cli vendor/bin/phpunit --coverage-html coverage

# =============================================================================
# OpenSpec commands
# =============================================================================

## Run OpenSpec CLI (usage: make openspec args="show add-video-fetcher")
openspec:
	docker compose run --rm openspec $(args)

## Initialize OpenSpec repository structure and repository-local Codex skills
openspec-init:
	docker compose run --rm openspec init --tools codex --force

## List active OpenSpec changes
openspec-list:
	docker compose run --rm openspec list

## List OpenSpec specifications
openspec-list-specs:
	docker compose run --rm openspec list --specs

## Validate all OpenSpec changes and specs in strict non-interactive mode
openspec-validate:
	docker compose run --rm openspec validate --all --strict --no-interactive

# =============================================================================
# Kinescope CLI commands
# =============================================================================

## Run Kinescope CLI (usage: make kinescope args="kinescope:project:list --format=json")
kinescope:
	docker compose exec php-cli php bin/kinescope $(args)

## List Kinescope projects (usage: make kinescope-project-list args="--format=json")
kinescope-project-list:
	docker compose exec php-cli php bin/kinescope kinescope:project:list $(args)

## Show one Kinescope project (usage: make kinescope-project-show args="<project-id>")
kinescope-project-show:
	docker compose exec php-cli php bin/kinescope kinescope:project:show $(args)

## List Kinescope folders (usage: make kinescope-folder-list args="--project-id=<project-id> --format=json")
kinescope-folder-list:
	docker compose exec php-cli php bin/kinescope kinescope:folder:list $(args)

## Show one Kinescope folder (usage: make kinescope-folder-show args="<folder-id> --project-id=<project-id>")
kinescope-folder-show:
	docker compose exec php-cli php bin/kinescope kinescope:folder:show $(args)

## List Kinescope videos (usage: make kinescope-video-list args="--project-id=<project-id> --format=json")
kinescope-video-list:
	docker compose exec php-cli php bin/kinescope kinescope:video:list $(args)

## Show one Kinescope video (usage: make kinescope-video-show args="<video-id>")
kinescope-video-show:
	docker compose exec php-cli php bin/kinescope kinescope:video:show $(args)

## List sanitized Kinescope video assets (usage: make kinescope-video-asset-list args="<video-id> --format=json")
kinescope-video-asset-list:
	docker compose exec php-cli php bin/kinescope kinescope:video:asset:list $(args)

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
	@echo "  make test-unit                 - Run unit tests"
	@echo "  make test-integration          - Run all integration tests"
	@echo "  make test-integration-fast     - Run integration tests except @group=download"
	@echo "  make test-integration-download - Run only @group=download integration tests"
	@echo "  make test-coverage             - Run tests with coverage"
	@echo ""
	@echo "OpenSpec:"
	@echo "  make openspec-init     - Initialize OpenSpec structure"
	@echo "  make openspec-list     - List active OpenSpec changes"
	@echo "  make openspec-validate - Validate all OpenSpec artifacts"
	@echo ""
	@echo "Kinescope CLI:"
	@echo "  make kinescope args=...              - Run arbitrary Kinescope CLI command"
	@echo "  make kinescope-project-list args=... - List projects"
	@echo "  make kinescope-project-show args=... - Show one project"
	@echo "  make kinescope-folder-list args=...  - List folders"
	@echo "  make kinescope-folder-show args=...  - Show one folder"
	@echo "  make kinescope-video-list args=...   - List videos"
	@echo "  make kinescope-video-show args=...   - Show one video"
	@echo "  make kinescope-video-asset-list args=... - List video assets"
	@echo ""
	@echo "Utilities:"
	@echo "  make php-cli-bash      - Access PHP container shell"
	@echo "  make clear-cache       - Clear cache files"
