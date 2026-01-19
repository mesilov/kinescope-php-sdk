# Техническое задание: PHP SDK для Kinescope API

## 1. Общее описание проекта

### 1.1 Назначение
Разработка PHP SDK для интеграции с Kinescope API - платформой для управления видеоконтентом, которая предоставляет возможности загрузки, транскодирования (до 4K), защиты и ускорения доставки видео.

### 1.2 Целевая аудитория
- PHP разработчики, интегрирующие видео-функциональность в веб-приложения
- Компании, использующие Kinescope для управления видеоконтентом
- Разработчики CMS и корпоративных систем

### 1.3 Цели проекта
- Предоставить удобный объектно-ориентированный интерфейс для работы с Kinescope API
- Обеспечить типобезопасность и автодополнение в IDE
- Упростить процесс аутентификации и обработки ошибок
- Обеспечить поддержку всех основных операций Kinescope API

## 2. Техническая информация об API

### 2.1 Базовые параметры
- **Base URL**: `https://api.kinescope.io`
- **Аутентификация**: Bearer Token (Access Key)
- **Формат данных**: JSON
- **Протокол**: HTTPS

### 2.2 HTTP методы
- `GET` - получение данных
- `POST` - создание ресурсов
- `PUT/PATCH` - обновление ресурсов
- `DELETE` - удаление ресурсов

### 2.3 HTTP коды ответов
- `200` - Success
- `400` - Bad request parameters
- `401` - Unauthorized
- `402` - Payment required
- `403` - Access denied
- `404` - Object not found

### 2.4 Формат аутентификации
```
Authorization: Bearer {api_key}
```

## 3. Требования к функциональности

### 3.1 Основные модули SDK

#### 3.1.1 Client (Клиент API)
- Инициализация с API ключом
- Настройка базового URL
- Управление HTTP заголовками
- Обработка запросов и ответов
- Обработка ошибок и исключений
- Поддержка retry механизма для failed requests
- Логирование запросов (опционально)

#### 3.1.2 Videos (Управление видео)
Методы для работы с видео:
- `list()` - получение списка видео с пагинацией и фильтрацией
- `get($videoId)` - получение информации о конкретном видео
- `create($data)` - создание нового видео
- `update($videoId, $data)` - обновление видео
- `delete($videoId)` - удаление видео
- `upload($videoId, $filePath)` - загрузка видеофайла
- `getUploadUrl($videoId)` - получение URL для загрузки

#### 3.1.3 Projects (Управление проектами)
Методы для работы с проектами:
- `list()` - получение списка проектов
- `get($projectId)` - получение информации о проекте
- `create($data)` - создание проекта
- `update($projectId, $data)` - обновление проекта
- `delete($projectId)` - удаление проекта

#### 3.1.4 Folders (Управление папками)
Методы для работы с папками:
- `list($projectId)` - получение списка папок в проекте
- `get($folderId)` - получение информации о папке
- `create($projectId, $data)` - создание папки
- `update($folderId, $data)` - обновление папки
- `delete($folderId)` - удаление папки

#### 3.1.5 Webhooks (Управление вебхуками)
Методы для работы с webhooks:
- `list()` - получение списка webhooks
- `get($webhookId)` - получение информации о webhook
- `create($data)` - создание webhook
- `update($webhookId, $data)` - обновление webhook
- `delete($webhookId)` - удаление webhook
- `verify($payload, $signature)` - проверка подписи webhook

### 3.2 Модели данных

Создание классов для типизации данных:
- `Video` - модель видео
- `Project` - модель проекта
- `Folder` - модель папки
- `Webhook` - модель webhook
- `PaginatedResponse` - модель для пагинированных ответов
- `UploadUrl` - модель для URL загрузки

### 3.3 Обработка исключений

Иерархия исключений:
```
KinescopeException (базовое исключение)
├── AuthenticationException (401)
├── PaymentRequiredException (402)
├── ForbiddenException (403)
├── NotFoundException (404)
├── BadRequestException (400)
├── ValidationException (валидация данных)
├── NetworkException (сетевые ошибки)
└── RateLimitException (превышение лимитов)
```

## 4. Архитектура SDK

