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
    function money($amount, int $decimals = 2): string
    {
        return currency_prefix() . number_format((float) $amount, $decimals);
    }
}
