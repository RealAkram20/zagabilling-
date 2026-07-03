@props(['name', 'checked' => false])

<label class="relative inline-flex items-center cursor-pointer flex-none">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) {{ $attributes }} class="sr-only peer">
    <span class="block w-[42px] h-[23px] rounded-full bg-[#D8DBE0] peer-checked:bg-brand transition-colors"></span>
    <span class="absolute left-[2.5px] top-[2.5px] w-[18px] h-[18px] rounded-full bg-white shadow transition-transform peer-checked:translate-x-[19px]"></span>
</label>
