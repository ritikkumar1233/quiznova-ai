{{-- Distraction-free exam shell (no sidebar / chat). Toasts still work for disqualification notices. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-gradient-to-b from-slate-50 via-indigo-50/30 to-slate-100 text-slate-900" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
        <div class="exam-layout mx-auto min-h-screen w-full max-w-[100vw] px-3 py-4 sm:px-8 sm:py-6 md:px-12 md:py-8">
            {{ $slot }}
        </div>

        @include('partials.flux-toast-stack')

        @fluxScripts
    </body>
</html>
