<div>
    @unless($fullPage)
    <div x-data="{ open: false }" x-on:open-chat.window="open = true" class="fixed right-4 bottom-4 z-50">
        <div class="flex flex-col items-end gap-2">
            <button x-on:click="open = !open" class="bento-flat p-3 rounded-full shadow-lg bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.707 9.707 0 01-4-.86L3 20l1.14-4.08A7.953 7.953 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>

            <div x-show="open" x-transition class="w-96 bg-white rounded-lg shadow-xl overflow-hidden border border-gray-200">
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-blue-50 flex items-center justify-between">
                    <div class="font-semibold text-gray-800">AI Chat Assistant</div>
                    <div class="flex items-center gap-3">
                        <button wire:click="startNewChat" class="text-sm font-medium text-teal-600 hover:text-teal-700 transition">New</button>
                        <button x-on:click="open = false" class="text-sm text-gray-400 hover:text-gray-600 transition">✕</button>
                    </div>
                </div>

                <div class="p-4 h-60 overflow-y-auto bg-gray-50" id="chat-messages">
                    @if($chatError)
                        <div class="mb-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 font-medium">
                            ⚠️ {{ $chatError }}
                        </div>
                    @endif
                    @if($session)
                        @foreach($session->messages as $m)
                            <div class="mb-3">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($m->role) }}</div>
                                <div class="p-3 rounded-lg mt-1 text-sm leading-relaxed" style="background:{{ $m->role === 'assistant' ? '#EBF8F5' : '#E0F2FE' }}; color: {{ $m->role === 'assistant' ? '#0F766E' : '#0C4A6E' }}">{!! nl2br(e($m->content)) !!}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-sm text-gray-500 italic">Start a chat or click Explain on any question.</div>
                    @endif
                </div>

                <div class="p-4 border-t border-gray-200 bg-white">
                    <div class="flex gap-2">
                        <input wire:model.defer="newMessage" type="text" placeholder="Ask something..." class="flex-1 p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" />
                        <button wire:click="sendMessage" wire:loading.attr="disabled" wire:target="sendMessage" class="btn btn-primary bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-medium px-4 py-2 rounded-lg transition disabled:opacity-50">
                            <span wire:loading.remove>Send</span>
                            <span wire:loading>⏳</span>
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
    document.addEventListener('livewire:load', function () {
        Livewire.on('messageAppended', function (sessionId) {
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

        Livewire.on('sessionSelected', function (id) {
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

        Livewire.on('chatStarted', function (id) {
            const full = document.getElementById('chat-messages-full');
            if (full) {
                // Trigger a re-render to show new chat in sidebar
                location.reload();
            }
        });
    });
</script>