### 4.1 Структура проекта
```
src/
├── Client.php                 # Основной клиент API
├── Configuration.php          # Конфигурация SDK
├── Resources/
│   ├── AbstractResource.php   # Базовый класс для ресурсов
│   ├── Videos.php             # Ресурс для работы с видео
│   ├── Projects.php           # Ресурс для работы с проектами
│   ├── Folders.php            # Ресурс для работы с папками
│   └── Webhooks.php           # Ресурс для работы с webhooks
├── Models/
│   ├── Video.php
│   ├── Project.php
│   ├── Folder.php
│   ├── Webhook.php
│   ├── PaginatedResponse.php
│   └── UploadUrl.php
├── Exceptions/
│   ├── KinescopeException.php
│   ├── AuthenticationException.php
│   ├── PaymentRequiredException.php
│   ├── ForbiddenException.php
│   ├── NotFoundException.php
│   ├── BadRequestException.php
│   ├── ValidationException.php
│   ├── NetworkException.php
│   └── RateLimitException.php
├── Http/
│   ├── HttpClient.php         # HTTP клиент (обертка над Guzzle)
│   ├── Request.php            # Класс запроса
│   └── Response.php           # Класс ответа
└── Utils/
    ├── Validator.php          # Валидация данных
    └── Logger.php             # Логирование (опционально)
```

### 4.2 Пример использования SDK

```php
<?php

use Kinescope\Client;
use Kinescope\Exceptions\KinescopeException;

// Инициализация клиента
$client = new Client('your-api-key');

// Альтернативный способ с конфигурацией
$client = new Client([
    'api_key' => 'your-api-key',
    'base_url' => 'https://api.kinescope.io',
    'timeout' => 30,
    'retry_attempts' => 3,
]);

try {
    // Получение списка видео
    $videos = $client->videos()->list([
        'page' => 1,
        'per_page' => 20,
        'project_id' => 'project-id',
    ]);

    foreach ($videos->getData() as $video) {
        echo $video->getTitle() . PHP_EOL;
    }

    // Создание нового видео
    $video = $client->videos()->create([
        'title' => 'My Video',
        'description' => 'Video description',
        'project_id' => 'project-id',
        'folder_id' => 'folder-id',
    ]);

    // Получение URL для загрузки
    $uploadUrl = $client->videos()->getUploadUrl($video->getId());

    // Загрузка файла
    $client->videos()->upload($video->getId(), '/path/to/video.mp4');

    // Обновление видео
    $client->videos()->update($video->getId(), [
        'title' => 'Updated Title',
    ]);

    // Получение конкретного видео
    $video = $client->videos()->get('video-id');

    // Удаление видео
    $client->videos()->delete('video-id');

} catch (KinescopeException $e) {
    echo 'Error: ' . $e->getMessage();
    echo 'Status Code: ' . $e->getStatusCode();
}
```

## 5. Технические требования

### 5.1 Системные требования
- PHP >= 7.4 (рекомендуется PHP 8.0+)
- Расширения:
  - `ext-json` - для работы с JSON
  - `ext-curl` - для HTTP запросов
  - `ext-mbstring` - для работы со строками

### 5.2 Зависимости
- `guzzlehttp/guzzle` (^7.0) - HTTP клиент
- `psr/log` (^1.1|^2.0|^3.0) - PSR-3 логирование (опционально)

### 5.3 Стандарты кодирования
- PSR-1: Basic Coding Standard
- PSR-2/PSR-12: Coding Style Guide
- PSR-4: Autoloading Standard
- PSR-7: HTTP Message Interface (для HTTP классов)
- PSR-18: HTTP Client Interface

### 5.4 Инструменты разработки
- `phpunit/phpunit` (^9.0) - тестирование
- `phpstan/phpstan` (^1.0) - статический анализ
- `friendsofphp/php-cs-fixer` (^3.0) - форматирование кода
- `psalm/psalm` - дополнительный статический анализ

## 6. Тестирование

### 6.1 Модульные тесты (Unit Tests)
- Покрытие всех публичных методов классов
- Тестирование обработки ошибок
- Тестирование валидации данных
- Мокирование HTTP запросов

### 6.2 Интеграционные тесты
- Тесты с реальным API (опционально, через API ключ в env)
- Тесты загрузки файлов
- Тесты пагинации

### 6.3 Требования к покрытию
- Минимальное покрытие кода: 80%
- Критические компоненты (Client, Resources): 90%+

### 6.4 Структура тестов
```
tests/
├── Unit/
│   ├── ClientTest.php
│   ├── Resources/
│   │   ├── VideosTest.php
│   │   ├── ProjectsTest.php
│   │   ├── FoldersTest.php
│   │   └── WebhooksTest.php
│   ├── Models/
│   │   ├── VideoTest.php
│   │   └── ...
│   └── Exceptions/
│       └── ExceptionsTest.php
├── Integration/
│   ├── VideosIntegrationTest.php
│   └── ...
└── TestCase.php
```

## 7. Документация

