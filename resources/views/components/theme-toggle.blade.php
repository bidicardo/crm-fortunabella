<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    x-on:click="
        dark = !dark;
        document.documentElement.classList.toggle('dark', dark);
        try { localStorage.theme = dark ? 'dark' : 'light'; } catch (e) {}
    "
    x-text="dark ? 'Светлая тема' : 'Тёмная тема'"
    {{ $attributes->merge(['class' => 'text-sm']) }}
></button>
