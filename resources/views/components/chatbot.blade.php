{{-- Global AI tutor: student shell only; hidden on active exam route and exam layout. --}}
@auth
    @if(auth()->user()->isStudent() && ! request()->routeIs('student.exams.take'))
        <livewire:student-chat wire:key="student-chat-global" />
    @endif
@endauth
