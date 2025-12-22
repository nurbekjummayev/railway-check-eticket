<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    /** @use HasFactory<\Database\Factories\TelegramUserFactory> */
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'is_active',
        'notify_when_found',
        'notify_when_not_found',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notify_when_found' => 'boolean',
            'notify_when_not_found' => 'boolean',
        ];
    }

    public function ticketSearches()
    {
        return $this->hasMany(TicketSearch::class);
    }

    public function activeTicketSearches()
    {
        return $this->hasMany(TicketSearch::class)->where('is_active', true);
    }
}
