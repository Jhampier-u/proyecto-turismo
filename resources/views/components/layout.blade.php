<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gesti&oacute;n tur&iacute;stica</title>
    @vite('resources/css/app.css')
</head>
<body>
    <header>
        <nav>
            <a href="{{ route('show.admLogin') }}">Iniciarfsdfds Sesi&oacute;n</a>
        </nav>


    </header>
    <main>
        {{ $slot }}
    </main>
</body>
</html>