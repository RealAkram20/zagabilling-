@props(['name', 'autocomplete' => 'current-password', 'placeholder' => ''])

<div x-data="{ show: false }" class="relative">
    <input :type="show ? 'text' : 'password'" name="{{ $name }}" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}"
           {{ $attributes->merge(['class' => 'w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg pl-3 pr-10 text-[13px] outline-none focus:border-brand']) }}>
    <button type="button" @click="show=!show" tabindex="-1" aria-label="Toggle password visibility"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#9AA0AA] hover:text-[#565b64]">
        <x-icon name="eye" class="w-4 h-4" x-show="!show" />
        <x-icon name="eye-off" class="w-4 h-4" x-show="show" x-cloak />
    </button>
</div>
