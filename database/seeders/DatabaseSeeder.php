<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Device;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\UnlockCode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@zaga.local'],
            ['name' => 'Amara Reyes', 'role' => User::ROLE_SUPER_ADMIN, 'password' => Hash::make('password')],
        );
        User::updateOrCreate(
            ['email' => 'jonas@zaga.local'],
            ['name' => 'Jonas Berg', 'role' => User::ROLE_OPERATOR, 'password' => Hash::make('password')],
        );
        User::updateOrCreate(
            ['email' => 'mei@zaga.local'],
            ['name' => 'Mei Kwan', 'role' => User::ROLE_SUPPORT, 'password' => Hash::make('password')],
        );

        foreach ([
            'security.require_2fa' => '0',
            'security.vault_reauth' => '1',
            'security.auto_lock' => '0',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $plans = collect([
            ['name' => '12-mo Flex', 'term_months' => 12, 'deposit_percentage' => 15.00, 'cadence' => 'biweekly', 'grace_days' => 3],
            ['name' => '18-mo Standard', 'term_months' => 18, 'deposit_percentage' => 20.00, 'cadence' => 'monthly', 'grace_days' => 3],
            ['name' => '24-mo Standard', 'term_months' => 24, 'deposit_percentage' => 20.00, 'cadence' => 'monthly', 'grace_days' => 5],
            ['name' => '36-mo Extended', 'term_months' => 36, 'deposit_percentage' => 10.00, 'cadence' => 'monthly', 'grace_days' => 7],
        ])->map(fn ($attributes) => Plan::create($attributes));

        $people = [
            'Daniel Osei', 'Priya Menon', 'Marcus Feld', 'Lucia Romano', 'Kenji Tan',
            'Aisha Bello', 'Sofia Vega', 'Tomas Nowak', 'Elena Petrova', 'Noah Adeyemi',
        ];

        $models = [
            'MacBook Pro 14" M3', 'Dell XPS 13', 'ThinkPad X1 Carbon', 'HP Spectre x360', 'MacBook Air M2',
            'Surface Laptop 5', 'Asus ZenBook 14', 'Acer Swift 3', 'MacBook Pro 16" M3', 'Lenovo Yoga 9i',
        ];

        $prices = [1899, 1299, 1499, 1399, 1199, 1099, 999, 849, 2499, 1149];

        $methods = ['Visa ···4021', 'Mastercard ···8890', 'Bank transfer', 'Apple Pay', 'Visa ···3310', 'Visa ···1180'];

        $statuses = [
            Device::STATUS_ACTIVE, Device::STATUS_ACTIVE, Device::STATUS_LOCKED,
            Device::STATUS_GRACE, Device::STATUS_ACTIVE, Device::STATUS_OVERDUE,
            Device::STATUS_ACTIVE, Device::STATUS_CLOSED, Device::STATUS_ACTIVE, Device::STATUS_ACTIVE,
        ];

        foreach ($people as $index => $name) {
            $client = Client::create([
                'name' => $name,
                'email' => Str::slug($name, '.') . '@example.com',
                'phone' => '+2547' . random_int(10000000, 99999999),
            ]);

            $plan = $plans->random();
            $status = $statuses[$index];
            $price = $prices[$index];
            $deposit = round($price * (float) $plan->deposit_percentage / 100, 2);
            $financed = round($price - $deposit, 2);
            $perInstallment = round($financed / (int) $plan->term_months, 2);

            $paidCount = random_int(2, max(2, (int) $plan->term_months - 3));
            $balance = round(max($financed - $paidCount * $perInstallment, 0), 2);

            $device = Device::create([
                'account_number' => 'ZG-' . (40000 + $index),
                'serial' => strtoupper(Str::random(12)),
                'name' => 'ZAGA-' . strtoupper(Str::random(5)),
                'model' => $models[$index],
                'price' => $price,
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'balance' => $balance,
                'next_due_at' => now()->addDays(random_int(-10, 25)),
                'bios_password' => strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4)),
                'recovery_key' => implode('-', [Str::upper(Str::random(6)), Str::upper(Str::random(6)), Str::upper(Str::random(6)), Str::upper(Str::random(6))]),
                'hmac_secret' => bin2hex(random_bytes(32)),
                'uninstall_code' => strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4)),
                'activated_at' => now()->subMonths(random_int(1, 10)),
            ]);

            for ($i = 0; $i < $paidCount; $i++) {
                Payment::create([
                    'device_id' => $device->id,
                    'client_id' => $client->id,
                    'amount' => $perInstallment,
                    'status' => Payment::STATUS_PAID,
                    'method' => 'pesapal',
                    'method_label' => $methods[array_rand($methods)],
                    'pesapal_tracking_id' => (string) Str::uuid(),
                    'merchant_reference' => 'ZP-' . strtoupper(Str::random(10)),
                    'paid_at' => now()->subDays($paidCount - $i)->subDays(random_int(0, 3)),
                ]);
            }

            if (in_array($status, [Device::STATUS_GRACE, Device::STATUS_OVERDUE, Device::STATUS_LOCKED], true)) {
                Payment::create([
                    'device_id' => $device->id,
                    'client_id' => $client->id,
                    'amount' => $perInstallment,
                    'status' => Payment::STATUS_PENDING,
                    'method' => 'pesapal',
                    'method_label' => $methods[array_rand($methods)],
                    'merchant_reference' => 'ZP-' . strtoupper(Str::random(10)),
                ]);
            }

            if ($status === Device::STATUS_ACTIVE) {
                UnlockCode::create([
                    'device_id' => $device->id,
                    'issued_by' => 1,
                    'code' => strtoupper(Str::random(4) . '-' . Str::random(4)),
                    'type' => UnlockCode::TYPE_FULL,
                    'expires_at' => now()->addDay(),
                ]);
            }
        }

        foreach ([['Dell Latitude 5440', 1099], ['HP EliteBook 840', 1249]] as $i => [$model, $price]) {
            Device::create([
                'account_number' => 'ZG-' . (41000 + $i),
                'serial' => strtoupper(Str::random(12)),
                'name' => 'ZAGA-' . strtoupper(Str::random(5)),
                'model' => $model,
                'price' => $price,
                'status' => Device::STATUS_UNASSIGNED,
                'balance' => 0,
                'bios_password' => strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4)),
                'recovery_key' => implode('-', [Str::upper(Str::random(6)), Str::upper(Str::random(6)), Str::upper(Str::random(6)), Str::upper(Str::random(6))]),
                'hmac_secret' => bin2hex(random_bytes(32)),
                'uninstall_code' => strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4)),
            ]);
        }

        $admin = User::where('email', 'admin@zaga.local')->first();
        $lockedDevice = Device::where('status', Device::STATUS_LOCKED)->first();
        $activeDevice = Device::where('status', Device::STATUS_ACTIVE)->first();

        $samples = [
            ['device.locked', 'Device locked', ($lockedDevice->account_number ?? 'A device') . ' was locked after its grace period expired.', $lockedDevice, 20, false],
            ['payment.received', 'Payment received', 'An installment payment cleared for ' . ($activeDevice->account_number ?? 'a device') . '.', $activeDevice, 90, false],
            ['unlock.issued', 'Unlock code issued', 'A full unlock code was issued to ' . ($activeDevice->client->name ?? 'a client') . '.', $activeDevice, 180, false],
            ['device.enrolled', 'Device enrolled', 'A device was enrolled on the 24-mo Standard plan.', $activeDevice, 400, true],
            ['device.registered', 'Device registered', 'A new device was added to inventory from the offline client.', null, 1500, true],
        ];

        foreach ($samples as [$type, $title, $body, $device, $minsAgo, $isRead]) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $device ? route('admin.devices.show', $device) : null,
                'read_at' => $isRead ? now()->subMinutes($minsAgo - 5) : null,
                'created_at' => now()->subMinutes($minsAgo),
                'updated_at' => now()->subMinutes($minsAgo),
            ]);
        }
    }
}
