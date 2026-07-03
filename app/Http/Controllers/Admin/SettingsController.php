<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    public function __construct(private SettingsService $settings)
    {
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

        return back()->with('status', 'Gateway settings saved.');
    }

    public function registerIpn(PesapalClient $pesapal): RedirectResponse
    {
        $result = $pesapal->registerIpn();

        if (! empty($result['ipn_id'])) {
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
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,svg,gif', 'max:1024'],
        ]);

        $branding = ['app_name' => $data['app_name'], 'primary_color' => $data['primary_color']];

        if ($request->hasFile('logo')) {
            $branding['logo_path'] = $this->storeUpload($request->file('logo'), 'logo');
        }

        if ($request->hasFile('favicon')) {
            $branding['favicon_path'] = $this->storeUpload($request->file('favicon'), 'favicon');
        }

        $this->settings->setBranding($branding);

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

        $this->settings->setMail($data);

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
        $this->settings->setSecurity($request->only('require_2fa', 'vault_reauth', 'auto_lock'));

        return back()->with('status', 'Security settings updated.');
    }

    public function inviteUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:super_admin,operator,support'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make(Str::random(16)),
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

        $user->update($data);

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

        $user->delete();

        return back()->with('status', 'User removed.');
    }

    private function lastSuperAdmin(): bool
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1;
    }
}
