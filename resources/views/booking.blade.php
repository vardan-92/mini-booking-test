<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mini Booking — бронирование переговорной</title>
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app" data-timezone="{{ config('app.timezone') }}" data-today="{{ now()->toDateString() }}"></div>
    <noscript>Включите JavaScript, чтобы открыть расписание переговорной.</noscript>
</body>
</html>
