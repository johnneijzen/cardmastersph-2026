<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <main class="flex min-h-screen w-full items-center justify-center p-6">{{ $slot }}</main>

    @fluxScripts
</body>
</html>
