@extends('layouts.admin')

@section('title', 'My account')

@section('content')
<x-page-header title="My account" subtitle="Manage your profile, password, and sign-in security." />

@if ($errors->any())
    <div class="max-w-3xl mb-4 px-4 py-3 rounded-xl bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
@endif

<div class="max-w-3xl space-y-4">
    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <form method="POST" action="{{ route('admin.account.profile') }}" enctype="multipart/form-data"
              class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="{ preview: '{{ $user->avatarUrl() }}' }">
            @csrf
            @method('PATCH')
            <div class="sm:col-span-2 flex items-center gap-4">
                <template x-if="preview">
                    <span class="w-[52px] h-[52px] rounded-full overflow-hidden bg-[#EFF1F4] flex-none"><img :src="preview" class="w-full h-full object-cover" alt=""></span>
                </template>
                <template x-if="!preview">
                    <span class="flex-none"><x-avatar :name="$user->name" :size="52" /></span>
                </template>
                <div>
                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC] cursor-pointer">
                        <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Change photo
                        <input type="file" name="avatar" accept="image/*" class="hidden"
                               @change="const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); }">
                    </label>
                    <div class="text-[11px] text-[#9AA0AA] mt-1.5">JPG, PNG or WEBP, up to 2&nbsp;MB.</div>
                </div>
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Full name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Email</label>
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <div class="sm:col-span-2">
                <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save profile</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[14.5px] font-semibold mb-4">Password</div>
        <form method="POST" action="{{ route('admin.account.password') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            @method('PATCH')
            <div class="sm:col-span-2">
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Current password</label>
                <x-password-input name="current_password" required autocomplete="current-password" />
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">New password</label>
                <x-password-input name="password" required autocomplete="new-password" />
            </div>
            <div>
                <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Confirm new password</label>
                <x-password-input name="password_confirmation" required autocomplete="new-password" />
            </div>
            <div class="sm:col-span-2">
                <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Update password</button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
        <div class="text-[14.5px] font-semibold mb-1">Two-factor authentication</div>
        <div class="text-[12px] text-[#8A909A] mb-4">A one-time code is emailed to <span class="font-medium text-[#565b64]">{{ $user->email }}</span> at sign-in.</div>
        @if ($twoFactorEnforced)
            <div class="flex items-center gap-2 text-[13px] text-[#0F7B54]">
                <x-icon name="shield" class="w-4 h-4" /> Enforced for all admins by organization policy.
            </div>
        @else
            <form method="POST" action="{{ route('admin.account.twoFactor') }}" class="flex items-center justify-between gap-4">
                @csrf
                @method('PATCH')
                <div>
                    <div class="text-[13px] font-medium">Require an email code at sign-in</div>
                    <div class="text-[11.5px] text-[#9AA0AA] mt-0.5">Recommended for accounts with elevated access.</div>
                </div>
                <div class="flex items-center gap-3">
                    <x-toggle name="two_factor_enabled" :checked="$user->two_factor_enabled" onchange="this.form.submit()" />
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
