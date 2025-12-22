<?php

namespace App\Services;

readonly class TelegramWebAppValidator
{
    public function __construct(private string $botToken) {}

    public function validate(string $initData): bool
    {
        parse_str($initData, $data);

        if (! isset($data['hash'])) {
            return false;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        $dataCheckString = $this->buildDataCheckString($data);

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculatedHash, $hash)) {
            return false;
        }

        if (isset($data['auth_date'])) {
            $authDate = (int) $data['auth_date'];
            $currentTime = time();

            if (($currentTime - $authDate) > 86400) {
                return false;
            }
        }

        return true;
    }

    public function parseInitData(string $initData): ?array
    {
        if (! $this->validate($initData)) {
            return null;
        }

        parse_str($initData, $data);

        if (isset($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }

    private function buildDataCheckString(array $data): string
    {
        ksort($data);

        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = $key.'='.$value;
        }

        return implode("\n", $parts);
    }
}
