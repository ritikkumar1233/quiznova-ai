<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'QuizNova AI') : config('app.name', 'QuizNova AI') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

{{-- Inter — QuizNova AI primary typeface --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..700;1,14..32,300..700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

@php
    // Fallback: include built CSS directly if Vite manifest isn't being applied
    $builtCss = public_path('build/assets/app-ZgTC4Owk.css');
@endphp
@if (file_exists($builtCss))
    <link rel="stylesheet" href="{{ asset('build/assets/app-ZgTC4Owk.css') }}">
@endif
