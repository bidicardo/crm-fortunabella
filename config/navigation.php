<?php

// Единый источник меню. Только массивы (без замыканий), чтобы работал config:cache.
// Пункт показывается, только если маршрут с таким именем существует.
// active — шаблоны routeIs для подсветки; tab — входит ли в нижнюю панель на телефоне.
return [
    'groups' => [
        [
            'label' => null,
            'items' => [
                ['label' => 'Главная', 'route' => 'home', 'icon' => 'home', 'active' => ['home'], 'tab' => true],
            ],
        ],
        [
            'label' => 'Продажи',
            'items' => [
                ['label' => 'Сделки', 'route' => 'deals.index', 'icon' => 'deals', 'active' => ['deals.*'], 'tab' => true],
                ['label' => 'Клиенты', 'route' => 'clients.index', 'icon' => 'clients', 'active' => ['clients.*'], 'tab' => true],
            ],
        ],
        [
            'label' => 'Партнёры',
            'items' => [
                // Шаблоны не пересекаются: «Действующие» вынесены из «Контрагентов» явным перечнем.
                ['label' => 'Контрагенты', 'route' => 'counterparties.index', 'icon' => 'counterparties', 'active' => ['counterparties.index', 'counterparties.create', 'counterparties.show', 'counterparties.edit'], 'tab' => false],
                ['label' => 'Действующие контрагенты', 'route' => 'counterparties.active', 'icon' => 'counterparties', 'active' => ['counterparties.active', 'counterparties.active.*'], 'tab' => false],
            ],
        ],
        [
            'label' => 'Работа',
            'items' => [
                ['label' => 'Задачи', 'route' => 'tasks.index', 'icon' => 'tasks', 'active' => ['tasks.*'], 'tab' => true],
                ['label' => 'Календарь', 'route' => 'calendar.index', 'icon' => 'calendar', 'active' => ['calendar.*'], 'tab' => false],
            ],
        ],
        [
            'label' => null,
            'items' => [
                ['label' => 'Документы', 'route' => 'documents.index', 'icon' => 'documents', 'active' => ['documents.*'], 'tab' => false],
            ],
        ],
    ],
];
