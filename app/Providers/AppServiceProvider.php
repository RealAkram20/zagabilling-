<?php

namespace App\Providers;

use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (! $this->app->runningInConsole() && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        Paginator::defaultView('pagination.zaga');

        $this->applyDynamicConfig();
        $this->composeBranding();

        View::composer('layouts.admin', function ($view) {
            $service = app(NotificationService::class);
            $user = auth()->user();
            $view->with('notifications', $user ? $service->recent($user) : collect());
            $view->with('notificationCount', $user ? $service->unreadCount($user) : 0);
            $view->with('gatewayEnv', app(SettingsService::class)->gateway()['env']);
        });
    }

    private function applyDynamicConfig(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $settings = app(SettingsService::class);
        $branding = $settings->branding();

        if (! empty($branding['app_name'])) {
            config(['app.name' => $branding['app_name']]);
        }

        $currency = $settings->currency();
        config([
            'zaga.currency' => $currency,
            'zaga.currency_prefix' => SettingsService::currencyPrefixFor($currency),
        ]);

        $mail = $settings->mail();

        if (! empty($mail['host'])) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $mail['host'],
                'mail.mailers.smtp.port' => $mail['port'] ?: 587,
                'mail.mailers.smtp.encryption' => ($mail['encryption'] && $mail['encryption'] !== 'none') ? $mail['encryption'] : null,
                'mail.mailers.smtp.username' => $mail['username'],
                'mail.mailers.smtp.password' => $settings->mailPassword(),
            ]);

            if (! empty($mail['from_address'])) {
                config(['mail.from.address' => $mail['from_address']]);
            }

            if (! empty($mail['from_name'])) {
                config(['mail.from.name' => $mail['from_name']]);
            }
        }
    }

    private function composeBranding(): void
    {
        View::composer(['layouts.admin', 'layouts.portal', 'layouts.guest', 'client.lookup'], function ($view) {
            try {
                $settings = app(SettingsService::class);
                $branding = $settings->branding();
                $tints = $settings->brandTints($branding['primary_color']);
                $prefix = $settings->currencyPrefix();
            } catch (\Throwable $e) {
                $branding = ['app_name' => 'Zaga', 'primary_color' => '#4B45C7', 'logo_path' => null, 'icon_path' => null, 'favicon_path' => null];
                $tints = ['DEFAULT' => '#4B45C7', '50' => '#F1F0FC', '100' => '#E4E3F6'];
                $prefix = 'KSh ';
            }

            $iconPath = $branding['icon_path'] ?? null ?: ($branding['favicon_path'] ?? null);

            $view->with('appName', $branding['app_name']);
            $view->with('brandTints', $tints);
            $view->with('currencyPrefix', $prefix);
            $view->with('logoUrl', ! empty($branding['logo_path']) ? asset($branding['logo_path']) : null);
            $view->with('iconUrl', $iconPath ? asset($iconPath) : null);
            $view->with('faviconUrl', ! empty($branding['favicon_path']) ? asset($branding['favicon_path']) : asset('favicon.ico'));
        });
    }
}
