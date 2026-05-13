{{--
    Global AI chat launcher for signed-in students.

    Included from `layouts/app/sidebar.blade.php` so it appears on dashboard, attempts,
    results, leaderboard, etc. Active exams use `layouts.exam`, which does not load this
    shell — the chatbot never mounts during an attempt. The route guard below is an extra
    safeguard if layouts ever change.
--}}
@auth
    @if(auth()->user()->isStudent() && ! request()->routeIs('student.exams.take'))
        <livewire:student-chat wire:key="student-chat-global" />
    @endif
@endauth
