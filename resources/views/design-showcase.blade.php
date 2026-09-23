<!DOCTYPE html>
<html lang="ru">
    <head>
        @include('partials.head', ['title' => 'Витрина дизайн-системы'])
    </head>
    <body class="min-h-dvh bg-page text-ink">
        <div class="mx-auto max-w-5xl space-y-10 p-4 pb-16 sm:p-8">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-h2 font-bold">Витрина дизайн-системы</h1>
                <x-theme-toggle class="rounded-md border border-line px-3 py-1.5 text-body" />
            </header>

            <p class="max-w-2xl text-body text-ink-secondary">
                Временная страница только для локальной разработки
                (<code>APP_ENV=local</code>) — в проде отвечает 404, в меню не
                входит. Переключите тему кнопкой выше и проверьте вид на
                телефоне. Наведение, фокус клавиатурой (Tab) и нажатие
                проверяются вручную — здесь показаны обычное, отключённое и
                ошибочное состояния.
            </p>

            {{-- Цвета --}}
            <section class="space-y-3">
                <h2 class="text-h3 font-semibold">Цвета</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ([
                        'accent' => 'Accent',
                        'accent-fill' => 'Accent fill',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'danger' => 'Danger',
                        'info' => 'Info',
                        'stage-new' => 'Этап: новая',
                        'stage-inwork' => 'Этап: в работе',
                        'stage-booked' => 'Этап: бронь',
                        'stage-done' => 'Этап: проведена',
                        'stage-refused' => 'Этап: отказ',
                    ] as $token => $label)
                        <div class="space-y-1">
                            <div class="h-12 rounded-md border border-line" style="background-color: var(--color-{{ $token }})"></div>
                            <p class="text-caption text-ink-secondary">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Типографика --}}
            <section class="space-y-2">
                <h2 class="text-h3 font-semibold">Типографика</h2>
                <p class="text-h1 font-bold">Заголовок H1 (заготовка)</p>
                <p class="text-h2 font-bold">Заголовок H2 (заготовка)</p>
                <p class="text-h3 font-semibold">Заголовок H3 — раздел, имя клиента</p>
                <p class="text-h4 font-semibold">Заголовок H4 — подраздел, диалог</p>
                <p class="text-body-lg">Текст body-lg (заготовка) — Съешь ещё этих мягких французских булок</p>
                <p class="text-body-md">Текст body-md — Съешь ещё этих мягких французских булок</p>
                <p class="text-body">Текст body (основной) — Съешь ещё этих мягких французских булок да выпей чаю</p>
                <p class="text-caption text-ink-secondary">Подпись caption — 19.09.2026 · Иван Иванов</p>
            </section>

            {{-- Иконки --}}
            <section class="space-y-2">
                <h2 class="text-h3 font-semibold">Иконки (Lucide)</h2>
                <div class="flex flex-wrap gap-4">
                    @foreach (['home','deals','clients','counterparties','tasks','calendar','documents','search','plus','close','check','filter','download','upload','mail','lock','logout','settings','bell','user','clock','menu','help','money','grid','trending','copy','edit','merge','archive','delete','warning','success','error','info','chevron-left','chevron-down','sort-up','sort-down','more','theme-dark','theme-light'] as $icon)
                        <div class="flex flex-col items-center gap-1 text-ink-secondary">
                            <x-icon :name="$icon" class="h-5 w-5" />
                            <span class="text-caption">{{ $icon }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Кнопки --}}
            <section class="space-y-3">
                <h2 class="text-h3 font-semibold">Кнопки</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button variant="primary">Основная</x-ui.button>
                    <x-ui.button variant="primary" icon="plus">С иконкой</x-ui.button>
                    <x-ui.button variant="primary" disabled>Отключена</x-ui.button>
                    <x-ui.button variant="secondary">Вторичная</x-ui.button>
                    <x-ui.button variant="secondary" disabled>Отключена</x-ui.button>
                    <x-ui.button variant="danger">Опасная</x-ui.button>
                    <x-ui.button variant="text">Текстовая</x-ui.button>
                    <x-ui.icon-button icon="edit" label="Редактировать" />
                    <x-ui.icon-button icon="delete" label="Удалить" variant="danger" />
                </div>
            </section>

            {{-- Поля --}}
            <section class="grid max-w-2xl gap-4 sm:grid-cols-2">
                <h2 class="col-span-full text-h3 font-semibold">Поля</h2>
                <x-ui.input label="Имя" placeholder="Имя клиента" />
                <x-ui.input label="Телефон (ошибка)" placeholder="+7 917 123-45-67" error="Введите номер в формате +7XXXXXXXXXX" />
                <x-ui.input label="Отключено" value="Нельзя изменить" disabled />
                <x-ui.select label="Тип клиента">
                    <option>Физическое лицо</option>
                    <option>Организация</option>
                </x-ui.select>
                <div class="sm:col-span-2">
                    <x-ui.textarea label="Примечания" rows="3">Пример текста</x-ui.textarea>
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <x-ui.checkbox label="Только архивные" />
                    <x-ui.radio name="demo-radio" value="a" label="Вариант А" checked />
                    <x-ui.radio name="demo-radio" value="b" label="Вариант Б" />
                    <x-ui.switch label="Тёмная тема (пример переключателя)" />
                </div>
            </section>

            {{-- Метки-статусы --}}
            <section class="space-y-2">
                <h2 class="text-h3 font-semibold">Метки-статусы</h2>
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="neutral">архив</x-ui.badge>
                    <x-ui.badge tone="success">оплачено</x-ui.badge>
                    <x-ui.badge tone="warning">ожидает</x-ui.badge>
                    <x-ui.badge tone="error">отказ</x-ui.badge>
                    <x-ui.badge tone="stage-new">Новая заявка</x-ui.badge>
                    <x-ui.badge tone="stage-inwork">В работе</x-ui.badge>
                    <x-ui.badge tone="stage-booked">Бронь</x-ui.badge>
                    <x-ui.badge tone="stage-done">Проведена</x-ui.badge>
                    <x-ui.badge tone="stage-refused">Отказ</x-ui.badge>
                </div>
            </section>

            {{-- Уведомления --}}
            <section class="max-w-2xl space-y-3">
                <h2 class="text-h3 font-semibold">Уведомления</h2>
                <x-ui.alert kind="success" title="Успех">Клиент сохранён.</x-ui.alert>
                <x-ui.alert kind="warning" title="Возможно, такой клиент уже есть">Совпал телефон.</x-ui.alert>
                <x-ui.alert kind="error" title="Ошибка">Не удалось сохранить клиента.</x-ui.alert>
                <x-ui.alert kind="info" title="Информация">Ссылка действует 24 часа.</x-ui.alert>
            </section>

            {{-- Карточка и пустое состояние --}}
            <section class="grid gap-4 sm:grid-cols-2">
                <x-ui.card title="Блок-карточка">
                    <p class="text-body text-ink-secondary">Содержимое карточки с заголовком.</p>
                </x-ui.card>
                <x-ui.empty-state icon="search" title="Клиентов не найдено" text="Измените условия поиска" />
            </section>

            {{-- Таблица --}}
            <section class="space-y-2">
                <h2 class="text-h3 font-semibold">Таблица</h2>
                <x-ui.table>
                    <thead>
                        <tr class="bg-surface">
                            <th class="px-4 py-2 font-semibold">Имя</th>
                            <th class="px-4 py-2 font-semibold">Телефон</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-line">
                            <td class="px-4 py-2">Иван Иванов</td>
                            <td class="px-4 py-2">+7 917 000-00-00</td>
                        </tr>
                    </tbody>
                </x-ui.table>
            </section>

            {{-- Выпадающая панель --}}
            <section class="space-y-2">
                <h2 class="text-h3 font-semibold">Выпадающая панель</h2>
                <x-ui.panel class="w-56">
                    <a href="#" class="block px-4 py-2 text-body hover:bg-page">Пункт 1</a>
                    <a href="#" class="block px-4 py-2 text-body hover:bg-page">Пункт 2</a>
                </x-ui.panel>
            </section>
        </div>

        @livewireScripts
    </body>
</html>
