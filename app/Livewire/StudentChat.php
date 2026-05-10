<?php

namespace App\Livewire;

use App\Ai\Agents\ExplainAnswerAgent;
use App\Ai\Agents\StudentChatAgent;
use App\Ai\ResolvedProviders;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentChat extends Component
{
    public ?ChatSession $session = null;

    public string $newMessage = '';

    public string $chatError = '';

    public bool $fullPage = false;

    protected $listeners = [
        'openChat' => 'handleOpenChat',
    ];

    public function mount(?int $sessionId = null, $fullPage = false): void
    {
        $this->fullPage = (bool) $fullPage;

        if ($sessionId) {
            $this->session = ChatSession::where('id', $sessionId)
                ->where('user_id', auth()->id())
                ->first();
        }
    }

    public function render(): View
    {
        $sessions = ChatSession::where('user_id', auth()->id())->latest()->get();

        return view('livewire.student-chat', [
            'sessions' => $sessions,
        ]);
    }

    public function startNewChat(): void
    {
        $this->session = ChatSession::create([
            'user_id' => auth()->id(),
            'title' => 'Chat '.now()->format('Y-m-d H:i'),
        ]);

        $this->dispatch('chatStarted', $this->session->id);
    }

    public function handleOpenChat(mixed $payload = null, ?string $question = null, ?string $answer = null): void
    {
        $this->chatError = '';

        if (is_array($payload)) {
            $question = $payload['question'] ?? $question;
            $answer = $payload['answer'] ?? $answer;
            $correctAnswer = $payload['correctAnswer'] ?? null;
        } else {
            $correctAnswer = null;
        }

        // Ensure a session exists
        if (! $this->session) {
            $this->startNewChat();
        }

        $questionText = $question ?: 'Unknown question';
        $answerText = $answer ?: 'Not answered';

        $prompt = "Explain the following question and the student's answer:\nQuestion: {$questionText}\nStudent answer: {$answerText}";

        ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'user',
            'content' => $prompt,
        ]);

        $this->dispatch('messageAppended', $this->session->id);

        $providers = $this->resolveProviders(ExplainAnswerAgent::class);

        if (empty($providers)) {
            $this->chatError = 'No AI providers are configured. Add a provider API key or set OLLAMA_BASE_URL to use Ollama.';

            return;
        }

        try {
            $agent = new ExplainAnswerAgent(
                question: $questionText,
                studentAnswer: $answerText,
                correctAnswer: $correctAnswer,
            );

            $response = $agent->prompt(
                'Explain the student answer with clear reasoning.',
                provider: $providers,
            );

            ChatMessage::create([
                'chat_session_id' => $this->session->id,
                'role' => 'assistant',
                'content' => trim($response->text),
            ]);

            $this->dispatch('messageAppended', $this->session->id);
        } catch (\Throwable $e) {
            logger()->error('Chat explanation failed', ['error' => $e->getMessage()]);
            $this->chatError = 'AI explanation failed. Check your AI provider keys or certificate setup.';
        }
    }

    public function sendMessage(): void
    {
        $this->chatError = '';

        $message = trim($this->newMessage);

        if ($message === '') {
            return;
        }

        if (! $this->session) {
            $this->startNewChat();
        }

        ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $this->newMessage = '';
        $this->dispatch('messageAppended', $this->session->id);

        $providers = $this->resolveProviders(StudentChatAgent::class);

        if (empty($providers)) {
            $this->chatError = 'No AI providers are configured. Add a provider API key or set OLLAMA_BASE_URL to use Ollama.';

            return;
        }

        try {
            $agent = new StudentChatAgent(
                conversation: $this->conversationContext(),
                message: $message,
            );

            $response = $agent->prompt(
                'Respond to the student message based on the conversation.',
                provider: $providers,
            );

            ChatMessage::create([
                'chat_session_id' => $this->session->id,
                'role' => 'assistant',
                'content' => trim($response->text),
            ]);

            $this->chatError = '';
            $this->dispatch('messageAppended', $this->session->id);
        } catch (\Throwable $e) {
            logger()->error('Chat response failed', ['error' => $e->getMessage()]);
            $this->chatError = 'AI chat failed. Check your AI provider keys or certificate setup.';
        }
    }

    public function selectSession(int $id): void
    {
        $this->session = ChatSession::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        $this->dispatch('sessionSelected', $id);
    }

    public function deleteSession(int $id): void
    {
        $session = ChatSession::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $session) {
            return;
        }

        $session->delete();

        // If the deleted session is currently open, clear it
        if ($this->session && $this->session->id === $id) {
            $this->session = null;
        }

        $this->dispatch('sessionDeleted', $id);
    }

    private function resolveProviders(string $agentClass): array
    {
        if ($agentClass::isFaked()) {
            return ['anthropic'];
        }

        return ResolvedProviders::list();
    }

    private function conversationContext(): string
    {
        if (! $this->session) {
            return '';
        }

        $messages = $this->session->messages()->latest()->take(8)->get()->reverse();

        return $messages->map(function ($message) {
            $role = $message->role === 'assistant' ? 'Assistant' : 'Student';

            return "{$role}: {$message->content}";
        })->implode("\n");
    }
}
