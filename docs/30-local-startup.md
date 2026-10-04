# Запуск проекта после перезагрузки компьютера

Инструкция для владельца. Проект лежит внутри Linux (WSL):
`~/crm-fortunabella` (из Windows — `\\wsl.localhost\Ubuntu\home\bidicardo\crm-fortunabella`).
Старую папку `F:\crm-fortunabella` не открывать и не править.

## Каждый раз после включения компьютера

1. **Запустить Docker Desktop** (если не запустился сам) и дождаться
   надписи **Engine running** внизу слева.
   Чтобы запускался сам: Docker Desktop → Settings → General →
   галочка **Start Docker Desktop when you sign in to your computer**.
2. **Открыть Ubuntu** («Пуск» → набрать `Ubuntu`) и выполнить:
   ```
   cd ~/crm-fortunabella
   ./vendor/bin/sail up -d
   ```
   В ответе — три контейнера `Started` или `Running`.
3. **Открыть VS Code** — один из способов:
   - в том же окне Ubuntu: `code .`;
   - в VS Code: File → Open Recent → **crm-fortunabella [WSL: Ubuntu]**
     (не `crm-fortunabella F:\` — это старая копия).

   Проверить: внизу слева написано **WSL: Ubuntu**.
4. **Открыть CRM в браузере:** `http://localhost`.

## Если меняли стили или Claude Code просит пересобрать

```
./vendor/bin/sail npm run build
```
(или `./vendor/bin/sail npm run dev` — сборка на лету, окно Ubuntu при этом
не закрывать).

## Остановить (необязательно)

```
./vendor/bin/sail stop
```
При выключении компьютера контейнеры останавливаются сами, данные базы
сохраняются.

## Если что-то не работает

| Что видно | Что сделать |
|---|---|
| `Docker or Podman is not running` | Docker Desktop не запущен — запустить, дождаться «Engine running», закрыть и снова открыть Ubuntu. Если не помогло: Docker Desktop → Settings → Resources → WSL integration → **Ubuntu** включена → Apply & restart. |
| Сайт не открывается | В Ubuntu: `./vendor/bin/sail ps` — все ли три контейнера `running`; если нет — `./vendor/bin/sail up -d`. |
| Страница с ошибкой 500 | `./vendor/bin/sail artisan migrate`, затем обновить страницу; если не помогло — прислать Claude Code вывод `./vendor/bin/sail logs laravel.test --tail=50`. |
| Страница без оформления | `./vendor/bin/sail npm run build`. |
| В VS Code внизу нет «WSL: Ubuntu» | Открыта не та копия — закрыть окно и открыть через `code .` из Ubuntu. |

## Стартовый промпт для новой сессии Claude Code

Вставить в Claude Code в окне VS Code с **WSL: Ubuntu**:

```
Проект переехал в WSL: теперь он в ~/crm-fortunabella (Ubuntu), Sail
запускается командой ./vendor/bin/sail, старая папка F:\crm-fortunabella
больше не используется. Переезд прошёл по docs/29-wsl-local-speedup.md,
всё работает (artisan --version — 1,1 с вместо 8,5 с), браузерные проверки
прошли. Порядок запуска после перезагрузки — docs/30-local-startup.md.

Прочитай CLAUDE.md, docs/27-task-checklist.md, docs/29-wsl-local-speedup.md
и docs/30-local-startup.md. Затем:
1. В docs/29 перед git clone добавь шаг
   git config --global --add safe.directory /mnt/f/crm-fortunabella/.git
   (без него клон из /mnt/f падает с «dubious ownership») и подчеркни, что в
   команде копирования vendor в конце нужны пробел и точка.
2. В CLAUDE.md и README.md замени команды для Windows
   (docker compose exec laravel.test …, WWWUSER/WWWGROUP, «sail не работает
   на Windows») на ./vendor/bin/sail …; добавь docs/30-local-startup.md в
   список документов CLAUDE.md.
3. Запусти ./vendor/bin/sail composer check.
4. Покажи git status и git diff, ничего не коммить.
Потом продолжим по плану с Д-23 (дизайн канбана контрагентов).
```
