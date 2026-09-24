<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-page text-body text-ink">
        {{-- Декоративный фон по референсу 5 (docs/design/refs/05-login.png): только CSS.
             Фигуры обрезаются своим fixed-блоком с overflow-hidden, поэтому страница
             не получает горизонтальной прокрутки. --}}
        @php $shape = 'absolute rounded-full bg-line-accent/40 dark:bg-accent-fill/25'; @endphp
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="{{ $shape }} -right-40 top-16 h-64 w-[46rem] -rotate-40"></div>
            <div class="{{ $shape }} -right-32 bottom-20 hidden h-36 w-[32rem] -rotate-40 sm:block"></div>
            <div class="{{ $shape }} -bottom-16 -left-28 h-36 w-[28rem] -rotate-40"></div>
            <div class="{{ $shape }} left-[8%] top-[55%] hidden h-32 w-32 sm:block"></div>
            <div class="{{ $shape }} left-[5%] top-[18%] hidden h-3 w-3 sm:block"></div>
            <x-icon name="plus" class="absolute left-[14%] top-[8%] hidden h-8 w-8 text-line-accent dark:text-accent-fill sm:block" />
            <x-icon name="plus" class="absolute left-[12%] top-[28%] hidden h-6 w-6 text-line-accent dark:text-accent-fill sm:block" />
        </div>

        {{-- Отступы под вырез и индикатор «домой». На телефоне всё компактнее, чтобы без клавиатуры
             страница помещалась в экран Safari без прокрутки; с клавиатурой iOS прокручивает сама. --}}
        <div class="relative mx-auto flex min-h-dvh w-full max-w-sm flex-col items-center justify-center gap-4 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-[max(1rem,env(safe-area-inset-top))] sm:gap-6 sm:pb-[max(2rem,env(safe-area-inset-bottom))] sm:pt-[max(2rem,env(safe-area-inset-top))]">
            <div class="flex flex-col items-center gap-1 sm:gap-2">
                {{-- Логотип — трафарет: цвет задаёт токен --color-logo (свой в каждой теме) --}}
                <span
                    role="img"
                    aria-label="Логотип {{ config('app.name') }}"
                    class="block aspect-[283/256] h-16 bg-logo sm:h-24"
                    style="-webkit-mask: url('{{ asset('images/logo.png') }}') center / contain no-repeat; mask: url('{{ asset('images/logo.png') }}') center / contain no-repeat;"
                ></span>
                <p class="text-h3 font-semibold">{{ config('app.name') }}</p>
            </div>

            <x-ui.card class="w-full shadow-panel">
                {{ $slot }}
            </x-ui.card>

            <x-theme-toggle class="focus-ring min-h-11 rounded-md px-2 text-caption text-ink-secondary underline underline-offset-2" />
        </div>

        @livewireScripts
    </body>
</html>
