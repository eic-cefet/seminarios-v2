<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Seminários EIC do CEFET-RJ</title>
    @include('partials.head-scripts')
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/system/app.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
