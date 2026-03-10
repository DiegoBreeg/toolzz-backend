<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Get the user who sent the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope to filter conversation between two users.
     * Returns messages where either user is sender or receiver, ordered by most recent.
     */
    public function scopeBetweenUsers(Builder $query, int $user1, int $user2): Builder
    {
        return $query->where(function (Builder $q) use ($user1, $user2) {
            $q->where(function (Builder $inner) use ($user1, $user2) {
                $inner->where('sender_id', $user1)
                      ->where('receiver_id', $user2);
            })->orWhere(function (Builder $inner) use ($user1, $user2) {
                $inner->where('sender_id', $user2)
                      ->where('receiver_id', $user1);
            });
        })->orderBy('created_at', 'desc');
    }
}
