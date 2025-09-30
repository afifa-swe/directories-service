# directories-service — Сервис справочников (Laravel)

📚 Лёгкий микросервис справочников на Laravel 12 (LTS) с поддержкой OAuth2 (Laravel Passport), импортом CSV в очередь, загрузкой файлов в MinIO и асинхронной обработкой через RabbitMQ.

---

## Краткое описание

Сервис предоставляет REST API для управления справочниками:

- `swift_codes` — банковские SWIFT-коды
- `budget_holders` — плательщики / получатели бюджетных средств
- `treasury_accounts` — казначейские счета

Основные возможности:
- Полный CRUD API для трёх справочников
- Импорт CSV через очередь (Laravel Excel, chunk size = 500)
- Загрузка изображений (jpg/png/jpeg) до 5 MB в MinIO (Storage::disk('s3'))
- Очереди через RabbitMQ (`vladimir-yuldashev/laravel-queue-rabbitmq`)
- Авторизация через Laravel Passport (grant: client_credentials)
- Docker + docker-compose окружение (app, web/nginx, db, rabbitmq, pgadmin, minio)
- Готовые сидеры (в проекте есть сидеры, ~100k записей)
- Postman коллекция: `directories-service.postman_collection.json`

---

## Технологии

- Laravel 12 (LTS)
- PHP 8.3
- PostgreSQL
- Laravel Passport (OAuth2, client_credentials)
- Laravel Excel (maatwebsite/excel)
- RabbitMQ (vladimir-yuldashev/laravel-queue-rabbitmq)
- MinIO (S3 совместимый storage)
- Docker / Docker Compose
- pgAdmin
- Postman (коллекция в репозитории)

---

## Структура данных (поля)

1. swift_codes
- `swift_code`, `bank_name`, `country`, `city`, `address`, `created_by`, `updated_by`

2. budget_holders
- `tin`, `name`, `region`, `district`, `address`, `phone`, `responsible`, `created_by`, `updated_by`

3. treasury_accounts
- `account`, `mfo`, `name`, `department`, `currency`, `created_by`, `updated_by`

---

## Быстрый старт (локально с Docker)

1. Клонировать репозиторий и перейти в папку проекта:

```bash
git clone <repo-url>
cd directories-service
```

2. Скопировать файл окружения и заполнить значения:

```bash
cp .env.example .env
# отредактируйте .env: DB_*, MINIO_*, RABBITMQ_*, PASSPORT_CLIENT_* и другие переменные
```

Важные переменные в `.env`:
- DB_* (Postgres)
- PASSPORT_CLIENT_ID / PASSPORT_CLIENT_SECRET (можно создать через artisan)
- MINIO_ENDPOINT / MINIO_KEY / MINIO_SECRET / MINIO_BUCKET / MINIO_REGION
- QUEUE_CONNECTION=rabbitmq
- RABBITMQ_HOST / RABBITMQ_PORT / RABBITMQ_USER / RABBITMQ_PASSWORD
- APP_URL / API_BASE_URL

3. Запустить Docker окружение:

```bash
docker compose up -d --build
```

4. Выполнить миграции и сидеры (в контейнере `app`):

```bash
# зайти в контейнер app, либо выполнить через docker compose exec
php artisan migrate --seed
```

5. Установить Passport (если ключи не сгенерированы):

```bash
php artisan passport:install
# для client_credentials клиента (если нужно):
php artisan passport:client --client --name="cc_client"
```

При создании клиента в консоли будут выведены `client_id` и `client_secret` — сохраните их.

---

## Авторизация (OAuth2 — client_credentials)

Получение access token (пример curl, form-encoded):

```bash
curl -X POST http://localhost:8095/oauth/token \
  -H "Accept: application/json" \
  -d "grant_type=client_credentials&client_id=YOUR_CLIENT_ID&client_secret=YOUR_CLIENT_SECRET"
```

Ответ содержит `access_token` (JWT). Пример использования полученного токена для вызова защищённого эндпоинта:

```bash
curl -X GET http://localhost:8095/api/swift \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Accept: application/json"
```

Важно: передавайте токен без кавычек и лишних пробелов — любая посторонняя символьная обёртка приведёт к ошибкам парсинга JWT.

---

## Postman

В репозитории есть коллекция: `directories-service.postman_collection.json`.
Рекомендации:
- Импортируйте коллекцию в Postman.
- Заполните запрос `/oauth/token` параметрами `client_id`/`client_secret`.
- Сохраните `access_token` в переменной окружения Postman и используйте `Authorization: Bearer {{token}}`.

