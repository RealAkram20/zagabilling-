@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="bg-white border border-[#E9EBEF] rounded-2xl shadow-sm p-8">
    <h1 class="text-xl font-bold tracking-tight">Sign in</h1>
    <p class="text-[13px] text-[#787E88] mt-1 mb-6">Access the billing portal.</p>

    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
        </div>
        <div>
            <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Password</label>
            <x-password-input name="password" required autocomplete="current-password" />
        </div>
        <label class="flex items-center gap-2 text-[12.5px] text-[#787E88]">
            <input type="checkbox" name="remember" class="rounded border-[#D8DBE0]"> Remember me
        </label>
        <button type="submit"
                class="w-full h-10 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-md shadow-brand/30 hover:opacity-95">
            Sign in
        </button>
    </form>
</div>
<p class="text-center text-[12px] text-[#9AA0AA] mt-5">Seeded admin · admin@zaga.local / password</p>
@endsection
