<?php

namespace App\Services;

use App\Repositories\SettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const SECURITY_DEFAULTS = [
        'require_2fa' => '1',
        'vault_reauth' => '1',
        'auto_lock' => '0',
    ];

    private const DEFAULT_COLOR = '#4B45C7';

    private const MAIL_KEYS = ['host', 'port', 'encryption', 'username', 'from_address', 'from_name'];

    public function __construct(private SettingsRepository $settings)
    {
    }

    public function security(): array
    {
        $values = [];

        foreach (self::SECURITY_DEFAULTS as $name => $default) {
            $values[$name] = (bool) (int) $this->settings->get("security.{$name}", $default);
        }

        return $values;
    }

    public function setSecurity(array $submitted): void
    {
        foreach (array_keys(self::SECURITY_DEFAULTS) as $name) {
            $this->settings->set("security.{$name}", empty($submitted[$name]) ? '0' : '1');
        }
    }

    public function gateway(): array
    {
        return [
            'env' => $this->settings->get('pesapal.env', config('services.pesapal.env', 'sandbox')),
            'consumer_key' => $this->settings->get('pesapal.consumer_key', config('services.pesapal.key')),
            'secret_set' => filled($this->settings->get('pesapal.consumer_secret', config('services.pesapal.secret'))),
            'currency' => $this->settings->get('pesapal.currency', config('services.pesapal.currency', 'KES')),
            'ipn_url' => $this->settings->get('pesapal.ipn_url', route('portal.ipn')),
            'ipn_id' => $this->settings->get('pesapal.ipn_id'),
        ];
    }

    public function setGateway(array $data): void
    {
        $currentEnv = $this->settings->get('pesapal.env', config('services.pesapal.env', 'sandbox'));

        $this->settings->set('pesapal.env', $data['env']);
        $this->settings->set('pesapal.consumer_key', $data['consumer_key'] ?? null);
        $this->settings->set('pesapal.currency', ! empty($data['currency']) ? strtoupper($data['currency']) : 'KES');
        $this->settings->set('pesapal.ipn_url', ! empty($data['ipn_url']) ? $data['ipn_url'] : route('portal.ipn'));

        if (! empty($data['consumer_secret'])) {
            $this->settings->set('pesapal.consumer_secret', $data['consumer_secret']);
        }

        if ($currentEnv !== $data['env']) {
            $this->settings->set('pesapal.ipn_id', null);
        }

        Cache::forget('pesapal_token');
    }

    public function branding(): array
    {
        return [
            'app_name' => $this->settings->get('app.name', config('app.name', 'Zaga')) ?: 'Zaga',
            'primary_color' => $this->settings->get('branding.primary_color', self::DEFAULT_COLOR) ?: self::DEFAULT_COLOR,
            'logo_path' => $this->settings->get('branding.logo_path'),
            'favicon_path' => $this->settings->get('branding.favicon_path'),
        ];
    }

    public function setBranding(array $data): void
    {
        if (array_key_exists('app_name', $data)) {
            $this->settings->set('app.name', $data['app_name'] ?: null);
        }

        if (array_key_exists('primary_color', $data)) {
            $this->settings->set('branding.primary_color', $data['primary_color'] ?: null);
        }

        if (! empty($data['logo_path'])) {
            $this->settings->set('branding.logo_path', $data['logo_path']);
        }

        if (! empty($data['favicon_path'])) {
            $this->settings->set('branding.favicon_path', $data['favicon_path']);
        }
    }

    public function brandTints(?string $hex = null): array
    {
        $hex = $hex ?: $this->branding()['primary_color'];

        return [
            'DEFAULT' => $hex,
            '50' => $this->mixWhite($hex, 0.92),
            '100' => $this->mixWhite($hex, 0.86),
        ];
    }

    public function mail(): array
    {
        $values = [];

        foreach (self::MAIL_KEYS as $key) {
            $values[$key] = $this->settings->get("mail.{$key}");
        }

        $values['password_set'] = filled($this->settings->get('mail.password'));

        return $values;
    }

    public function setMail(array $data): void
    {
        foreach (self::MAIL_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $this->settings->set("mail.{$key}", $data[$key] ?: null);
            }
        }

        if (! empty($data['password'])) {
            $this->settings->set('mail.password', Crypt::encryptString($data['password']));
        }
    }

    public function mailPassword(): ?string
    {
        $encrypted = $this->settings->get('mail.password');

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mixWhite(string $hex, float $whiteRatio): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#F1F0FC';
        }

        $channel = fn (int $value) => (int) round($value * (1 - $whiteRatio) + 255 * $whiteRatio);

        return sprintf(
            '#%02X%02X%02X',
            $channel(hexdec(substr($hex, 0, 2))),
            $channel(hexdec(substr($hex, 2, 2))),
            $channel(hexdec(substr($hex, 4, 2))),
        );
    }
}