---

## API — примеры и маршруты

Общие принципы — стандартный REST CRUD.

Пример маршрутов для `swift_codes`:
- GET /api/swift — список
- POST /api/swift — создать
- GET /api/swift/{id} — получить
- PUT /api/swift/{id} — обновить
- DELETE /api/swift/{id} — удалить

Пример тела запроса (создать / обновить):

```json
{
  "swift_code": "ABCDUS33XXX",
  "bank_name": "Банк Пример",
  "country": "UZ",
  "city": "Ташкент",
  "address": "ул. Пример, 1"
}
```

Пример для `budget_holders`:

```json
{
  "tin": "123456789",
  "name": "ООО Пример",
  "region": "Ташкентская",
  "district": "Мирзо-Улугбек",
  "address": "ул. Пример, 2",
  "phone": "+998901234567",
  "responsible": "Иванов И.И."
}
```

Пример для `treasury_accounts`:

```json
{
  "account": "20202020202020",
  "mfo": "00000",
  "name": "Казначейство",
  "department": "Финансы",
  "currency": "UZS"
}
```

---

## Загрузка файлов (MinIO)

Ограничения: jpg/png/jpeg, max 5 MB.
Пример curl (multipart/form-data):

```bash
curl -X POST http://localhost:8095/api/upload \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -F "file=@/path/to/image.jpg" \
  -F "resource=swift" \
  -F "resource_id=123"
```

Проверьте точные имена полей в коллекции Postman и контроллере `UploadController`.

---

## CSV импорт (Laravel Excel + очередь)

Импорт реализован через очередь (RabbitMQ) с обработкой чанков по 500 строк.

Процесс:
1. Загружаете CSV по соответствующему endpoint (см. Postman коллекцию).
2. Файл сохраняется и создаётся задача в очереди.
3. Очередной worker обрабатывает файл пакетами по 500 строк.

Запуск worker (в контейнере app):

```bash
php artisan queue:work
# или для конкретной очереди
php artisan queue:work --queue=imports
```

---

## Сидеры и тестовые данные

В проекте присутствуют сидеры для наполнения БД (включая большие наборы). Для заполнения:

```bash
php artisan db:seed
# или конкретный сидер
php artisan db:seed --class=BudgetHolderSeeder
```

Учтите: крупные сидеры могут требовать значительных ресурсов и времени.

---

## Конфигурация и отладка

Проверьте:
- `config/auth.php` — guard `api` должен использовать драйвер `passport`.
- `config/passport.php` — guard может быть указан как `api`.
- Наличие ключей: `storage/oauth-private.key` и `storage/oauth-public.key` (должны быть доступны процессу web/php-fpm).

Типичные ошибки и решения:
- "Class 'Laravel\Passport\Passport' not found" — не вызывайте `Passport::routes()` вручную, если версия пакета этого не требует.
- JWT parse errors (invalid base64 characters) — проверьте, что заголовок `Authorization` содержит токен без кавычек.
- `Unauthenticated.` — проверьте правильность заголовка Authorization и корректность guard/миграций/ключей.

---

## Полезные команды

```bash
# поднять докер-окружение
docker compose up -d --build

# миграции и сидеры
php artisan migrate --seed

# создать client_credentials клиент
php artisan passport:client --client --name="cc_client"

# запустить worker очереди
php artisan queue:work
```

---

## Важные файлы в проекте

- `routes/api.php` — роуты API
- `app/Http/Controllers/` — контроллеры CRUD, импорт, загрузки
- `app/Models/SwiftCode.php`, `BudgetHolder.php`, `TreasuryAccount.php`
- `database/seeders/` — сидеры
- `directories-service.postman_collection.json` — Postman коллекция
- `storage/oauth-private.key`, `storage/oauth-public.key` — ключи Passport

---

## Дальнейшие шаги

Если нужно, могу:
- добавить подробные curl-примеры для каждого CRUD-эндпоинта;
- обновить Postman коллекцию с примерами авторизации (если вы хотите — я вставлю client_id/secret как переменные окружения коллекции без утечки секретов в репозиторий);
- вернуть `auth:api` в маршрутах и проверить порядок middleware для поддержки client_credentials токенов (при необходимости).

---

Спасибо. Если хотите — вставлю дополнительные примеры или оформлю отдельный раздел по отладке и логированию.
