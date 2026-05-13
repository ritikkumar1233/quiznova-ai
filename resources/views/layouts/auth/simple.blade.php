<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased" style="font-family:'Inter',ui-sans-serif,system-ui,sans-serif;">

        <div class="flex min-h-screen">

            {{-- Brand story + AI visual (desktop) --}}
            <div class="relative hidden w-[min(28rem,42vw)] shrink-0 flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-900 px-10 py-12 text-white lg:flex">
                <div class="pointer-events-none absolute -left-24 top-1/4 size-80 rounded-full bg-cyan-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -right-16 bottom-0 size-72 rounded-full bg-violet-500/25 blur-3xl"></div>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3" wire:navigate>
                    <x-app-logo-icon class="size-11" />
                    <span class="text-xl font-bold tracking-tight">{{ config('app.name') }}</span>
                </a>

                <div class="relative z-10 flex flex-col gap-10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300/90">Production-grade</p>
                        <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight">
                            Learn faster with an AI tutor at your side.
                        </h1>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-indigo-100/85">
                            {{ config('app.name') }} blends secure assessments, adaptive practice, and real-time AI support—built for serious educators and ambitious students.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-md">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-indigo-500 text-lg font-bold text-slate-950">
                                AI
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Guided explanations</p>
                                <p class="text-xs text-indigo-200/80">Ask the tutor after every attempt—never during a live exam.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="relative z-10 text-xs text-indigo-300/60">
                    © {{ date('Y') }} {{ config('app.name') }}
                </p>
            </div>

            {{-- Form column --}}
            <div class="relative flex flex-1 flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 px-4 py-10 sm:px-8">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(99,102,241,0.12),transparent)]"></div>

                <a href="{{ route('home') }}" class="relative z-10 mb-8 flex items-center gap-2 lg:hidden" wire:navigate>
                    <x-app-logo-icon class="size-9" />
                    <span class="text-lg font-bold text-slate-900">{{ config('app.name') }}</span>
                </a>

                <div class="relative z-10 w-full max-w-md">
                    <x-nova.glass-panel>
                        {{ $slot }}
                    </x-nova.glass-panel>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
