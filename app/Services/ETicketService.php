<?php

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;

class ETicketService
{
    private string $baseUrl = 'https://eticket.railway.uz/api';

    public function searchStations(string $query = ''): array
    {
        $stations = $this->send('v1/handbook/stations/list', [
            'name' => $query,
        ]) ?? [];
        $stations = $stations['stations'] ?? [];
        $d = [];
        foreach ($stations as $station) {
            $d[] = [
                'value' => $station['code'],
                'label' => $station['name'],
            ];
        }

        return $d;
    }

    /**
     * @throws ConnectionException
     */
    private function send($action, $data, $method = 'post'): ?array
    {
        // Chromium orqali barcha cookielarni olamiz
        $cookies = $this->getXsrftoken();

        // Cookie ichidan XSRF-TOKEN qiymatini ajratib olamiz
        $xsrfTokenValue = $cookies['XSRF-TOKEN'] ?? null;

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'uz',
                'Content-Type' => 'application/json',
                'X-XSRF-TOKEN' => $xsrfTokenValue, // JUDA MUHIM: Cookiedagi bilan bir xil bo'lsin
                'Origin' => 'https://eticket.railway.uz',
                'Referer' => 'https://eticket.railway.uz/uz/home',
                'device-type' => 'BROWSER',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            ])
            ->withCookies($cookies, 'eticket.railway.uz')
            ->{$method}($action, $data);

        if ($response->failed()) {
            // Agar xato bo'lsa, sababini ko'rish uchun:
            dd([
                'status' => $response->status(),
                'body' => $response->json(),
                'sent_cookies' => $cookies,
                'sent_token_header' => $xsrfTokenValue,
            ]);
        }

        return $response->json('data', []);
    }

    private function getXsrfToken(): ?array
    {
        return Cache::remember('railway_cookies', 3600, function () {
            $process = (new ChromeProcess)->toProcess();
            $process->start();

            $options = (new ChromeOptions)->addArguments(collect([
                '--headless=new',  // yangi headless rejim
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
                '--disable-extensions',
                '--disable-setuid-sandbox',  // qo'shimcha
            ])->all());

            $options->setBinary('/usr/bin/google-chrome-stable');

            // Set the binary path for the Chrome browser

            $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);

            // 2. Drayverga ulanamiz
            $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

            // 3. Browser obyektini yaratamiz (Endi visit() ishlaydi)
            $browser = new Browser($driver);

            try {
                $browser->visit('https://eticket.railway.uz/uz/home');

                // Cookie-larni olish
                $cookies = $driver->manage()->getCookies();
                $formattedCookies = [];

                foreach ($cookies as $cookie) {
                    $formattedCookies[$cookie['name']] = $cookie['value'];
                }
                $browser->quit();
                $process->stop();

                return $formattedCookies;
            } finally {
                $browser->quit();
                $process->stop();
            }
        });
    }
}
