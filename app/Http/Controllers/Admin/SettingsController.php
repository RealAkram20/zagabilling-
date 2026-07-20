<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PesapalClient;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings,
        private AuditLogger $audit,
    ) {
    }

    public function index(): View
    {
        return view('admin.settings', [
            'security' => $this->settings->security(),
            'gateway' => $this->settings->gateway(),
            'branding' => $this->settings->branding(),
            'mail' => $this->settings->mail(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function updateGateway(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'env' => ['required', 'in:sandbox,live'],
            'consumer_key' => ['nullable', 'string', 'max:255'],
            'consumer_secret' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'ipn_url' => ['nullable', 'url', 'max:255'],
        ]);

        $this->settings->setGateway($data);

        // Never log the secret value — only that it changed, and by whom.
        $this->audit->record('settings.gateway', 'Updated payment gateway settings', null, [
            'env' => $data['env'],
            'ipn_url' => $data['ipn_url'] ?? null,
            'secret_changed' => filled($data['consumer_secret'] ?? null),
        ]);

        return back()->with('status', 'Gateway settings saved.');
    }

    public function registerIpn(PesapalClient $pesapal): RedirectResponse
    {
        $result = $pesapal->registerIpn();

        if (! empty($result['ipn_id'])) {
            $this->audit->record('settings.ipn_register', 'Registered a PesaPal IPN endpoint', null, [
                'ipn_id' => $result['ipn_id'],
            ]);

            return back()->with('status', 'IPN registered with PesaPal (' . $result['ipn_id'] . ').');
        }

        return back()->withErrors(['ipn' => $result['error'] ?? 'IPN registration failed.']);
    }

    public function testGateway(PesapalClient $pesapal): RedirectResponse
    {
        if (! $pesapal->configured()) {
            return back()->withErrors(['gateway_test' => 'Add and save your consumer key and secret first.']);
        }

        $result = $pesapal->testConnection();

        if (! empty($result['ok'])) {
            return back()->with('status', 'PesaPal connection succeeded — access token acquired.');
        }

        return back()->withErrors(['gateway_test' => $result['error'] ?? 'Could not authenticate with PesaPal.']);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            // SVG is intentionally excluded — it can carry executable script and
            // is served from the public web root.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,gif', 'max:1024'],
        ]);

        $branding = ['app_name' => $data['app_name'], 'primary_color' => $data['primary_color']];

        if ($request->hasFile('logo')) {
            $branding['logo_path'] = $this->storeUpload($request->file('logo'), 'logo');
        }

        if ($request->hasFile('icon')) {
            $branding['icon_path'] = $this->storeUpload($request->file('icon'), 'icon');
        }

        if ($request->hasFile('favicon')) {
            $branding['favicon_path'] = $this->storeUpload($request->file('favicon'), 'favicon');
        }

        $this->settings->setBranding($branding);

        $this->audit->record('settings.branding', 'Updated branding settings', null, [
            'app_name' => $data['app_name'],
            'assets_changed' => array_values(array_intersect(['logo', 'icon', 'favicon'], array_keys($request->allFiles()))),
        ]);

        return back()->with('status', 'Branding updated.');
    }

    public function updateMail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $allowed = config('mail.allowed_hosts', []);
        if (! empty($allowed) && ! empty($data['host'])
            && ! in_array(strtolower($data['host']), array_map('strtolower', $allowed), true)) {
            return back()->withInput()->withErrors([
                'host' => 'That SMTP host is not on the approved list. Contact your administrator.',
            ]);
        }

        $this->settings->setMail($data);

        // Rerouting outbound mail is security-sensitive — record host + actor,
        // never the SMTP password.
        $this->audit->record('settings.mail', 'Updated outbound email (SMTP) settings', null, [
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'password_changed' => filled($data['password'] ?? null),
        ]);

        return back()->with('status', 'Email settings saved.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            Mail::raw('This is a test email from ' . config('app.name') . '. Your SMTP settings are working.', function ($message) use ($user) {
                $message->to($user->email)->subject(config('app.name') . ' — SMTP test');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['mail_test' => 'Send failed: ' . $e->getMessage()]);
        }

        return back()->with('status', 'Test email sent to ' . $user->email . '.');
    }

    private function storeUpload(UploadedFile $file, string $prefix): string
    {
        $directory = public_path('uploads/branding');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $prefix . '_' . now()->timestamp . '_' . Str::random(6) . '.' . $file->extension();
        $file->move($directory, $filename);

        return 'uploads/branding/' . $filename;
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $security = $request->only('require_2fa', 'vault_reauth', 'auto_lock');
        $this->settings->setSecurity($security);

        $this->audit->record('settings.security', 'Updated security settings', null, $security);

        return back()->with('status', 'Security settings updated.');
    }

    public function inviteUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:super_admin,operator,support'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make(Str::random(16)),
        ]);

        $this->audit->record('user.invite', "Invited {$user->email} as {$user->role}", $user, [
            'role' => $user->role,
        ]);

        return back()->with('status', 'Team member invited.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:super_admin,operator,support'],
        ]);

        if ($user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN && $this->lastSuperAdmin()) {
            return back()->withErrors(['role' => 'You cannot change the role of the last super admin.']);
        }

        $roleChanged = $user->role !== $data['role'];
        $user->update($data);

        $this->audit->record('user.update', "Updated user {$user->email}", $user, [
            'role' => $user->role,
            'role_changed' => $roleChanged,
        ]);

        return back()->with('status', 'User updated.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isSuperAdmin() && $this->lastSuperAdmin()) {
            return back()->withErrors(['user' => 'You cannot delete the last super admin.']);
        }

        $email = $user->email;
        $this->audit->record('user.delete', "Removed user {$email}", $user, [
            'role' => $user->role,
        ]);

        $user->delete();

        return back()->with('status', 'User removed.');
    }

    private function lastSuperAdmin(): bool
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1;
    }
}
