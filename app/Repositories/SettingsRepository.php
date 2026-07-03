<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingsRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
