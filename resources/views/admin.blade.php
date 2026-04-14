<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Seminários EIC - Admin</title>
    @include('partials.head-scripts')
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/admin/app.tsx'])
</head>
<body class="bg-background text-foreground">
    <div id="app"></div>
</body>
</html>
