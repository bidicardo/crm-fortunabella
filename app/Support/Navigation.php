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
}
