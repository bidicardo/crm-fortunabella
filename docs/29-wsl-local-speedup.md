# Ускорение локальной работы: перенос проекта в WSL

Инструкция для владельца. Решение рекомендовано документацией Laravel Sail.

## Зачем

Проект лежит на диске Windows (`F:\crm-fortunabella`), а Docker читает его
через медленный мост между Windows и Linux. Из-за этого локально страница
грузится около 3 с, ответ Livewire (например, после переноса карточки в
канбане) — до 8 с, `php artisan --version` в контейнере — 8,5 с (сам PHP
работает 0,4 с, остальное — чтение файлов). Если держать проект внутри
Linux (WSL), всё работает в несколько раз быстрее. На сервере (VPS) этой
проблемы нет.

Данные не теряются: база MySQL хранится в отдельном томе Docker
`crm-fortunabella_sail-mysql`. Новая папка называется так же, поэтому
подхватит тот же том.

## 1. Установить Ubuntu в WSL (один раз)

1. Открыть PowerShell **от имени администратора** и выполнить:
   ```
   wsl --install -d Ubuntu
   ```
2. Если попросит — перезагрузить компьютер.
3. Откроется окно Ubuntu и попросит придумать имя пользователя и пароль
   Linux. Это отдельный пароль, только для Linux: записать у себя и
   никуда не отправлять.
4. Docker Desktop → **Settings → Resources → WSL integration** → включить
   **Ubuntu** → **Apply & restart**.

## 2. Остановить старые контейнеры

В PowerShell:
```
cd F:\crm-fortunabella
$env:WWWUSER=1000; $env:WWWGROUP=1000
docker compose down
```
Флаг `-v` **не добавлять** — он удалит базу.

## 3. Скопировать проект внутрь Linux

Открыть **Ubuntu** из меню «Пуск» и выполнить по очереди:
```
git config --global --add safe.directory /mnt/f/crm-fortunabella/.git
git clone /mnt/f/crm-fortunabella ~/crm-fortunabella
cd ~/crm-fortunabella
git remote set-url origin https://github.com/bidicardo/crm-fortunabella.git
cp /mnt/f/crm-fortunabella/.env .env
cp -r /mnt/f/crm-fortunabella/vendor .
```
- Первая команда обязательна: без неё `git clone` из `/mnt/f` падает с
  ошибкой «dubious ownership» (папка на диске Windows принадлежит другому
  пользователю).
- В команде копирования `vendor` в конце стоят **пробел и точка**
  (`… vendor .`) — точка означает «в текущую папку»; без них команда не
  сработает.
- `.env` в Git не хранится — копируется отдельно и остаётся только на
  этом компьютере.
- `vendor` копируется, чтобы не скачивать заново. `node_modules` не
  копировать — она собрана под Windows.

Чтобы `git push` из Linux использовал вход в GitHub из Windows:
```
git config --global credential.helper "/mnt/c/Program\ Files/Git/mingw64/bin/git-credential-manager.exe"
git config --global user.name "bidicardo"
```
Почта для коммитов — та же, что в Git на Windows (посмотреть в PowerShell:
`git config user.email`). Затем в Ubuntu:
```
git config --global user.email "ваша-почта"
```

## 4. Запустить проект

В Ubuntu, в папке `~/crm-fortunabella`:
```
./vendor/bin/sail up -d
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan migrate
```
Скрипт `sail` в Linux работает сам, задавать `WWWUSER` и `WWWGROUP` не
нужно. Открыть `http://localhost` — должны быть прежние клиенты и
контрагенты (войти, возможно, придётся заново).

## 5. Открыть проект в VS Code

1. В VS Code установить расширение **WSL** (Microsoft).
2. В Ubuntu выполнить:
   ```
   cd ~/crm-fortunabella && code .
   ```
3. VS Code откроется с надписью **WSL: Ubuntu** в левом нижнем углу.
   Claude Code в этом окне работает внутри Linux.

## 6. Проверить скорость

В Ubuntu:
```
time ./vendor/bin/sail artisan --version
```
Было около 8,5 с, должно стать меньше секунды. Загрузка страниц и
перетаскивание в канбане — почти мгновенно.

## 7. Сообщить Claude Code

В VS Code, открытом через WSL (шаг 5), написать Claude Code, что переезд
выполнен. Он обновит в `CLAUDE.md` и `README.md` команды для Linux
(`./vendor/bin/sail …` вместо `docker compose exec laravel.test …`) и
проверит, что тесты проходят на новом месте.

## После переезда

- Работать только в `~/crm-fortunabella`. Папку `F:\crm-fortunabella` не
  править, чтобы не было двух копий; удалить её самостоятельно, когда всё
  проверено.
- Проверка перед коммитом: `./vendor/bin/sail composer check`.
- Команды `docker compose exec laravel.test …` из `CLAUDE.md` и
  `README.md` в Linux заменяются на `./vendor/bin/sail …` — после
  переезда эти места обновляются.
- Если `git push` из Ubuntu зависает или пишет `Failed to connect to
  github.com port 443`, а в Windows GitHub открывается (через VPN), то
  WSL идёт в сеть мимо VPN. Исправление (Windows 11): открыть
  `notepad %USERPROFILE%\.wslconfig` (Win + R), вписать
  ```
  [wsl2]
  networkingMode=mirrored
  ```
  сохранить, закрыть VS Code, в PowerShell выполнить `wsl --shutdown`,
  открыть Ubuntu и запустить `./vendor/bin/sail up -d`. Проверка в
  Ubuntu (не в PowerShell): `wslinfo --networking-mode` → `mirrored`.
  Откат — удалить строку `networkingMode=mirrored` и снова
  `wsl --shutdown`.
- Расширение Claude в Chrome продолжает работать: адрес `http://localhost`
  тот же.
