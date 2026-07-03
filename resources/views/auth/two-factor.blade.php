@extends('layouts.guest')

@section('title', 'Verify sign-in')

@section('content')
<div class="bg-white border border-[#E9EBEF] rounded-2xl shadow-sm p-8">
    <h1 class="text-xl font-bold tracking-tight">Check your email</h1>
    <p class="text-[13px] text-[#787E88] mt-1 mb-6">We sent a 6-digit code to your email address. Enter it below to finish signing in.</p>

    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
    @endif
    @if (session('status'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#E6F4EE] border border-[#BFE3D2] text-[13px] text-[#0F7B54]">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Verification code</label>
        <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus placeholder="000000"
               class="w-full h-12 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[20px] font-semibold tnum tracking-[0.4em] text-center outline-none focus:border-brand mb-4">
        <button type="submit" class="w-full h-10 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-md shadow-brand/30 hover:opacity-95">Verify and sign in</button>
    </form>
</div>
<div class="flex items-center justify-center gap-4 mt-5 text-[12px]">
    <form method="POST" action="{{ route('two-factor.resend') }}">
        @csrf
        <button class="text-brand font-medium">Resend code</button>
    </form>
    <span class="text-[#D8DBE0]">·</span>
    <a href="{{ route('login') }}" class="text-[#9AA0AA] font-medium">Back to sign in</a>
</div>
@endsection
