@if (! empty($iconUrl))
    <img src="{{ $iconUrl }}" alt="{{ $appName }}" class="w-9 h-9 rounded-[9px] object-contain">
@else
    <div class="w-9 h-9 rounded-[9px] bg-brand flex items-center justify-center shadow-[0_2px_6px_rgba(75,69,199,.35)]">
        <x-icon name="lock" class="w-[18px] h-[18px] text-white" sw="2.2" />
    </div>
@endif
