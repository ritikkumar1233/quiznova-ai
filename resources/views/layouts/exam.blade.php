{{--
    Exam mode layout — full-page assessment shell only.

    Intentionally excludes the dashboard Flux sidebar, mobile header, profile menus,
    and any primary navigation so students cannot pivot away during a timed attempt.

    Anti-cheat UI (fullscreen prompts, tab-switch warnings, violation modal) is rendered
    by the take-exam Livewire page inside {{ $slot }}.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body
        class="exam-layout min-h-screen antialiased bg-gradient-to-b from-slate-50 via-indigo-50/30 to-slate-100 text-slate-900"
        style="font-family:'Inter', ui-sans-serif, system-ui, sans-serif;"
    >
        <div class="exam-layout__frame mx-auto min-h-screen w-full max-w-[100vw] px-3 py-4 sm:px-8 sm:py-6 md:px-12 md:py-8">
            {{ $slot }}
        </div>

        @include('partials.flux-toast-stack')

        @fluxScripts
    </body>
</html>
