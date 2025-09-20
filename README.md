<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Справочники — Laravel microservice

Кратко

Проект — микросервис «Справочники» на Laravel 12 (LTS). Предназначен для CRUD и массового импорта трёх справочников: SWIFT-коды банков, бюджетополучатели и счета казначейства. Поддерживается OAuth2 (Laravel Passport), импорт CSV через очередь (RabbitMQ), файлы хранятся в MinIO (S3-совместимое хранилище). В проекте подготовлены сидеры для массовой генерации данных (пакеты >100k записей).

---

Технологии

- PHP 8.3, Laravel 12 (LTS)
- PostgreSQL
- Laravel Passport (OAuth2, client_credentials)
- Laravel Excel (импорт CSV)
- Laravel Queues (RabbitMQ)
- MinIO (Storage::disk('s3'))
- Docker + Docker Compose
- pgAdmin
- Postman (коллекция с запросами)

---

Структура данных (справочники)

- `swift_codes`
  - `id` (uuid)
  - `swift_code` (string)
  - `bank_name` (string)
  - `country` (string)
  - `city` (string)
  - `address` (string)
  - `created_by`, `updated_by` (uuid)
  - timestamps

- `budget_holders`
  - `id` (uuid)
  - `tin` (string)
  - `name` (string)
  - `region` (string)
  - `district` (string)
  - `address` (string)
  - `phone` (string)
  - `responsible` (string)
  - `created_by`, `updated_by` (uuid)
  - timestamps

- `treasury_accounts`
  - `id` (uuid)
  - `account` (string)
  - `mfo` (string)
  - `name` (string)
  - `department` (string)
  - `currency` (string)
  - `created_by`, `updated_by` (uuid)
  - timestamps

---

Возможности API

- Полный CRUD для всех трёх справочников (routes в `routes/api.php`, контроллеры в `app/Http/Controllers/`)
- Импорт CSV в очередь: `POST /api/{resource}/import` — import ставится в очередь и обрабатывается пакетами (chunk size = 500)
- Загрузка файлов (изображений) в MinIO: `POST /api/upload` (использует `Storage::disk('s3')->put()` и `Storage::disk('s3')->url()`)
- Фильтрация, поиск (ilike), пагинация и сортировка в операциях списка
- Защита маршрутов через middleware `auth:api` (Laravel Passport TokenGuard)

---

Docker (сервисы)

- `app` — PHP/Laravel приложение (php-fpm) — nginx проксирует внешний порт
- `web` — nginx (порт 8095 на хост)
- `db` — postgres:15 (порт 5435 на хост)
- `pgadmin` — pgAdmin (5051)
- `rabbitmq` — rabbitmq:3-management (5673/15673 на хост)
- `minio` — minio/minio (порт S3 9000 -> 9010 на хост; console 9001 -> 9011 на хост)

Запуск окружения

1. Скопируйте пример окружения и отредактируйте `.env`:

   cp .env .env.local
   # отредактируйте .env.local по окружению

   Важные переменные (пример):

   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=minio
   AWS_SECRET_ACCESS_KEY=minio123
   AWS_DEFAULT_REGION=us-east-1
   AWS_BUCKET=local
   AWS_ENDPOINT=http://minio:9000
   AWS_USE_PATH_STYLE_ENDPOINT=true

2. Соберите и запустите контейнеры:

   docker compose up -d --build

3. Установите зависимости (если ещё не установлено):

   docker compose exec app composer install
   docker compose exec app composer require league/flysystem-aws-s3-v3 --no-interaction

---

MinIO

- Конфигурация в `docker-compose.yml` (image: `minio/minio`, command: `server /data --console-address ":9001"`).
- Публичные порты на хосте по настройке репозитория:
  - S3 API: http://localhost:9010 (проксирует контейнерный 9000)
  - Console: http://localhost:9011 (логин: `minio`, пароль: `minio123`)
- Создание bucket `local` можно сделать через MinIO Console или автоматизированным скриптом `scripts/create_minio_bucket.php`.

---

Filesystems / MinIO в Laravel

- Файл: `config/filesystems.php` — диск `s3` должен быть настроен и содержать `use_path_style_endpoint => true` и `endpoint => env('AWS_ENDPOINT')`.
- В `.env` поставить `FILESYSTEM_DISK=s3` и значения `AWS_*` как выше.

---

Upload API

