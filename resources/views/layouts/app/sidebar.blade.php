<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-[var(--color-canvas)] text-slate-900" style="font-family:'Inter',ui-sans-serif,system-ui,sans-serif;">

        {{-- ── Sidebar — QuizNova AI ─────────────────────── --}}
        <flux:sidebar sticky collapsible="mobile"
            class="border-r border-slate-200/90 bg-white/95 shadow-sm shadow-indigo-950/5 backdrop-blur-md">

            {{-- Brand --}}
            <flux:sidebar.header class="border-b border-slate-100 px-4 py-3.5">
                <a href="{{ route('dashboard') }}" wire:navigate class="group flex items-center gap-2.5">
                    <x-app-logo-icon class="size-8 shrink-0 transition group-hover:scale-[1.02]" />
                    <span class="text-base font-bold tracking-tight text-slate-900">
                        {{ config('app.name') }}
                    </span>
                </a>
                <flux:sidebar.collapse class="lg:hidden text-slate-400" />
            </flux:sidebar.header>

            {{-- Navigation --}}
            <flux:sidebar.nav class="px-2 py-3">
                @auth
                    @if (auth()->user()->isTeacher())
                        <div class="px-2 pb-1.5 text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-400">
                            Teaching
                        </div>
                        <flux:sidebar.item
                            icon="academic-cap"
                            :href="route('teacher.exams.index')"
                            :current="request()->routeIs('teacher.exams.*')"
                            wire:navigate
                        >
                            My Exams
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="rectangle-stack"
                            :href="route('teacher.questions.index')"
                            :current="request()->routeIs('teacher.questions.*')"
                            wire:navigate
                        >
                            Question Bank
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="chart-bar-square"
                            :href="route('teacher.ai-usage')"
                            :current="request()->routeIs('teacher.ai-usage')"
                            wire:navigate
                        >
                            AI Usage
                        </flux:sidebar.item>
                    @else
                        <div class="px-2 pb-1.5 text-[0.6875rem] font-semibold uppercase tracking-wider text-slate-400">
                            Learning
                        </div>
                        <flux:sidebar.item
                            icon="book-open"
                            :href="route('student.dashboard')"
                            :current="request()->routeIs('student.dashboard', 'student.exams.*', 'student.attempts.results')"
                            wire:navigate
                        >
                            Available Exams
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="clock"
                            :href="route('student.attempts')"
                            :current="request()->routeIs('student.attempts')"
                            wire:navigate
                        >
                            My Attempts
                        </flux:sidebar.item>
                    @endif
                @endauth
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Role badge --}}
            @auth
                <div class="px-3 pb-3">
                    <div class="flex items-center gap-2 rounded-xl border border-indigo-200/80 bg-gradient-to-r from-indigo-50 to-violet-50 px-3 py-2">
                        <svg class="size-3.5 shrink-0 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs font-semibold capitalize text-indigo-900">
                            {{ auth()->user()->role->value }}
                        </span>
                    </div>
                </div>
            @endauth

            {{-- User menu (desktop) --}}
            <div class="border-t border-slate-100 p-2">
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            </div>
        </flux:sidebar>

        {{-- ── Mobile header ────────────────────────────── --}}
        <flux:header class="lg:hidden glass-bar">
            <flux:sidebar.toggle icon="bars-2" inset="left" class="text-slate-600" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid flex-1 leading-tight">
                                <span class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- ── Page slot ────────────────────────────────── --}}
        <flux:main class="px-4 py-8 sm:px-8 sm:py-10 lg:px-10">
            {{ $slot }}
        </flux:main>

        @include('partials.flux-toast-stack')

        <x-chatbot />

        @fluxScripts
    </body>
</html>
