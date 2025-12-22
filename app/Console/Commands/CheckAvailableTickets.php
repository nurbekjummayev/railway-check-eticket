<?php

namespace App\Console\Commands;

use App\Models\TelegramUser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class CheckAvailableTickets extends Command
{
    protected $signature = 'tickets:check {--date= : Date to check in Y-m-d format}';

    protected $description = 'Check available tickets from eTicket API and notify via Telegram';

    private const DEP_STATION_CODE = '2900000';

    private const ARV_STATION_CODE = '2900864';

    private const COOKIE = '__stripe_mid=669fe4d0-c70b-408c-9ad2-c5678c4661f8b20899; G_ENABLED_IDPS=google; _ga=GA1.1.707157725.1766258295; _ga_K4H2SZ7MWK=GS2.1.s1766258295$o1$g1$t1766259378$j60$l0$h0; XSRF-TOKEN=27f8fa80-5d12-425f-a8d5-fa648ca9967b; __stripe_sid=1a8cf5cf-8911-4d63-b7c2-593e7ef98e252902ab; _ga_R5LGX7P1YR=GS2.1.s1766427969$o3$g1$t1766427990$j39$l0$h0';

    private const XSRF_TOKEN = '27f8fa80-5d12-425f-a8d5-fa648ca9967b';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->addDays(1)->format('Y-m-d');

        $this->info("Checking tickets for: {$date}");

        $users = TelegramUser::where('notify_when_found', true)->get();

        if ($users->isEmpty()) {
            $this->warn('No active Telegram users found.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} active users.");

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'uz',
                'Connection' => 'keep-alive',
                'Content-Type' => 'application/json',
                'Origin' => 'https://eticket.railway.uz',
                'Referer' => 'https://eticket.railway.uz/uz/pages/trains-page',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-XSRF-TOKEN' => self::XSRF_TOKEN,
                'device-type' => 'BROWSER',
                'sec-ch-ua' => '"Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"macOS"',
                'Cookie' => self::COOKIE,
            ])->post('https://eticket.railway.uz/api/v3/handbook/trains/list', [
                'directions' => [
                    'forward' => [
                        'date' => $date,
                        'depStationCode' => self::DEP_STATION_CODE,
                        'arvStationCode' => self::ARV_STATION_CODE,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('eTicket API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->error('Failed to fetch data from API');

                return self::FAILURE;
            }

            $data = $response->json();
            $trains = $data['data']['directions']['forward']['trains'] ?? [];

            if (empty($trains)) {
                $this->warn('No trains found');

                return self::SUCCESS;
            }

            $availableTickets = $this->findAvailableTickets($trains, $date);

            if (empty($availableTickets)) {
                $this->warn('No available seats found');

                // Send "not found" notifications to users who enabled it
                foreach ($users as $user) {
                    if ($user->notify_when_not_found) {
                        $this->sendNotFoundNotification($user->chat_id, $date);
                        $this->info("  → Sent 'not found' to user: {$user->first_name} ({$user->chat_id})");
                    }
                }

                return self::SUCCESS;
            }

            $count = count($availableTickets);
            $this->info("✓ Found $count available tickets!");

            foreach ($users as $user) {
                if ($user->notify_when_found) {
                    $this->sendTelegramNotification($user->chat_id, $availableTickets);
                    $this->info("  → Sent to user: {$user->first_name} ({$user->chat_id})");
                }
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            Log::error('Error checking tickets', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function findAvailableTickets(array $trains, string $date): array
    {
        $available = [];

        foreach ($trains as $train) {
            $cars = [];
            $hasAvailableSeats = false;

            foreach ($train['cars'] as $car) {
                if ($car['freeSeats'] > 0) {
                    $hasAvailableSeats = true;
                    $cars[] = [
                        'type' => $car['type'],
                        'free_seats' => $car['freeSeats'],
                        'tariff' => $car['tariffs'][0]['tariff'] ?? 0,
                    ];
                }
            }

            if ($hasAvailableSeats) {
                $available[] = [
                    'date' => $date,
                    'train_number' => $train['number'],
                    'departure_date' => $train['departureDate'],
                    'arrival_date' => $train['arrivalDate'],
                    'time_on_way' => $train['timeOnWay'],
                    'brand' => $train['brand'],
                    'from_station' => $train['subRoute']['depStationName'],
                    'to_station' => $train['subRoute']['arvStationName'],
                    'cars' => $cars,
                ];
            }
        }

        return $available;
    }

    protected function sendTelegramNotification(string $chatId, array $tickets): void
    {
        $bot = app(Nutgram::class);
        $message = $this->formatMessage($tickets);

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

        $message = "❌ <b>Chipta topilmadi</b>\n\n";
        $message .= "📅 <b>Sana:</b> {$formattedDate}\n";
        $message .= "📍 <b>Yo'nalish:</b> TOSHKENT → QUMQURGON\n\n";
        $message .= "Hozircha bo'sh joylar mavjud emas. Biz sizga doimiy ravishda tekshirib turamiz va bo'sh joylar paydo bo'lganda darhol xabar beramiz! ✅";

        $bot->sendMessage(
            text: $message,
            chat_id: $chatId,
            parse_mode: 'HTML'
        );
    }

    protected function formatMessage(array $trains): string
    {
        $firstTrain = $trains[0];

        $message = "🎫 <b>Bo'sh joylar topildi!</b>\n\n";
        $message .= "📍 <b>Yo'nalish:</b> {$firstTrain['from_station']} → {$firstTrain['to_station']}\n";
        $message .= '📅 <b>Sana:</b> '.date('d.m.Y', strtotime($firstTrain['date']))."\n\n";

        foreach ($trains as $train) {
            $message .= "━━━━━━━━━━━━━━━━━━\n";
            $message .= "🚂 <b>Poyezd #{$train['train_number']}</b>\n";
            $message .= "⏰ <b>Jo'nash:</b> {$train['departure_date']}\n";

            foreach ($train['cars'] as $car) {
                $carType = str_pad($car['type'].':', 11, ' ', STR_PAD_RIGHT);
                $freeSeats = str_pad($car['free_seats'].'ta', 4, ' ', STR_PAD_LEFT);
                $tariff = number_format($car['tariff'], 0, '.', ' ').' so\'m';

                $message .= "🎟 <code>{$carType}|{$freeSeats}| {$tariff}</code>\n";
            }
        }

        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= '🔗 <a href="https://eticket.railway.uz">Chiptalarni sotib olish</a>';

        return $message;
    }
}
