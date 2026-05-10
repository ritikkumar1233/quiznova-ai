<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        // Get first user message to use as chat name
        $firstMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if ($firstMessage) {
            // Extract first 40 characters and truncate at word boundary
            $name = substr($firstMessage->content, 0, 40);
            if (strlen($firstMessage->content) > 40) {
                $name = substr($name, 0, strrpos($name, ' ')).'...';
            }

            return $name;
        }

        return $this->title;
    }
}
