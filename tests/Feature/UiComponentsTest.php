<?php

use Illuminate\Support\Facades\Blade;

it('renders the button variants and an icon', function () {
    expect(Blade::render('<x-ui.button variant="primary" icon="plus">Новый</x-ui.button>'))
        ->toContain('Новый')->toContain('bg-accent-fill');

    expect(Blade::render('<x-ui.button variant="danger" disabled>Удалить</x-ui.button>'))
        ->toContain('disabled')->toContain('bg-danger');

    expect(Blade::render('<x-ui.button href="/clients" variant="secondary">Клиенты</x-ui.button>'))
        ->toContain('<a href="/clients"');
});

it('renders an icon-button with an accessible label', function () {
    expect(Blade::render('<x-ui.icon-button icon="edit" label="Редактировать" />'))
        ->toContain('aria-label="Редактировать"')
        ->toContain('<button');
});

it('renders an input with label, error and disabled state', function () {
    $html = Blade::render('<x-ui.input label="Телефон" name="phone" error="Неверный формат" />');

    expect($html)
        ->toContain('for="phone"')
        ->toContain('id="phone"')
        ->toContain('Неверный формат')
        ->toContain('aria-invalid="true"');

    expect(Blade::render('<x-ui.input label="Имя" disabled />'))->toContain('disabled');
});

it('renders a select with options', function () {
    $html = Blade::render('<x-ui.select label="Тип" name="legal_type"><option>Физлицо</option></x-ui.select>');

    expect($html)->toContain('for="legal_type"')->toContain('Физлицо');
});

it('renders a textarea', function () {
    expect(Blade::render('<x-ui.textarea label="Примечания" name="notes">текст</x-ui.textarea>'))
        ->toContain('<textarea')->toContain('текст');
});

it('renders checkbox, radio and switch with a clickable label', function () {
    expect(Blade::render('<x-ui.checkbox label="Только архивные" name="only_archived" />'))
        ->toContain('type="checkbox"')->toContain('Только архивные');

    expect(Blade::render('<x-ui.radio label="Вариант А" name="side" value="a" />'))
        ->toContain('type="radio"')->toContain('value="a"');

    expect(Blade::render('<x-ui.switch label="Тёмная тема" name="dark" />'))
        ->toContain('sr-only')->toContain('Тёмная тема');
});

it('renders a status badge in the given tone', function () {
    expect(Blade::render('<x-ui.badge tone="stage-new">Новая заявка</x-ui.badge>'))
        ->toContain('Новая заявка')->toContain('--color-stage-new');
});

it('renders the four alert kinds with the right role', function () {
    expect(Blade::render('<x-ui.alert kind="error" title="Ошибка">Текст</x-ui.alert>'))
        ->toContain('role="alert"')->toContain('Ошибка');

    expect(Blade::render('<x-ui.alert kind="success" title="Успех">Текст</x-ui.alert>'))
        ->toContain('role="status"');
});

it('renders a card with a title', function () {
    expect(Blade::render('<x-ui.card title="Клиент">Содержимое</x-ui.card>'))
        ->toContain('Клиент')->toContain('Содержимое');
});

it('renders a table wrapper', function () {
    expect(Blade::render('<x-ui.table><tr><td>1</td></tr></x-ui.table>'))
        ->toContain('<table')->toContain('overflow-x-auto');
});

it('renders an empty state', function () {
    expect(Blade::render('<x-ui.empty-state icon="search" title="Ничего не найдено" text="Измените запрос" />'))
        ->toContain('Ничего не найдено')->toContain('Измените запрос');
});

it('renders a dropdown panel container', function () {
    expect(Blade::render('<x-ui.panel>Пункт</x-ui.panel>'))
        ->toContain('Пункт')->toContain('shadow-panel');
});
