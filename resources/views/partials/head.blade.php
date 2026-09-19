<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>

{{-- Тема и состояние левого меню выставляются до отрисовки, чтобы не было мигания --}}
<script>
    try {
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        if (localStorage.sidebar === 'collapsed') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    } catch (e) {}
</script>

@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
