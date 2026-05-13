<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ config('app.name') }} — AI-Powered Adaptive Learning Platform. Create quizzes, deliver secure exams, and analyze performance with AI.">
        <title>{{ config('app.name') }} — AI-Powered Adaptive Learning Platform</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..800;1,14..32,300..800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes nova-fade-up {
                from { opacity: 0; transform: translateY(16px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .nova-animate {
                animation: nova-fade-up 0.65s ease-out forwards;
            }
            .nova-delay-1 { animation-delay: 0.08s; opacity: 0; }
            .nova-delay-2 { animation-delay: 0.16s; opacity: 0; }
            .nova-delay-3 { animation-delay: 0.24s; opacity: 0; }
            .nova-delay-4 { animation-delay: 0.32s; opacity: 0; }
            details.nova-faq > summary { list-style: none; }
            details.nova-faq > summary::-webkit-details-marker { display: none; }
        </style>
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased" style="font-family:'Inter',ui-sans-serif,system-ui,sans-serif;">

        <header class="glass-bar sticky top-0 z-50">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-app-logo-icon class="size-9" />
                    <div class="leading-tight">
                        <span class="block text-base font-bold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                        <span class="hidden text-[0.65rem] font-medium uppercase tracking-wider text-indigo-600 sm:block">Adaptive Learning Platform</span>
                    </div>
                </a>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a href="#features" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 md:inline">Features</a>
                        <a href="#faq" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 md:inline">FAQ</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-teal text-sm">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-teal text-sm">Get Started</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        {{-- 1. Hero --}}
        <section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 via-white to-slate-50 px-4 pb-24 pt-14 sm:px-6 sm:pt-20">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_90%_60%_at_50%_-10%,rgba(99,102,241,0.22),transparent)]"></div>
            <div class="nova-glow-orb -left-24 top-16 size-[26rem] bg-indigo-400"></div>
            <div class="nova-glow-orb -right-16 top-32 size-[20rem] bg-violet-400"></div>

            <div class="relative z-10 mx-auto max-w-5xl text-center nova-animate">
                <p class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">AI-Powered Adaptive Learning Platform</p>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl md:text-6xl md:leading-[1.06]">
                    Transform Learning with<br>
                    <span class="nova-gradient-text">AI-Powered Quizzes</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
                    {{ config('app.name') }} helps organizations build assessments, guide learners with an intelligent tutor, and measure outcomes in real time—securely and at scale.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="btn-teal px-8 py-3.5 text-base">Get Started</a>
                        <a href="#features" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-8 py-3.5 text-base font-semibold text-slate-800 shadow-md shadow-slate-900/5 backdrop-blur transition hover:border-indigo-200 hover:shadow-lg">Explore Features</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-teal px-8 py-3.5 text-base">Open Dashboard</a>
                        <a href="#features" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-8 py-3.5 text-base font-semibold text-slate-800 shadow-md backdrop-blur transition hover:border-indigo-200">Explore Features</a>
                    @endguest
                </div>
            </div>

            <div class="relative z-10 mx-auto mt-16 max-w-5xl px-1 nova-animate nova-delay-1">
                <div class="rounded-3xl border border-slate-200/90 bg-white/80 p-2 shadow-2xl shadow-indigo-950/10 backdrop-blur-xl">
                    <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-950 p-6 sm:p-8">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur-md transition hover:bg-white/15">
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-cyan-400/20 text-cyan-300">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75l9-9z"/></svg>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-cyan-300/90">AI engine</p>
                                <p class="mt-1 text-lg font-bold text-white">Smart generation</p>
                                <p class="text-sm text-indigo-200/75">Questions &amp; hints tuned to your curriculum</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur-md transition hover:bg-white/15">
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-violet-400/20 text-violet-200">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-violet-300/90">Integrity</p>
                                <p class="mt-1 text-lg font-bold text-white">Proctored flow</p>
                                <p class="text-sm text-indigo-200/75">Fullscreen &amp; focus monitoring</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/10 p-4 backdrop-blur-md transition hover:bg-white/15">
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-indigo-400/20 text-indigo-200">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/></svg>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-300/90">Analytics</p>
                                <p class="mt-1 text-lg font-bold text-white">Live insights</p>
                                <p class="text-sm text-indigo-200/75">Attempts, scores &amp; AI usage</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Features --}}
        <section id="features" class="border-t border-slate-200/80 bg-white px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="text-center nova-animate nova-delay-2">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Everything you need to teach and learn</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-slate-600">Purpose-built tools for authoring, delivery, and analysis—wrapped in a polished experience your users will trust.</p>
                </div>

                @php
                    $featureIcons = [
                        'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.847a4.5 4.5 0 003.09 3.09L15.75 12l-2.847.813a4.5 4.5 0 00-3.09 3.09z',
                        'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                        'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
                        'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
                        'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
                        'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
                    ];
                    $features = [
                        ['title' => 'AI Question Generator', 'body' => 'Generate and refine question banks with AI assistance—aligned to topics, difficulty, and learning goals.'],
                        ['title' => 'Adaptive Difficulty', 'body' => 'Surface the right level of challenge based on performance signals so practice stays efficient.'],
                        ['title' => 'AI Chatbot Tutor', 'body' => 'On-demand explanations on dashboards and results—automatically hidden during secure exams.'],
                        ['title' => 'Real-Time Analytics', 'body' => 'Live views of attempts, scores, and activity so instructors can respond without waiting for exports.'],
                        ['title' => 'Anti-Cheating System', 'body' => 'Fullscreen enforcement, violation tracking, and structured submission flows learners cannot bypass casually.'],
                        ['title' => 'Personalized Learning', 'body' => 'Connect results to recommendations and review flows that help each learner close specific gaps.'],
                    ];
                @endphp

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as $i => $f)
                        <div class="group rounded-2xl border border-slate-200/90 bg-white/70 p-6 shadow-lg shadow-slate-900/5 backdrop-blur-md transition duration-300 hover:-translate-y-1 hover:border-indigo-200/80 hover:shadow-xl hover:shadow-indigo-950/10">
                            <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100 text-indigo-700 ring-1 ring-indigo-200/50 transition group-hover:scale-105">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $featureIcons[$i] }}"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $f['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $f['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 3. How it works --}}
        <section id="how-it-works" class="border-t border-slate-200/80 bg-gradient-to-b from-slate-50 to-indigo-50/40 px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">How it works</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate-600">From first quiz to insight—three clear steps.</p>
                </div>

                <div class="relative mt-16 grid gap-10 md:grid-cols-3 md:gap-6">
                    <div class="pointer-events-none absolute left-0 right-0 top-12 hidden h-0.5 bg-gradient-to-r from-indigo-200 via-violet-200 to-cyan-200 md:block" style="margin-left:10%;margin-right:10%;"></div>

                    @foreach ([
                        ['step' => '1', 'title' => 'Create Quiz', 'body' => 'Author items manually or with AI, set timing, publish when ready.'],
                        ['step' => '2', 'title' => 'Take Adaptive Test', 'body' => 'Learners complete secure attempts with integrity controls enabled.'],
                        ['step' => '3', 'title' => 'Analyze Performance', 'body' => 'Review scores, AI explanations, and usage analytics in one place.'],
                    ] as $item)
                        <div class="relative flex flex-col items-center text-center">
                            <div class="relative z-10 flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-xl font-bold text-white shadow-lg shadow-indigo-600/30 ring-4 ring-white">
                                {{ $item['step'] }}
                            </div>
                            <h3 class="mt-6 text-xl font-bold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 max-w-xs text-sm text-slate-600">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 4. Stats --}}
        <section id="stats" class="border-t border-slate-200/80 bg-gradient-to-br from-indigo-950 via-slate-900 to-violet-950 px-4 py-20 text-white sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight">Trusted at scale</h2>
                    <p class="mx-auto mt-3 max-w-xl text-indigo-200/85">Representative platform metrics—connect your own analytics backend when you deploy.</p>
                </div>
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Students Active', 'value' => '12.4k', 'sub' => 'Monthly active learners'],
                        ['label' => 'Quizzes Conducted', 'value' => '48k', 'sub' => 'Completed attempts'],
                        ['label' => 'Accuracy Improvement', 'value' => '+34%', 'sub' => 'Avg. lift after 4 sessions'],
                        ['label' => 'AI Responses Generated', 'value' => '1.2M', 'sub' => 'Tutor & grading assists'],
                    ] as $stat)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-md transition hover:bg-white/10">
                            <p class="text-3xl font-black tracking-tight text-cyan-300 sm:text-4xl">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-xs text-indigo-200/70">{{ $stat['sub'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 5. Testimonials --}}
        <section class="border-t border-slate-200/80 bg-white px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Teams rely on {{ config('app.name') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate-600">What instructors and operators say about the experience.</p>
                </div>
                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['q' => '“The clearest exam experience we have rolled out—students understand the flow on day one.”', 'name' => 'Alex Rivera', 'role' => 'Head of L&D', 'initials' => 'AR', 'stars' => 5],
                        ['q' => '“AI tutor on results is a differentiator. Support tickets dropped the week we turned it on.”', 'name' => 'Morgan Chen', 'role' => 'Engineering Educator', 'initials' => 'MC', 'stars' => 5],
                        ['q' => '“Integrity tooling is strict without feeling hostile. That balance matters for our brand.”', 'name' => 'Jordan Okon', 'role' => 'Certification Lead', 'initials' => 'JO', 'stars' => 5],
                    ] as $t)
                        <div class="flex flex-col rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/80 p-6 shadow-lg shadow-slate-900/5">
                            <div class="mb-4 flex text-amber-400" aria-label="{{ $t['stars'] }} out of 5 stars">
                                @for ($s = 0; $s < $t['stars']; $s++)
                                    <svg class="size-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <p class="flex-1 text-sm leading-relaxed text-slate-700">{{ $t['q'] }}</p>
                            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                                <div class="flex size-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-bold text-white">{{ $t['initials'] }}</div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $t['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $t['role'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 6. Platform preview --}}
        <section class="border-t border-slate-200/80 bg-slate-50 px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Inside the platform</h2>
                    <p class="mx-auto mt-3 max-w-xl text-slate-600">A quick look at analytics, assessments, and the AI assistant.</p>
                </div>
                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-xl shadow-slate-900/5 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Analytics</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">Performance overview</p>
                        <div class="mt-4 space-y-3 rounded-xl bg-slate-50 p-4">
                            <div class="flex justify-between text-sm"><span class="text-slate-500">Completion</span><span class="font-semibold text-slate-900">87%</span></div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full w-[87%] rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"></div></div>
                            <div class="flex justify-between text-sm"><span class="text-slate-500">Avg. score</span><span class="font-semibold text-slate-900">76%</span></div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-xl shadow-slate-900/5 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-violet-600">Exam</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">Focused attempt UI</p>
                        <div class="mt-4 space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-900">Question 3 of 12 · Timer 18:42</div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">Single-select options · Flag for review</div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-xl shadow-slate-900/5 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-cyan-600">AI Tutor</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">Chatbot preview</p>
                        <div class="mt-4 space-y-2 rounded-xl bg-gradient-to-br from-indigo-50 to-cyan-50 p-4">
                            <div class="rounded-lg bg-white/90 px-3 py-2 text-xs text-slate-700 shadow-sm">“Explain why my answer missed the key concept…”</div>
                            <div class="rounded-lg bg-indigo-600 px-3 py-2 text-xs text-white shadow-sm">Step-by-step reasoning with examples →</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 7. FAQ --}}
        <section id="faq" class="border-t border-slate-200/80 bg-white px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Frequently asked questions</h2>
                    <p class="mt-3 text-slate-600">Quick answers about security, AI, and deployment.</p>
                </div>
                <div class="mt-10 space-y-3">
                    @foreach ([
                        ['q' => 'Is the AI tutor available during live exams?', 'a' => 'No. The tutor is available on learning surfaces such as dashboards and results. Active exam sessions use a distraction-free layout without chat to protect integrity.'],
                        ['q' => 'What anti-cheating measures are supported?', 'a' => 'The platform supports fullscreen enforcement, violation tracking for tab switches and exit events, and structured submission flows. Configure policies to match your institution’s standards.'],
                        ['q' => 'Can we use our own AI providers?', 'a' => 'Yes. Connect supported providers via your environment configuration so generation, hints, and chat run on the stack you approve.'],
                        ['q' => 'Is '.config('app.name').' suitable for production traffic?', 'a' => 'The stack is built on Laravel and Livewire with a modular UI. Scale web workers, queues, and databases like any production Laravel deployment.'],
                    ] as $faq)
                        <details class="nova-faq group rounded-2xl border border-slate-200/90 bg-slate-50/80 backdrop-blur transition open:border-indigo-200 open:bg-white open:shadow-md">
                            <summary class="flex cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-slate-900">
                                {{ $faq['q'] }}
                                <svg class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="border-t border-slate-100 px-5 pb-4 pt-2 text-sm leading-relaxed text-slate-600">
                                {{ $faq['a'] }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="border-t border-slate-200/80 bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-700 px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-3xl text-center text-white">
                <h2 class="text-3xl font-bold tracking-tight">Ready to modernize assessments?</h2>
                <p class="mt-4 text-indigo-100">Launch {{ config('app.name') }} for your organization—secure delivery, AI assistance where it counts, and analytics your team can act on.</p>
                @guest
                    <a href="{{ route('register') }}" class="mt-8 inline-flex rounded-xl bg-white px-10 py-4 text-base font-bold text-indigo-700 shadow-lg transition hover:bg-indigo-50">Get Started Free</a>
                @else
                    <a href="{{ route('dashboard') }}" class="mt-8 inline-flex rounded-xl bg-white px-10 py-4 text-base font-bold text-indigo-700 shadow-lg transition hover:bg-indigo-50">Go to Dashboard</a>
                @endguest
            </div>
        </section>

        {{-- 8. Footer --}}
        <footer class="border-t border-slate-200 bg-slate-900 px-4 py-14 text-slate-300 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div class="flex flex-col gap-10 md:flex-row md:justify-between">
                    <div class="max-w-xs">
                        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                            <x-app-logo-icon class="size-9" />
                            <span class="text-lg font-bold text-white">{{ config('app.name') }}</span>
                        </a>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">AI-Powered Adaptive Learning Platform for modern teams.</p>
                        <div class="mt-5 flex gap-3">
                            <a href="#" class="flex size-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 text-slate-400 transition hover:border-indigo-500 hover:text-white" aria-label="LinkedIn">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                            <a href="#" class="flex size-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 text-slate-400 transition hover:border-indigo-500 hover:text-white" aria-label="X">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="#" class="flex size-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 text-slate-400 transition hover:border-indigo-500 hover:text-white" aria-label="GitHub">
                                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-10 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Product</p>
                            <ul class="mt-4 space-y-2 text-sm">
                                <li><a href="#features" class="transition hover:text-white">Features</a></li>
                                <li><a href="#how-it-works" class="transition hover:text-white">How it works</a></li>
                                <li><a href="#stats" class="transition hover:text-white">Impact</a></li>
                                <li><a href="#faq" class="transition hover:text-white">FAQ</a></li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Account</p>
                            <ul class="mt-4 space-y-2 text-sm">
                                @if (Route::has('login'))
                                    <li><a href="{{ route('login') }}" class="transition hover:text-white">Log in</a></li>
                                @endif
                                @if (Route::has('register'))
                                    <li><a href="{{ route('register') }}" class="transition hover:text-white">Sign up</a></li>
                                @endif
                                <li><a href="{{ route('home') }}" class="transition hover:text-white">Home</a></li>
                            </ul>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Legal</p>
                            <ul class="mt-4 space-y-2 text-sm">
                                <li><a href="#" class="transition hover:text-white">Privacy</a></li>
                                <li><a href="#" class="transition hover:text-white">Terms</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-12 border-t border-slate-800 pt-8 text-center text-xs text-slate-500">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </div>
        </footer>
    </body>
</html>
