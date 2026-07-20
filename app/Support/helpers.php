<?php

use App\Services\SettingsService;

if (! function_exists('currency_prefix')) {
    function currency_prefix(): string
    {
        $prefix = config('zaga.currency_prefix');

        if (! $prefix) {
            $prefix = SettingsService::currencyPrefixFor(config('services.pesapal.currency', 'KES'));
        }

        return $prefix;
    }
}

if (! function_exists('money')) {
    function money($amount, ?int $decimals = null): string
    {
        $amount = (float) $amount;

        if ($decimals === null) {
            $decimals = fmod($amount, 1) == 0.0 ? 0 : 2;
        }

        return currency_prefix() . number_format($amount, $decimals);
    }
}
