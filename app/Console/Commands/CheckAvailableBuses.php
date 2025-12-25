<?php

namespace App\Console\Commands;

use App\Models\TelegramUser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class CheckAvailableBuses extends Command
{
    protected $signature = 'buses:check {--date= : Date to check in Y-m-d format}';

    protected $description = 'Check available bus tickets from AvtoTicket API and notify via Telegram';

    private const FROM_STATION = '1726'; // Toshkent

    private const TO_STATION = '1722223'; // Sherobod

    private const BEARER_TOKEN = 'ad28f8b5-54e7-4084-8a42-1b9d10fd2242';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->addDays(1)->format('Y-m-d');

        $this->info("Checking buses for: {$date}");

        $users = TelegramUser::where('notify_when_found', true)->get();

        if ($users->isEmpty()) {
            $this->warn('No active Telegram users found.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} active users.");

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'uz',
                'Authorization' => 'Bearer '.self::BEARER_TOKEN,
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json;charset=UTF-8',
                'Origin' => 'https://avtoticket.uz',
                'Referer' => 'https://avtoticket.uz/',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-site',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'sec-ch-ua' => '"Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"macOS"',
            ])->post('https://wapi.avtoticket.uz/api/api-trips', [
                'date' => $date,
                'from' => self::FROM_STATION,
                'to' => self::TO_STATION,
                'days' => 1,
            ]);

            if (! $response->successful()) {
                Log::error('AvtoTicket API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->error('Failed to fetch data from API');

                return self::FAILURE;
            }

            $data = $response->json();

            if (! $data['success']) {
                $this->error('API returned success=false');

                return self::FAILURE;
            }
            $dayData = $data['data'][1] ?? null;

            if (! $dayData) {
                $this->warn('No data found for the specified date');

                return self::SUCCESS;
            }

            $trips = $dayData['trips'] ?? [];

            if (empty($trips)) {
                $this->warn('No buses found');

                // Send "not found" notifications to users who enabled it
                foreach ($users as $user) {
                    if ($user->notify_when_not_found) {
                        try {
                            $this->sendNotFoundNotification($user->chat_id, $date);
                            $this->info("  → Sent 'not found' to user: {$user->first_name} ({$user->chat_id})");

                        }catch (Exception $e) {
                            Log::error('Error sending not found notification', [
                                'chat_id' => $user->chat_id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $this->error("  → Failed to send 'not found' to user: {$user->first_name} ({$user->chat_id})");
                        }
                    }
                }

                return self::SUCCESS;
            }

            $availableTrips = $this->findAvailableTrips($trips, $date);

            if (empty($availableTrips)) {
                $this->warn('No available seats found');

                // Send "not found" notifications to users who enabled it
                foreach ($users as $user) {
                    if ($user->notify_when_not_found) {
                        try {
                            $this->sendNotFoundNotification($user->chat_id, $date);
                            $this->info("  → Sent 'not found' to user: {$user->first_name} ({$user->chat_id})");
                        }catch (Exception $e) {
                            Log::error('Error sending not found notification', [
                                'chat_id' => $user->chat_id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            $this->error("  → Failed to send 'not found' to user: {$user->first_name} ({$user->chat_id})");
                        }
                    }
                }

                return self::SUCCESS;
            }

            $count = count($availableTrips);
            $this->info("✓ Found $count available buses!");

            foreach ($users as $user) {
                if ($user->notify_when_found) {
                    try {
                        $this->sendTelegramNotification($user->chat_id, $availableTrips);
                        $this->info("  → Sent to user: {$user->first_name} ({$user->chat_id})");
                    }catch (Exception $e) {
                        Log::error('Error sending available trips notification', [
                            'chat_id' => $user->chat_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $this->error("  → Failed to send to user: {$user->first_name} ({$user->chat_id})");
                    }
                }
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            Log::error('Error checking buses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function findAvailableTrips(array $trips, string $date): array
    {
        $available = [];

        foreach ($trips as $trip) {
            $availableSeats = $trip['seats'] - $trip['sold_seats'];
            if ($availableSeats > 0) {
                $available[] = [
                    'date' => $date,
                    'departure_at' => $trip['departure_at'],
                    'route_name' => $trip['route_name_uz'] ?? $trip['route_name'],
                    'from_name' => $trip['from_name_uz'] ?? $trip['from_name_ru'],
                    'to_name' => $trip['to_name_uz'] ?? $trip['to_name_ru'],
                    'price' => $trip['price'],
                    'available_seats' => $availableSeats,
                    'total_seats' => $trip['seats'],
                    'bus_model' => $trip['bus_model_name'],
                ];
            }
        }

        return $available;
    }

    protected function sendTelegramNotification(string $chatId, array $trips): void
    {
        $bot = app(Nutgram::class);
        $message = $this->formatMessage($trips);

        $bot->sendMessage(
            text: $message,
            chat_id: $chatId,
            parse_mode: 'HTML'
        );
    }

    protected function sendNotFoundNotification(string $chatId, string $date): void
    {
        $bot = app(Nutgram::class);
        $formattedDate = date('d.m.Y', strtotime($date));

        $message = "❌ <b>Avtobus topilmadi</b>\n\n";
        $message .= "📅 <b>Sana:</b> {$formattedDate}\n";
        $message .= "📍 <b>Yo'nalish:</b> Toshkent → Sherobod\n\n";
        $message .= "Hozircha bo'sh joylar mavjud emas. Biz sizga doimiy ravishda tekshirib turamiz va bo'sh joylar paydo bo'lganda darhol xabar beramiz! ✅";

        $bot->sendMessage(
            text: $message,
            chat_id: $chatId,
            parse_mode: 'HTML'
        );
    }

    protected function formatMessage(array $trips): string
    {
        $firstTrip = $trips[0];

        $message = "🚌 <b>Bo'sh joylar topildi!</b>\n\n";
        $message .= "📍 <b>Yo'nalish:</b> {$firstTrip['from_name']} → {$firstTrip['to_name']}\n";
        $message .= '📅 <b>Sana:</b> '.date('d.m.Y', strtotime($firstTrip['date']))."\n\n";

        foreach ($trips as $trip) {
            $message .= "━━━━━━━━━━━━━━━━━━\n";
            $message .= "🚌 <b>{$trip['route_name']}</b>\n";
            $message .= "⏰ <b>Jo'nash:</b> ".date('H:i', strtotime($trip['departure_at']))."\n";
            $message .= "🎟 <b>Bo'sh joylar:</b> {$trip['available_seats']}/{$trip['total_seats']}\n";
            $message .= '💰 <b>Narx:</b> '.number_format($trip['price'], 0, '.', ' ')." so'm\n";
            $message .= "🚐 <b>Avtobus:</b> {$trip['bus_model']}\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= '🔗 <a href="https://avtoticket.uz">Chiptalarni sotib olish</a>';

        return $message;
    }
}
