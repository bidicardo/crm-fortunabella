# Промпты для Claude Code — Фаза 2а: Прод-окружение на VPS

Источник: `docs/25-architecture-proposal.md` (раздел 11 и Фаза 2а, задачи
20.1–20.4), требования — `docs/23-hosting-constraints.md`,
`docs/19-security.md` (разделы «Сервер», «Резервные копии»).

Решения владельца (повторно не обсуждать): VPS **Hostkey vm.nano**
(Москва, 2 vCPU, 2 ГБ RAM, 60 ГБ SSD, Ubuntu 24.04); CRM —
`crm.fortunabella.ru`, лендинг `fortunabella.ru` — на том же VPS; DNS и
почта остаются в REG.RU; СУБД — **MySQL 8.4** (как в Sail); бэкапы — на
ПК владельца; выполняется сразу после Фазы 2.

Выполнять по порядку: 20.1 → 20.2 → 20.3 → 20.4. Каждая часть — отдельный
запуск; после каждой — проверка владельцем и коммит по явной просьбе.

## Что нужно от владельца заранее

- Заказанный VPS (проверить, что виртуализация KVM), его IP и доступ root
  на первый вход.
- SSH-ключ на ПК владельца (публичную часть передать в чат; приватную —
  никогда).
- Доступ к DNS-зоне `fortunabella.ru` в REG.RU (A-записи ставит владелец
  по инструкции).
- Репозиторий на GitHub (приватный): включённые Actions и доступ к GHCR.
- Файлы текущего лендинга (`index.html`, `send.php` и т. п.) — выгрузить
  с REG.RU; секреты лендинга (`config.php` с токеном бота) в Git не
  класть.
- Пароль шифрования бэкапов — придумывает и хранит владелец (в чат не
  писать).

## Общие правила

- Новые зависимости и сторонние Docker-образы — только официальные
  (`php`, `nginx`, `mysql`, `caddy`) и с подтверждения владельца.
- Никаких секретов в Git: только `.env.production.example` без значений.
- Бизнес-логику CRM не менять; тесты не ослаблять.
- Всё, что выполняется на сервере, — скриптами из репозитория и
  инструкцией по шагам для владельца (Claude Code к серверу не
  подключается).
- В конце каждой части: `composer check`, `git status`, `git diff`,
  ничего не коммитить; чек-лист проверки для владельца.

---

## Задача 20.1 — Подготовка VPS

```
Прочитай CLAUDE.md, docs/23-hosting-constraints.md (разделы «Подготовка
сервера», «Бюджет памяти»), docs/19-security.md (раздел «Сервер»).

Сделай в репозитории каталог deploy/ с инструкцией и скриптом первичной
настройки чистого Ubuntu 24.04 (запускает владелец под root один раз):
1. deploy/README.md — пошагово для новичка: первый вход, запуск скрипта,
   проверка, что вход по ключу работает, и только потом отключение входа
   по паролю; как вернуть доступ через консоль панели Hostkey, если
   что-то пошло не так.
2. deploy/setup-server.sh (идемпотентный, bash, set -euo pipefail):
   пользователь deploy с sudo и SSH-ключом из аргумента; запрет входа по
   паролю и root по SSH; ufw (22, 80, 443); fail2ban (sshd);
   unattended-upgrades; swap 1 ГБ; Docker Engine + Compose plugin из
   официального репозитория Docker; пользователь deploy в группе docker;
   часовой пояс Europe/Moscow.
3. deploy/check-server.sh — проверки: версия Docker/Compose, swap,
   статус ufw и fail2ban, исходящий HTTPS к api.telegram.org,
   web.push.apple.com, fcm.googleapis.com (код ответа), свободная память
   и диск.
Проверь скрипты shellcheck (если есть в Sail/локально) или хотя бы
bash -n.
```

---

## Задача 20.2 — Production-образ и compose

