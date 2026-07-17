<div x-data="{ open: false, message: '', resolve: null,
        cancel() { this.open = false; if (this.resolve) { this.resolve(false); this.resolve = null; } },
        accept() { this.open = false; if (this.resolve) { this.resolve(true); this.resolve = null; } } }"
     @zaga-confirm.window="message = $event.detail.message; resolve = $event.detail.resolve; open = true"
     @keydown.escape.window="if (open) cancel()"
     x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" style="background:rgba(16,20,28,.45)">
    <div @click.outside="cancel()" x-transition class="bg-white w-full max-w-sm rounded-[16px] shadow-[0_20px_60px_rgba(16,20,28,.25)] overflow-hidden">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-[#FBEAE8] text-[#B23A30] flex items-center justify-center flex-none"><x-icon name="shield" class="w-5 h-5" sw="2" /></div>
                <div class="pt-0.5 min-w-0">
                    <div class="text-[14.5px] font-semibold mb-1">Please confirm</div>
                    <p class="text-[12.5px] text-[#787E88] leading-relaxed" x-text="message"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" @click="cancel()" class="h-9 px-4 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">Cancel</button>
                <button type="button" @click="accept()" class="h-9 px-4 rounded-lg bg-brand text-white text-[12.5px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Confirm</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.zagaConfirm = function (message) {
        return new Promise(function (resolve) {
            window.dispatchEvent(new CustomEvent('zaga-confirm', { detail: { message: message, resolve: resolve } }));
        });
    };
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form && form.dataset && form.dataset.confirm) {
            e.preventDefault();
            window.zagaConfirm(form.dataset.confirm).then(function (ok) { if (ok) { form.submit(); } });
        }
    }, true);
</script>
