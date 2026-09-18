# Fortuna CRM — окружение разработчика

Техническая документация и требования — в [`docs/`](docs/), архитектурные
решения — в [`docs/25-architecture-proposal.md`](docs/25-architecture-proposal.md),
правила работы над проектом — в [`CLAUDE.md`](CLAUDE.md).

## Стек

PHP 8.4 + Laravel 13. Подробности и обоснование — в
`docs/25-architecture-proposal.md`.

## Как поднять проект локально через Sail (рекомендуется)

Laravel Sail — Docker-окружение (PHP 8.4 + MySQL 8.4 + Mailpit) только
для компьютера разработчика. На продакшене (REG.RU) Docker не
используется, см. `docs/23-hosting-constraints.md`.

Нужны Docker Desktop и WSL2. Команды ниже — для Linux/macOS/WSL2:

```
cp .env.example .env
./vendor/bin/sail up -d              # поднять окружение
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate    # миграции в MySQL
./vendor/bin/sail artisan test       # тесты
./vendor/bin/sail down               # остановить
```

Приложение: http://localhost, письма (Mailpit): http://localhost:8025,
MySQL с хоста: `127.0.0.1:3306`.

Скрипт `sail` не работает в Git Bash/PowerShell на Windows — там
запускайте `docker compose` напрямую (после `sail up` те же команды):

```
$env:WWWUSER=1000; $env:WWWGROUP=1000     # PowerShell
docker compose up -d
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan test
docker compose down
```

Тесты (`phpunit.xml`) используют SQLite in-memory и не требуют MySQL.

## Как поднять проект локально (без Sail)

Альтернатива — напрямую через локально установленные PHP и Composer:

1. Установить PHP 8.4 (или 8.5.7 alt — если 8.4 недоступна, см.
   `docs/23-hosting-constraints.md`) и Composer.
2. Установить зависимости:
   ```
   composer install
   ```
3. Скопировать файл окружения (если ещё не создан) и сгенерировать ключ:
   ```
   cp .env.example .env
   php artisan key:generate
   ```
4. Для быстрого локального старта без поднятия MySQL можно временно
   переключить `.env` на SQLite:
   ```
   DB_CONNECTION=sqlite
   ```
   (файл `database/database.sqlite` создаётся автоматически при первой
   миграции). Для конфигурации, близкой к продакшену, использовать
   MySQL/MariaDB — см. значения в `.env.example`.
5. Накатить миграции:
   ```
   php artisan migrate
   ```
6. Запустить локальный сервер:
   ```
   php artisan serve
   ```

## Переменные окружения

Актуальный список — в `.env.example`. Ключевые моменты:

- `APP_TIMEZONE` — временно `Europe/Moscow`, окончательно не решено
  (открытый вопрос в `docs/21-open-questions.md`).
- `DB_*` — рассчитаны на MySQL/MariaDB (требование хостинга,
  `docs/23-hosting-constraints.md`); локально для старта допустим
  SQLite (см. выше).
- Реальные секреты (токены, пароли) никогда не хранятся в `.env.example`
  и не коммитятся — только в локальном `.env`, который в Git не входит.

## Проверка перед коммитом

Перед каждым коммитом запускать одну команду — линтер (Pint, без
автоисправления) и тесты:

```
composer check
```

В Sail: `docker compose exec laravel.test composer check`
(или `./vendor/bin/sail composer check` в WSL2).
Автоисправить стиль: `vendor/bin/pint`. Только тесты: `php artisan test`.

## Документация требований

Все требования, бизнес-правила и ограничения — в папке `docs/`, начиная
с `docs/01-product-overview.md`. Перед изменением кода стоит свериться
с соответствующим документом — CLAUDE.md требует не додумывать
неоднозначные требования, а уточнять их.
