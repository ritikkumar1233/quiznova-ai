<div>
    @unless($fullPage)
    <div
        x-data="{ open: false }"
        x-on:open-chat.window="open = true"
        class="pointer-events-none fixed bottom-4 right-4 z-[100] flex flex-col items-end gap-3 sm:bottom-6 sm:right-6"
    >
        <div class="pointer-events-auto flex flex-col items-end gap-3">
            <button
                type="button"
                x-on:click="open = !open"
                class="flex size-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-teal-600 text-white shadow-lg ring-2 ring-white/40 transition hover:scale-105 hover:from-teal-600 hover:to-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2"
                aria-label="Toggle AI chat assistant"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.707 9.707 0 01-4-.86L3 20l1.14-4.08A7.953 7.953 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                x-cloak
                class="w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-2xl ring-1 ring-black/5"
                role="dialog"
                aria-modal="false"
                aria-label="AI Chat Assistant"
            >
                <div class="flex items-center justify-between border-b border-gray-200 bg-gradient-to-r from-teal-50 to-blue-50 p-4">
                    <div class="font-semibold text-gray-800">AI Chat Assistant</div>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="startNewChat" class="text-sm font-medium text-teal-600 transition hover:text-teal-700">New</button>
                        <button type="button" x-on:click="open = false" class="text-sm text-gray-400 transition hover:text-gray-600">✕</button>
                    </div>
                </div>

                <div class="relative h-60 bg-gray-50 p-4">
                    <div
                        wire:loading.delay
                        wire:target="sendMessage,handleOpenChat"
                        class="absolute inset-0 z-10 flex items-center justify-center gap-2 bg-gray-50/90 backdrop-blur-[2px]"
                    >
                        <svg class="size-6 shrink-0 animate-spin text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-teal-800">Thinking…</span>
                    </div>

                    <div class="h-full overflow-y-auto pr-1" id="chat-messages">
                        @if($chatError)
                            <div class="mb-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                                ⚠️ {{ $chatError }}
                            </div>
                        @endif
                        @if($session)
                            @foreach($session->messages as $m)
                                <div class="mb-3">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ ucfirst($m->role) }}</div>
                                    <div class="mt-1 rounded-lg p-3 text-sm leading-relaxed" style="background:{{ $m->role === 'assistant' ? '#EBF8F5' : '#E0F2FE' }}; color: {{ $m->role === 'assistant' ? '#0F766E' : '#0C4A6E' }}">{!! nl2br(e($m->content)) !!}</div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-sm italic text-gray-500">Start a chat or click Explain on any question.</div>
                        @endif
                    </div>
                </div>

                <div class="border-t border-gray-200 bg-white p-4">
                    <div class="flex gap-2">
                        <input
                            wire:model.defer="newMessage"
                            type="text"
                            placeholder="Ask something..."
                            class="min-w-0 flex-1 rounded-xl border border-gray-300 p-2.5 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                        />
                        <button
                            wire:click="sendMessage"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage"
                            type="button"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-4 py-2 font-medium text-white transition hover:from-teal-600 hover:to-teal-700 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sendMessage">Send</span>
                            <span wire:loading wire:target="sendMessage" class="inline-flex items-center gap-1">
                                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunless
    @if($fullPage)
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4">
            <div class="mx-auto max-w-7xl">
                <!-- Header is provided by the page layout; do not duplicate here -->

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Sidebar: Chat Sessions (larger) -->
                    <div class="md:col-span-4">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 sticky top-8">
                            <div class="bg-gradient-to-r from-teal-50 to-blue-50 p-4 border-b border-gray-200 flex items-center justify-between">
                                <h2 class="font-semibold text-gray-800">Chats</h2>
                                <button wire:click="startNewChat" class="text-sm font-medium bg-teal-600 text-white px-3 py-1 rounded">+ New Chat</button>
                            </div>
                            <div class="p-4 space-y-2 max-h-[36rem] overflow-y-auto">
                                @forelse($sessions as $s)
                                    <div class="flex items-start justify-between gap-2">
                                        <button 
                                            wire:click="selectSession({{ $s->id }})" 
                                            class="flex-1 text-left p-3 rounded-lg hover:bg-gray-100 transition font-medium text-sm
                                                @if($session && $session->id === $s->id) 
                                                    bg-teal-100 text-teal-900 border-l-4 border-teal-500
                                                @else 
                                                    text-gray-700 hover:border-l-4 hover:border-gray-300
                                                @endif
                                            "
                                        >
                                            <div class="truncate">{{ $s->display_name }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $s->created_at->format('M d, H:i') }}</div>
                                        </button>
                                        <button wire:click="deleteSession({{ $s->id }})" class="ml-2 text-red-500 hover:text-red-700" title="Delete chat">&times;</button>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500 italic p-3">No chats yet. Start a new one!</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Main: Chat Area (larger) -->
                    <div class="md:col-span-8">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 flex flex-col h-96">
                            <!-- Chat Header -->
                            <div class="bg-gradient-to-r from-teal-50 to-blue-50 p-4 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h2 class="font-semibold text-gray-800">
                                        @if($session)
                                            {{ $session->display_name }}
                                        @else
                                            Start a New Chat
                                        @endif
                                    </h2>
                                    @if($session)
                                        <p class="text-xs text-gray-600 mt-1">Started {{ $session->created_at->format('M d, Y at H:i') }}</p>
                                    @endif
                                </div>

                            <!-- Messages Area -->
                            <div class="flex-1 overflow-y-auto p-4 bg-gray-50" id="chat-messages-full">
                                @if($chatError)
                                    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 font-medium">
                                        ⚠️ {{ $chatError }}
                                    </div>
                                @endif

                                @if($session && $session->messages->count() > 0)
                                    @foreach($session->messages as $m)
                                        <div class="mb-4 animate-fadeIn">
                                            <div class="flex items-baseline gap-2 mb-2">
                                                <span class="font-semibold text-sm {{ $m->role === 'assistant' ? 'text-teal-700' : 'text-blue-700' }}">
                                                    {{ ucfirst($m->role) }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ $m->created_at->format('H:i') }}</span>
                                            </div>
                                            <div class="p-4 rounded-lg text-sm leading-relaxed" style="background:{{ $m->role === 'assistant' ? '#EBF8F5' : '#E0F2FE' }}; color: {{ $m->role === 'assistant' ? '#0F766E' : '#0C4A6E' }}">
                                                {!! nl2br(e($m->content)) !!}
                                            </div>
                                        </div>
                                    @endforeach
                                @elseif($session)
                                    <div class="text-center text-gray-500 py-12">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.707 9.707 0 01-4-.86L3 20l1.14-4.08A7.953 7.953 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <p>No messages yet. Start the conversation!</p>
                                    </div>
                                @else
                                    <div class="text-center text-gray-500 py-12">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m0 0h6m0 0h-6m0 0H6m0 0h6" />
                                        </svg>
                                        <p class="font-medium">Select a chat or create a new one</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Input Area -->
                            <div class="border-t border-gray-200 bg-white p-4">
                                <div class="flex gap-3">
                                    <input 
                                        wire:model.defer="newMessage" 
                                        type="text" 
                                        placeholder="Type your message..." 
                                        class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 transition" 
                                        @keydown.enter="$wire.sendMessage()"
                                    />
                                    <button 
                                        wire:click="sendMessage" 
                                        wire:loading.attr="disabled" 
                                        wire:target="sendMessage" 
                                        class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-medium px-6 py-3 rounded-lg transition disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <span wire:loading.remove>Send</span>
                                        <span wire:loading class="animate-spin">⏳</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }

    /* Scrollbar styling */
    #chat-messages::-webkit-scrollbar,
    #chat-messages-full::-webkit-scrollbar {
        width: 6px;
    }

    #chat-messages::-webkit-scrollbar-track,
    #chat-messages-full::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    #chat-messages::-webkit-scrollbar-thumb,
    #chat-messages-full::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    #chat-messages::-webkit-scrollbar-thumb:hover,
    #chat-messages-full::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('messageAppended', function () {
            const el = document.getElementById('chat-messages');
            if (el) {
                setTimeout(() => {
                    el.scrollTop = el.scrollHeight;
                }, 50);
            }

            const full = document.getElementById('chat-messages-full');
            if (full) {
                setTimeout(() => {
                    full.scrollTop = full.scrollHeight;
                }, 50);
            }
        });

        Livewire.on('sessionSelected', function () {
            const el = document.getElementById('chat-messages');
            if (el) {
                setTimeout(() => {
                    el.scrollTop = el.scrollHeight;
                }, 50);
            }

            const full = document.getElementById('chat-messages-full');
            if (full) {
                setTimeout(() => {
                    full.scrollTop = full.scrollHeight;
                }, 50);
            }
        });

        Livewire.on('chatStarted', function () {
            const full = document.getElementById('chat-messages-full');
            if (full) {
                location.reload();
            }
        });
    });
</script>