- Route: `POST /api/upload`
- Валидация: `image|required|mimes:jpg,jpeg,png|max:5120` (5MB)
- Контроллер: `app/Http/Controllers/FileUploadController.php` — сохраняет файл в `Storage::disk('s3')->put()` и возвращает JSON с `path` и `url`.
- Для теста локально можно временно сделать маршрут публичным; в production — включить `auth:api`.

Пример curl (без auth, для локальной проверки):

```bash
curl -X POST http://localhost:8095/api/upload \
  -F "image=@/path/to/test.jpg"
```

---

Миграции и сидеры

- Миграции в `database/migrations/`.
- Сидеры:
  - `SwiftCodeSeeder` — ~100k записей (batch inserts)
  - `BudgetHolderSeeder` — ~100k записей (batch inserts)
  - `TreasuryAccountSeeder` — ~100k записей (batch inserts)
  - Зарегистрированы в `DatabaseSeeder.php`

Запуск миграций + сидеров (в контейнере):

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Примечания по сидерам

- Сидеры делают пакетную вставку (chunk = 1000), используют UUID для `id`, `created_by`, `updated_by`.
- При большом объёме возможны ограничения по памяти. Рекомендации:
  - Увеличить `memory_limit` PHP в контейнере (рекомендуется 512M) или
  - Уменьшить `chunk` в сидерах (например, 500) и/или запускать сидеры по частям.

---

OAuth2 / Laravel Passport

- Используется Laravel Passport + TokenGuard.
- Маршруты защищены через `auth:api`.
- Пример получения access_token (client_credentials):

```bash
curl -X POST http://localhost:8095/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id={CLIENT_ID}" \
  -d "client_secret={CLIENT_SECRET}"
```

- Пример вызова защищённого endpoint:

```bash
curl -H "Authorization: Bearer {ACCESS_TOKEN}" "http://localhost:8095/api/swift?page=1&per_page=5"
```

Примечание: если получаете ошибки парсинга токена (Lcobucci JWT), проверьте `oauth-private.key`/`oauth-public.key` и корректность передачи токена (без кавычек).

---

Импорт CSV

- Endpoint: `POST /api/{resource}/import` (например, `/api/swift/import`)
- Импорт выполняется через Laravel Excel и класс импорта реализует `ShouldQueue`, `WithChunkReading` (chunk = 500) и `OnEachRow`.
- Очередь: RabbitMQ — проверьте настройки в `.env` (`QUEUE_CONNECTION=rabbitmq` и `RABBITMQ_*`).
- Запуск worker для обработки импорта:

```bash
docker compose exec app php artisan queue:work
```

---

Postman и тесты

- В репозитории есть коллекция Postman `directories-service.postman_collection.json`.
- Пример теста для Postman:

```js
pm.test("Формат ответа корректен", function () {
  const res = pm.response.json();
  pm.expect(res).to.have.keys(['message', 'data', 'timestamp', 'success']);
});
```

---

Примеры API

- GET /api/swift?page=1&country=UZ
- POST /api/budget-holders/import (form-data file -> CSV)
- POST /api/upload (file)
- GET /api/treasury-accounts?sort=name&direction=desc

---

Отладка и FAQ

- 500 при парсинге токена: проверьте заголовок Authorization — токен должен быть без кавычек, без дополнительных символов.
- Проблемы с подключением к БД: проверьте `DB_HOST` (в docker-compose обычно `db`).
- MinIO URL в ответе может использовать internal host `minio:9000` — в браузере используйте `http://localhost:9010/...` или консоль `http://localhost:9011`.
- Сидеры OOM: уменьшите chunk или увеличьте memory_limit в контейнере.

---

Полезные файлы

- `routes/api.php` — список API маршрутов
- `app/Http/Controllers/*` — контроллеры CRUD и загрузки
- `app/Imports/*` — импорты CSV
- `database/seeders/*` — сидеры большого объёма
- `scripts/create_minio_bucket.php` — утилита для создания bucket в MinIO

---

Контакты

- Репозиторий: локальная рабочая копия
- По просьбе могу:
  - Пересемлить данные строго по 100k для каждой таблицы,
  - Увеличить memory_limit контейнера и досеять оставшиеся записи,
  - Вернуть `/api/upload` под защиту `auth:api` и помочь с client_credentials flow.

README составлен по текущему состоянинию кода и рабочих проверок.
