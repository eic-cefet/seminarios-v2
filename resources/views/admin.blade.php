<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Admin</title>
    @include('partials.head-scripts')
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/admin/app.tsx'])
</head>
<body class="bg-background text-foreground">
    <div id="app"></div>
</body>
</html>