```
Прочитай CLAUDE.md, docs/23-hosting-constraints.md, docs/25-architecture-
proposal.md (раздел 11), compose.yaml (Sail), .env.example,
bootstrap/app.php, config/filesystems.php, vite.config.js.

Сделай production-конфигурацию CRM:
1. docker/Dockerfile, multi-stage: stage composer (composer install
   --no-dev --optimize-autoloader), stage node (npm ci, npm run build),
   runtime php:8.4-fpm с расширениями pdo_mysql, opcache, intl, zip,
   bcmath (только нужные — проверь по composer.json и коду), без Node и
   dev-зависимостей; nginx — отдельный контейнер или в том же (выбери
   проще по памяти и объясни). Пользователь не root.
2. docker/php.ini: upload_max_filesize/post_max_size 12M,
   memory_limit 256M, opcache для production; docker/php-fpm.conf:
   pm=ondemand, pm.max_children под бюджет памяти.
3. compose.prod.yaml: app, worker (queue:work --tries=3 --max-time=3600),
   scheduler (schedule:work), db (mysql:8.4, innodb_buffer_pool_size=256M,
   performance_schema=OFF, без публикации порта, healthcheck), caddy
   (порты 80/443, автоматический HTTPS для crm.fortunabella.ru и
   fortunabella.ru); тома db-data, storage, caddy-data; restart: always;
   лимиты памяти у каждого контейнера — суммарно с запасом под 2 ГБ.
4. docker/Caddyfile: crm.fortunabella.ru → app; fortunabella.ru и www →
   лендинг (статический сайт + PHP для send.php; каталог landing/ в
   репозитории, секреты — вне Git через том или переменные).
5. bootstrap/app.php: trustProxies(at: '*') — CRM за прокси; healthcheck
   по /up.
6. .env.production.example (без значений секретов): APP_ENV=production,
   APP_DEBUG=false, APP_URL=https://crm.fortunabella.ru,
   SESSION_SECURE_COOKIE=true, LOG_CHANNEL=stderr, DB_HOST=db,
   QUEUE_CONNECTION=database.
7. .dockerignore (vendor, node_modules, .git, .env*, storage/*,
   tests, docs).
8. Тест: витрина /design-showcase в production по-прежнему 404 (уже
   есть — не сломать); тест, что за прокси с X-Forwarded-Proto: https
   генерируются https-ссылки.
Проверка локально: docker compose -f compose.prod.yaml up (с
self-signed/локальным доменом или только app+db), /up = 200, вход,
список клиентов, docker stats — суммарная память. Покажи цифры.
```

---

## Задача 20.3 — CI, деплой, лендинг и DNS

```
Прочитай docs/prompts/04-vps-production.md (решения), результаты 20.1–20.2.

1. .github/workflows/deploy.yml: на push в main — composer check (как
   сейчас), сборка образа, публикация в ghcr.io (приватно) с тегами sha и
   latest. Деплой на сервер — отдельной ручной командой (не из CI),
   чтобы ключ сервера не хранился в GitHub.
2. deploy/deploy.sh (запускается на VPS пользователем deploy): docker
   compose pull, up -d, php artisan migrate --force, config:cache,
   route:cache, view:cache, event:cache, queue:restart; проверка /up;
   откат: deploy.sh <предыдущий тег>.
3. Перенос лендинга: каталог landing/ (без секретов), инструкция, куда
   положить config.php на сервере.
4. deploy/README.md: вход в GHCR на сервере (токен только чтение),
   первый запуск, создание первого создателя (crm:create-creator),
   A-записи crm.fortunabella.ru и fortunabella.ru в DNS REG.RU, проверка
   сертификатов, что делать с MX (не трогать).
Чек-лист владельцу: HTTPS открывается на обоих доменах, вход в CRM,
форма лендинга отправляет заявку в Telegram как раньше, iPhone —
копирование ссылки-приглашения по HTTPS.
```

---

## Задача 20.4 — Бэкапы

```
Прочитай docs/23-hosting-constraints.md и docs/19-security.md (разделы
«Бэкапы», «Резервные копии»).

1. deploy/backup.sh (cron на VPS, ежедневно ночью): mysqldump из
   контейнера db (--single-transaction), архив тома storage (документы),
   шифрование паролем (openssl enc -aes-256-cbc -pbkdf2 или age —
   без новых пакетов, если можно), имя с датой, хранение 7 последних;
   лог результата. Пароль — в файле только для root/deploy на сервере
   (права 600), не в Git.
2. deploy/restore.sh: восстановление из выбранного архива в чистую БД и
   том (с подтверждением), инструкция.
3. Скрипт для ПК владельца (Windows): backup-pull.ps1 — забирает новые
   архивы по scp с ключом, хранит N последних; инструкция, как
   поставить его в Планировщик заданий Windows.
4. deploy/README.md: ежемесячная проверка восстановления по шагам.
Проверка: на локальном prod-compose — бэкап создаётся, восстановление в
чистую БД возвращает клиентов и документы.
```
