<div class="flex gap-2 justify-center mb-6">
    @for ($i = 1; $i <= 4; $i++)
        <span class="w-[26px] h-1 rounded-full" style="background: {{ $i <= $active ? '#4B45C7' : '#E4E3F6' }};"></span>
    @endfor
</div>
