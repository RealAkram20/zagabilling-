<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Client\UnlockController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/two-factor', [LoginController::class, 'showTwoFactor'])->name('two-factor.show');
    Route::post('/two-factor', [LoginController::class, 'verifyTwoFactor'])->name('two-factor.verify');
    Route::post('/two-factor/resend', [LoginController::class, 'resendTwoFactor'])->name('two-factor.resend');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('/devices/search', [DeviceController::class, 'search'])->name('devices.search');
    Route::get('/devices/create', [DeviceController::class, 'create'])->middleware('can:manage-devices')->name('devices.create');
    Route::get('/devices/bulk', [DeviceController::class, 'bulkCreate'])->middleware('can:manage-devices')->name('devices.bulk');
    Route::post('/devices/bulk', [DeviceController::class, 'bulkStore'])->middleware('can:manage-devices')->name('devices.bulk.store');
    Route::post('/devices', [DeviceController::class, 'store'])->middleware('can:manage-devices')->name('devices.store');
    Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
    Route::get('/devices/{device}/edit', [DeviceController::class, 'edit'])->middleware('can:manage-devices')->name('devices.edit');
    Route::patch('/devices/{device}', [DeviceController::class, 'update'])->middleware('can:manage-devices')->name('devices.update');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->middleware('can:manage-devices')->name('devices.destroy');
    Route::post('/devices/{device}/enroll', [DeviceController::class, 'enroll'])->middleware('can:manage-devices')->name('devices.enroll');
    Route::post('/devices/{device}/unlock', [DeviceController::class, 'unlock'])->middleware('can:manage-devices')->name('devices.unlock');
    Route::post('/devices/{device}/collect', [DeviceController::class, 'collect'])->middleware('can:issue-codes')->name('devices.collect');
    Route::get('/devices/{device}/payment-status', [DeviceController::class, 'paymentStatus'])->middleware('can:issue-codes')->name('devices.paymentStatus');
    Route::post('/devices/{device}/vault', [DeviceController::class, 'revealVault'])->middleware('can:reveal-vault')->name('devices.vault');
    Route::post('/devices/{device}/provisioning', [DeviceController::class, 'revealProvisioning'])->middleware('can:reveal-provisioning')->name('devices.provisioning');
    Route::post('/devices/{device}/uninstall-auth', [DeviceController::class, 'uninstallAuthorization'])->middleware('can:manage-devices')->name('devices.uninstallAuth');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('can:manage-clients')->name('clients.store');
    Route::get('/clients/check-email', [ClientController::class, 'checkEmail'])->name('clients.checkEmail');
    Route::delete('/clients/bulk', [ClientController::class, 'bulkDestroy'])->middleware('can:manage-clients')->name('clients.bulkDestroy');
    Route::get('/clients/{client}/panel', [ClientController::class, 'panel'])->name('clients.panel');
    Route::post('/clients/{client}/enroll', [ClientController::class, 'enroll'])->middleware('can:manage-clients')->name('clients.enroll');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->middleware('can:manage-clients')->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('can:manage-clients')->name('clients.destroy');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [PlanController::class, 'store'])->middleware('can:manage-plans')->name('plans.store');
    Route::patch('/plans/{plan}', [PlanController::class, 'update'])->middleware('can:manage-plans')->name('plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->middleware('can:manage-plans')->name('plans.destroy');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/{notification}/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/account', [AccountController::class, 'edit'])->name('account');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::patch('/account/two-factor', [AccountController::class, 'updateTwoFactor'])->name('account.twoFactor');

    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::patch('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
        Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
        Route::patch('/settings/mail', [SettingsController::class, 'updateMail'])->name('settings.mail');
        Route::post('/settings/mail/test', [SettingsController::class, 'sendTestMail'])->name('settings.mail.test');
        Route::patch('/settings/gateway', [SettingsController::class, 'updateGateway'])->name('settings.gateway');
        Route::post('/settings/gateway/register-ipn', [SettingsController::class, 'registerIpn'])->name('settings.gateway.ipn');
        Route::post('/settings/gateway/test', [SettingsController::class, 'testGateway'])->name('settings.gateway.test');

        Route::middleware('can:manage-users')->group(function () {
            Route::post('/settings/users', [SettingsController::class, 'inviteUser'])->name('settings.users');
            Route::patch('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
            Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
        });
    });
});

Route::prefix('unlock')->name('portal.')->group(function () {
    Route::get('/', [UnlockController::class, 'lookup'])->name('lookup');
    Route::post('/', [UnlockController::class, 'find'])->name('find');
    Route::get('/callback', [UnlockController::class, 'callback'])->name('callback');
    Route::match(['get', 'post'], '/ipn', [UnlockController::class, 'ipn'])->name('ipn');
    Route::get('/{device}/summary', [UnlockController::class, 'summary'])->name('summary');
    Route::get('/{device}/payment', [UnlockController::class, 'payment'])->name('payment');
    Route::post('/{device}/pay', [UnlockController::class, 'pay'])->name('pay');
    Route::get('/{device}/code', [UnlockController::class, 'code'])->name('code');
});
