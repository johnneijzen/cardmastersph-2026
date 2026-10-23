<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased print:bg-white dark:bg-zinc-800 dark:text-zinc-100 print:dark:bg-white print:dark:text-zinc-900">
    <main class="mx-auto max-w-4xl p-4 sm:p-8 print:max-w-none print:p-0">
        {{ $slot }}
    </main>
</body>
</html>
