<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\SettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalClient
{
    public function __construct(private SettingsRepository $settings)
    {
    }

    public function testConnection(): array
    {
        Cache::forget('pesapal_token');

        return $this->token()
            ? ['ok' => true]
            : ['error' => 'Authentication failed. Check the environment, consumer key, and secret.'];
    }

    public function env(): string
    {
        return $this->setting('env', config('services.pesapal.env', 'sandbox')) ?: 'sandbox';
    }

    public function configured(): bool
    {
        return filled($this->key()) && filled($this->secret());
    }

    public function registerIpn(): array
    {
        if (! $this->configured()) {
            return ['error' => 'Add your consumer key and secret before registering an IPN.'];
        }

        $token = $this->token();
        if (! $token) {
            return ['error' => 'Could not authenticate with PesaPal. Check your credentials and environment.'];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->timeout(25)->post($this->url('/api/URLSetup/RegisterIPN'), [
                'url' => $this->ipnUrl(),
                'ipn_notification_type' => 'GET',
            ]);
        } catch (\Throwable $e) {
            Log::warning('PesaPal RegisterIPN failed: ' . $e->getMessage());

            return ['error' => 'Could not reach PesaPal. Please try again.'];
        }

        $id = $response->json('ipn_id');
        if ($id) {
            $this->settings->set('pesapal.ipn_id', $id);

            return ['ipn_id' => $id];
        }

        return ['error' => $response->json('error.message') ?? $response->json('message') ?? 'IPN registration failed.'];
    }

    public function submitOrder(Payment $payment, ?string $phoneOverride = null): array
    {
        $device = $payment->device;
        $client = $payment->client;

        try {
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->timeout(25)
                ->post($this->url('/api/Transactions/SubmitOrderRequest'), [
                    'id' => $payment->merchant_reference,
                    'currency' => $this->setting('currency', config('services.pesapal.currency', 'KES')),
                    'amount' => (float) $payment->amount,
                    'description' => 'Installment for ' . $device->account_number,
                    'callback_url' => route('portal.callback'),
                    'notification_id' => $this->ipnId(),
                    'billing_address' => [
                        'email_address' => $client->email,
                        'phone_number' => $phoneOverride ?: $client->phone,
                        'first_name' => $client->name,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('PesaPal SubmitOrder failed: ' . $e->getMessage());

            return ['error' => ['message' => 'Could not reach PesaPal.']];
        }

        return $response->json() ?? [];
    }

    public function transactionStatus(string $orderTrackingId): array
    {
        try {
            $response = Http::withToken($this->token())
                ->acceptJson()
                ->timeout(25)
                ->get($this->url('/api/Transactions/GetTransactionStatus'), [
                    'orderTrackingId' => $orderTrackingId,
                ]);
        } catch (\Throwable $e) {
            Log::warning('PesaPal GetTransactionStatus failed: ' . $e->getMessage());

            return [];
        }

        return $response->json() ?? [];
    }

    private function key(): ?string
    {
        return $this->setting('consumer_key', config('services.pesapal.key'));
    }

    private function secret(): ?string
    {
        return $this->setting('consumer_secret', config('services.pesapal.secret'));
    }

    private function ipnUrl(): string
    {
        return $this->setting('ipn_url', config('services.pesapal.callback_url')) ?: route('portal.ipn');
    }

    private function token(): ?string
    {
        $cached = Cache::get('pesapal_token');
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()->timeout(25)->post($this->url('/api/Auth/RequestToken'), [
                'consumer_key' => $this->key(),
                'consumer_secret' => $this->secret(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('PesaPal RequestToken failed: ' . $e->getMessage());

            return null;
        }

        $token = $response->json('token');
        if ($token) {
            Cache::put('pesapal_token', $token, now()->addMinutes(4));
        }

        return $token;
    }

    private function ipnId(): ?string
    {
        $stored = $this->settings->get('pesapal.ipn_id');
        if ($stored) {
            return $stored;
        }

        return $this->registerIpn()['ipn_id'] ?? null;
    }

    private function url(string $path): string
    {
        $base = $this->env() === 'live'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';

        return $base . $path;
    }

    private function setting(string $key, ?string $fallback = null): ?string
    {
        return $this->settings->get('pesapal.' . $key) ?? $fallback;
    }
}