### 7.1 README.md
- Описание библиотеки
- Установка через Composer
- Быстрый старт
- Примеры использования
- Ссылки на полную документацию

### 7.2 Код документация
- PHPDoc комментарии для всех публичных методов и классов
- Примеры использования в docblocks
- Описание параметров и возвращаемых значений
- Описание возможных исключений

### 7.3 Дополнительная документация
- CHANGELOG.md - история изменений
- CONTRIBUTING.md - руководство для контрибьюторов
- LICENSE - лицензия (MIT рекомендуется)
- docs/ - расширенная документация
  - installation.md
  - authentication.md
  - videos.md
  - projects.md
  - folders.md
  - webhooks.md
  - error-handling.md
  - examples.md

## 8. Безопасность

### 8.1 Обработка API ключей
- Никогда не логировать API ключи
- Поддержка загрузки из переменных окружения
- Предупреждения о безопасном хранении ключей в документации

### 8.2 Валидация данных
- Валидация входящих параметров
- Санитизация данных перед отправкой
- Проверка типов данных

### 8.3 HTTPS
- Все запросы только через HTTPS
- Проверка SSL сертификатов

## 9. Производительность

### 9.1 Кеширование
- Опциональное кеширование ответов (через PSR-6/PSR-16)
- Настраиваемое время жизни кеша

### 9.2 Пагинация
- Поддержка эффективной пагинации
- Итераторы для удобного обхода больших списков

### 9.3 Batch операции
- Поддержка массовых операций (если API поддерживает)

## 10. Composer пакет

### 10.1 composer.json
```json
{
    "name": "kinescope/php-sdk",
    "description": "Official PHP SDK for Kinescope API",
    "keywords": ["kinescope", "video", "api", "sdk"],
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=7.4",
        "ext-json": "*",
        "ext-curl": "*",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "phpstan/phpstan": "^1.0",
        "friendsofphp/php-cs-fixer": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Kinescope\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Kinescope\\Tests\\": "tests/"
        }
    }
}
```

## 11. CI/CD

### 11.1 GitHub Actions
- Автоматический запуск тестов при каждом push/PR
- Проверка стиля кода (PHP CS Fixer)
- Статический анализ (PHPStan, Psalm)
- Генерация отчетов о покрытии кода

### 11.2 Версионирование
- Semantic Versioning (MAJOR.MINOR.PATCH)
- Автоматическая генерация CHANGELOG
- Теги для релизов

## 12. Этапы разработки

### Этап 1: Основа (Foundation)
- [ ] Создание структуры проекта
- [ ] Настройка Composer
- [ ] Реализация базового Client
- [ ] Реализация HttpClient с Guzzle
- [ ] Реализация базовых исключений
- [ ] Настройка PHPUnit

### Этап 2: Основные ресурсы (Core Resources)
- [ ] Реализация Videos resource
- [ ] Реализация Projects resource
- [ ] Реализация Folders resource
- [ ] Создание моделей данных
- [ ] Unit тесты для ресурсов

### Этап 3: Дополнительные функции (Additional Features)
- [ ] Реализация Webhooks resource
- [ ] Загрузка файлов
- [ ] Пагинация
- [ ] Валидация данных
- [ ] Обработка rate limiting

### Этап 4: Тестирование и документация
- [ ] Интеграционные тесты
- [ ] Достижение 80%+ покрытия кода
- [ ] Написание документации
- [ ] Примеры использования
- [ ] README.md

### Этап 5: Финализация
- [ ] Настройка CI/CD
- [ ] Статический анализ (PHPStan, Psalm)
- [ ] Code style фиксы
- [ ] Подготовка к релизу
- [ ] Публикация на Packagist

## 13. Поддержка и обновления

### 13.1 Политика поддержки версий
- Активная поддержка: текущая мажорная версия
- Исправления безопасности: предыдущая мажорная версия
- EOL: через 12 месяцев после релиза новой мажорной версии

### 13.2 Обновление документации API
- Регулярная проверка изменений в Kinescope API
- Обновление SDK при добавлении новых endpoints
- Поддержка обратной совместимости

## 14. Источники информации

Основная документация:
- [Kinescope API Documentation](https://documenter.getpostman.com/view/10589901/TVCcXpNM)
- [Kinescope GitHub](https://github.com/kinescope)
- [Kinescope Official Website](https://www.kinescope.com/)

## 15. Контакты и поддержка

- GitHub Issues: для багов и feature requests
- Email: для приватных вопросов
- Документация: подробные гайды и примеры

---

**Дата создания**: 2026-01-19
**Версия документа**: 1.0
**Статус**: Draft
