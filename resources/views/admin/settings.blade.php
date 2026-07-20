@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<x-page-header title="Settings" subtitle="Branding, gateway, email, team, and security configuration." />

@if (session('status'))
    <div class="max-w-3xl mb-4 px-4 py-3 rounded-lg bg-[#E9F6EF] border border-[#BFE6D2] text-[13px] text-[#0F7B54]">{{ session('status') }}</div>
@endif

<div class="max-w-3xl space-y-4">
    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]"
         x-data="{ color: '{{ old('primary_color', $branding['primary_color']) }}', logoPreview: @js($branding['logo_path'] ? asset($branding['logo_path']) : null), iconPreview: @js(! empty($branding['icon_path']) ? asset($branding['icon_path']) : null), faviconPreview: @js($branding['favicon_path'] ? asset($branding['favicon_path']) : null) }">
        <div class="text-[14.5px] font-semibold mb-1">Branding</div>
        <div class="text-[12px] text-[#8A909A] mb-4">App name, primary colour, logo, and favicon. Applies across the admin, portal, and login screens.</div>

        @error('app_name') <div class="mb-3 px-3 py-2 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[12.5px] text-[#B23A30]">{{ $message }}</div> @enderror
        @error('primary_color') <div class="mb-3 px-3 py-2 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[12.5px] text-[#B23A30]">{{ $message }}</div> @enderror
        @error('logo') <div class="mb-3 px-3 py-2 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[12.5px] text-[#B23A30]">{{ $message }}</div> @enderror
        @error('icon') <div class="mb-3 px-3 py-2 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[12.5px] text-[#B23A30]">{{ $message }}</div> @enderror
        @error('favicon') <div class="mb-3 px-3 py-2 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[12.5px] text-[#B23A30]">{{ $message }}</div> @enderror

        <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">App name</label>
                <input name="app_name" value="{{ old('app_name', $branding['app_name']) }}" required maxlength="60"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Primary colour</label>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="color" class="w-11 h-10 rounded-lg border border-[#E4E6EB] bg-white p-1 cursor-pointer">
                    <input name="primary_color" x-model="color" pattern="^#[0-9A-Fa-f]{6}$" required
                           class="flex-1 h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum uppercase outline-none focus:border-brand">
                    <span class="w-10 h-10 rounded-lg border border-[#E4E6EB] flex-none" :style="'background:' + color"></span>
                </div>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Logo <span class="text-[#9AA0AA] font-normal">(wide, shown when expanded)</span></label>
                <div class="flex items-center gap-3">
                    <template x-if="logoPreview"><img :src="logoPreview" class="h-10 max-w-[120px] rounded-lg object-contain border border-[#EEF0F3] flex-none" alt=""></template>
                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12px] font-medium text-[#4A4F58] cursor-pointer">
                        <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Choose file
                        <input type="file" name="logo" accept="image/*" class="hidden" @change="const f=$event.target.files[0]; if(f) logoPreview=URL.createObjectURL(f)">
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Icon <span class="text-[#9AA0AA] font-normal">(square, shown when collapsed)</span></label>
                <div class="flex items-center gap-3">
                    <template x-if="iconPreview"><img :src="iconPreview" class="w-10 h-10 rounded-lg object-contain border border-[#EEF0F3] flex-none" alt=""></template>
                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12px] font-medium text-[#4A4F58] cursor-pointer">
                        <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Choose file
                        <input type="file" name="icon" accept="image/*" class="hidden" @change="const f=$event.target.files[0]; if(f) iconPreview=URL.createObjectURL(f)">
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Favicon <span class="text-[#9AA0AA] font-normal">(ICO/PNG, ≤1MB)</span></label>
                <div class="flex items-center gap-3">
                    <template x-if="faviconPreview"><img :src="faviconPreview" class="w-8 h-8 rounded object-contain border border-[#EEF0F3] flex-none" alt=""></template>
                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12px] font-medium text-[#4A4F58] cursor-pointer">
                        <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Choose file
                        <input type="file" name="favicon" accept="image/*,.ico" class="hidden" @change="const f=$event.target.files[0]; if(f) faviconPreview=URL.createObjectURL(f)">
                    </label>
                </div>
            </div>
            <div class="sm:col-span-2">
                <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save branding</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="flex items-center justify-between mb-1">
            <div class="text-[14.5px] font-semibold">Payment gateway · PesaPal</div>
            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md {{ $gateway['env'] === 'live' ? 'text-[#0F7B54] bg-[#E6F4EE]' : 'text-[#8A6410] bg-[#FBF1DD]' }}">{{ strtoupper($gateway['env']) }}</span>
        </div>
        <div class="text-[12px] text-[#8A909A] mb-4">Credentials are stored securely and never committed to the repository.</div>

        @error('ipn')
            <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $message }}</div>
        @enderror
        @error('gateway_test')
            <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.settings.gateway') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Environment</label>
                <select name="env" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                    <option value="sandbox" @selected($gateway['env'] === 'sandbox')>Sandbox</option>
                    <option value="live" @selected($gateway['env'] === 'live')>Live / Production</option>
                </select>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Currency</label>
                <input name="currency" value="{{ old('currency', $gateway['currency']) }}" maxlength="3" placeholder="KES"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] uppercase tnum outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Consumer key</label>
                <input name="consumer_key" value="{{ old('consumer_key', $gateway['consumer_key']) }}" placeholder="Your PesaPal consumer key"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Consumer secret</label>
                <x-password-input name="consumer_secret" autocomplete="new-password" placeholder="{{ $gateway['secret_set'] ? '•••••••• (leave blank to keep)' : 'Your PesaPal consumer secret' }}" />
            </div>
            <div class="sm:col-span-2">
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">IPN URL</label>
                <input name="ipn_url" value="{{ old('ipn_url', $gateway['ipn_url']) }}" placeholder="{{ route('portal.ipn') }}"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[12.5px] outline-none focus:border-brand">
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save gateway settings</button>
                <button formaction="{{ route('admin.settings.gateway.test') }}" formmethod="POST" formnovalidate
                        class="h-10 px-4 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">Test connection</button>
            </div>
        </form>

        <div class="mt-5 pt-4 border-t border-[#EEF0F3] flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-[12.5px] font-medium">Instant Payment Notification (IPN)</div>
                @if ($gateway['ipn_id'])
                    <div class="text-[11.5px] text-[#0F7B54] mt-0.5 flex items-center gap-1.5"><x-icon name="check" class="w-3.5 h-3.5" /> Registered · <span class="tnum">{{ $gateway['ipn_id'] }}</span></div>
                @else
                    <div class="text-[11.5px] text-[#8A6410] mt-0.5">Not registered yet.</div>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.settings.gateway.ipn') }}">
                @csrf
                <button class="flex items-center gap-2 h-9 px-3.5 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="external" class="w-4 h-4" sw="2" /> {{ $gateway['ipn_id'] ? 'Re-register IPN' : 'Register IPN' }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[14.5px] font-semibold mb-1">Email · SMTP</div>
        <div class="text-[12px] text-[#8A909A] mb-4">Outgoing mail for sign-in codes and notifications. Leave the host blank to keep the default (log) driver. The password is encrypted at rest.</div>

        @error('mail_test')
            <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.settings.mail') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            @method('PATCH')
            <div class="sm:col-span-2">
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">SMTP host</label>
                <input name="host" value="{{ old('host', $mail['host']) }}" placeholder="smtp.mailgun.org"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Port</label>
                <input name="port" type="number" value="{{ old('port', $mail['port']) }}" placeholder="587"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Encryption</label>
                <select name="encryption" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                    @foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('encryption', $mail['encryption'] ?: 'tls') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Username</label>
                <input name="username" value="{{ old('username', $mail['username']) }}" autocomplete="off"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Password</label>
                <x-password-input name="password" autocomplete="new-password" placeholder="{{ $mail['password_set'] ? '•••••••• (leave blank to keep)' : 'SMTP password' }}" />
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">From address</label>
                <input name="from_address" type="email" value="{{ old('from_address', $mail['from_address']) }}" placeholder="billing@yourdomain.com"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">From name</label>
                <input name="from_name" value="{{ old('from_name', $mail['from_name']) }}" placeholder="{{ $branding['app_name'] }}"
                       class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save email settings</button>
                <button formaction="{{ route('admin.settings.mail.test') }}" formmethod="POST" formnovalidate
                        class="h-10 px-4 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">Send test email</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]" x-data="{ inviting: false }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="text-[14.5px] font-semibold">Admin users</div>
                <div class="text-[12px] text-[#8A909A]">People with portal access.</div>
            </div>
            <button @click="inviting=!inviting" class="flex items-center gap-2 h-9 px-3.5 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                <x-icon name="user-plus" class="w-4 h-4" sw="1.9" /> Invite
            </button>
        </div>

        <form x-show="inviting" x-cloak method="POST" action="{{ route('admin.settings.users') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2 mb-4 p-3 rounded-xl bg-[#FBFBFC] border border-[#EEF0F3]">
            @csrf
            <input name="name" placeholder="Full name" required class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            <input name="email" type="email" placeholder="Email" required class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            <select name="role" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] outline-none">
                <option value="operator">Operator</option>
                <option value="support">Support</option>
                <option value="super_admin">Super Admin</option>
            </select>
            <button class="h-9 rounded-lg bg-brand text-white text-[12.5px] font-semibold">Send invite</button>
        </form>

        <div class="space-y-1">
            @foreach ($users as $user)
                <div x-data="{ editing: false }" class="py-2.5 border-b border-[#F4F5F7] last:border-0">
                    <div x-show="!editing" class="flex items-center gap-3">
                        <x-avatar :name="$user->name" :image="$user->avatarUrl()" :size="34" :variant="$user->isSuperAdmin() ? 'brand' : 'purple'" />
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-medium truncate">{{ $user->name }} @if ($user->id === auth()->id())<span class="text-[11px] text-[#9AA0AA] font-normal">(you)</span>@endif</div>
                            <div class="text-[11.5px] text-[#9AA0AA] truncate">{{ $user->email }}</div>
                        </div>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md {{ $user->isSuperAdmin() ? 'text-brand bg-brand-50' : 'text-[#6B7280] bg-[#EFF1F4]' }}">{{ $user->roleLabel() }}</span>
                        <button @click="editing=true" title="Edit" class="w-8 h-8 rounded-lg border border-[#E4E6EB] flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC]"><x-icon name="pencil" class="w-3.5 h-3.5" /></button>
                        @if ($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.settings.users.destroy', $user) }}" data-confirm="Remove {{ $user->name }}?">
                                @csrf
                                @method('DELETE')
                                <button title="Delete" class="w-8 h-8 rounded-lg border border-[#E7C9C4] flex items-center justify-center text-[#B23A30] hover:bg-[#FDF6F5]"><x-icon name="trash" class="w-3.5 h-3.5" /></button>
                            </form>
                        @endif
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.settings.users.update', $user) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-center">
                        @csrf
                        @method('PATCH')
                        <input name="name" value="{{ $user->name }}" required class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        <input name="email" type="email" value="{{ $user->email }}" required class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        <select name="role" class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] outline-none">
                            <option value="operator" @selected($user->role === 'operator')>Operator</option>
                            <option value="support" @selected($user->role === 'support')>Support</option>
                            <option value="super_admin" @selected($user->role === 'super_admin')>Super Admin</option>
                        </select>
                        <div class="flex gap-2">
                            <button class="h-9 px-3 rounded-lg bg-brand text-white text-[12.5px] font-semibold">Save</button>
                            <button type="button" @click="editing=false" class="h-9 px-3 rounded-lg border border-[#E4E6EB] text-[12.5px] text-[#4A4F58]">Cancel</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.security') }}" class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        @csrf
        @method('PATCH')
        <div class="flex items-center justify-between mb-4">
            <div class="text-[14.5px] font-semibold">Security</div>
            <button class="h-9 px-3.5 rounded-lg bg-brand text-white text-[12.5px] font-semibold">Save</button>
        </div>
        <div class="space-y-4">
            @php
                $toggles = [
                    ['require_2fa', 'Require 2FA for all admins', 'Enforce hardware or app-based MFA.'],
                    ['vault_reauth', 'Vault reveal re-authentication', 'Prompt for password before showing BIOS / BitLocker keys.'],
                    ['auto_lock', 'Auto-lock on overdue', 'Devices lock automatically once grace expires.'],
                ];
            @endphp
            @foreach ($toggles as [$key, $label, $desc])
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-[13px] font-medium">{{ $label }}</div>
                        <div class="text-[11.5px] text-[#9AA0AA] mt-0.5">{{ $desc }}</div>
                    </div>
                    <x-toggle :name="$key" :checked="$security[$key]" />
                </div>
            @endforeach
        </div>
    </form>
</div>
@endsection
