<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSearch extends Model
{
    /** @use HasFactory<\Database\Factories\TicketSearchFactory> */
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'from_station_code',
        'from_station_name',
        'to_station_code',
        'to_station_name',
        'departure_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
