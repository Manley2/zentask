<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Zentask — Account Access</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-cover bg-center bg-no-repeat"
      style="background-image: url('{{ asset('images/bg3-zentask.png') }}');">

    {{ $slot }}

</body>
</html>
