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
        class="exam-layout min-h-screen antialiased"
        style="background-color:#F8F9FA; color:#1F2937; font-family:'Lexend',sans-serif;"
    >
        <div class="exam-layout__frame mx-auto w-full min-h-screen max-w-[100vw] px-3 py-4 sm:px-6 sm:py-6 md:px-10 md:py-8 lg:px-12">
            {{ $slot }}
        </div>

        @include('partials.flux-toast-stack')

        @fluxScripts
    </body>
</html>
