<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class Navigation
{
    /** Группы меню без отсутствующих маршрутов и пустых групп; у пункта добавлены url и current. */
    public static function groups(): array
    {
        $groups = [];

        foreach (config('navigation.groups') as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (! Route::has($item['route'])) {
                    continue;
                }

                $item['url'] = route($item['route']);
                $item['current'] = request()->routeIs(...$item['active']);
                $items[] = $item;
            }

            if ($items) {
                $groups[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * Куда ведёт стрелка «Назад» в верхней панели: не на предыдущую страницу истории,
     * а в родительский раздел. Карточка, форма и т. п. → список раздела; список раздела
     * и страницы вне меню (например, «Пользователи») → главная; на главной стрелки нет.
     */
    public static function backUrl(): ?string
    {
        $route = request()->route()?->getName();

        if ($route === null || $route === 'home') {
            return null;
        }

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if ($item['current'] && $item['route'] !== $route) {
                    return $item['url'];
                }
            }
        }

        return route('home');
    }
}
