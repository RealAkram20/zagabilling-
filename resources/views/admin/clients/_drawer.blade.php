@php
    $totalBalance = $client->devices->sum('balance');
    $paidCount = $client->payments->where('status', 'paid')->count();
    $onTime = $client->payments->count() ? round($paidCount / $client->payments->count() * 100) : 100;
@endphp

<div x-data="{
        editing: false,
        dq: '', results: [], picked: null, searching: false, timer: null,
        planId: '', plans: window.zagaPlans || {},
        get plan() { return this.plans[this.planId] || null; },
        get price() { return this.picked ? Number(this.picked.price || 0) : 0; },
        get deposit() { return this.plan ? Math.ceil((this.price * this.plan.deposit / 100) / 100) * 100 : 0; },
        get financed() { return this.plan ? (this.price - this.deposit) : 0; },
        get per() { return (this.plan && this.plan.term) ? Math.ceil(this.financed / this.plan.term / 100) * 100 : 0; },
        searchDevices() { clearTimeout(this.timer); const q = this.dq;
            this.timer = setTimeout(async () => {
                if (q.trim().length < 1) { this.results = []; return; }
                this.searching = true;
                const r = await fetch('{{ route('admin.devices.search') }}?q=' + encodeURIComponent(q));
                this.results = await r.json(); this.searching = false;
            }, 250); },
        pick(d) { this.picked = d; this.results = []; this.dq = ''; }
    }">

    <div class="px-6 py-5 border-b border-[#EEF0F3]">
        <div class="flex items-center gap-3">
            <x-avatar :name="$client->name" :image="$client->avatarUrl()" :size="42" />
            <div class="min-w-0">
                <div class="text-[16px] font-bold tracking-[-0.01em] truncate">{{ $client->name }}</div>
                <div class="text-[12.5px] text-[#787E88] truncate">{{ $client->email ?? 'No email' }} · {{ $client->phone ?? 'No phone' }}</div>
            </div>
        </div>
        @can('manage-clients')
            <div class="flex items-center gap-2 mt-4">
                <button @click="editing = !editing" class="flex items-center gap-1.5 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="pencil" class="w-3.5 h-3.5" /> <span x-text="editing ? 'Close edit' : 'Edit'"></span>
                </button>
                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" data-confirm="Delete this client? Their devices return to inventory.">
                    @csrf
                    @method('DELETE')
                    <button class="flex items-center gap-1.5 h-9 px-3 rounded-lg border border-[#E7C9C4] bg-white text-[12.5px] font-medium text-[#B23A30] hover:bg-[#FDF6F5]"><x-icon name="trash" class="w-3.5 h-3.5" /> Delete</button>
                </form>
            </div>
        @endcan
    </div>

    @can('manage-clients')
        <form x-show="editing" x-cloak method="POST" action="{{ route('admin.clients.update', $client) }}" enctype="multipart/form-data"
              class="px-6 py-5 border-b border-[#EEF0F3] space-y-3" x-data="{ preview: '{{ $client->avatarUrl() }}' }">
            @csrf
            @method('PATCH')
            <div class="flex items-center gap-3">
                <template x-if="preview"><span class="w-12 h-12 rounded-full overflow-hidden bg-[#EFF1F4] flex-none"><img :src="preview" class="w-full h-full object-cover" alt=""></span></template>
                <template x-if="!preview"><span class="flex-none"><x-avatar :name="$client->name" :size="48" /></span></template>
                <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12px] font-medium text-[#4A4F58] cursor-pointer">
                    <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Change photo
                    <input type="file" name="avatar" accept="image/*" class="hidden" @change="const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); }">
                </label>
            </div>
            <input name="name" value="{{ $client->name }}" required placeholder="Full name" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            <input name="email" type="email" value="{{ $client->email }}" required placeholder="Email" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            <div class="grid grid-cols-2 gap-2">
                <input name="phone" value="{{ $client->phone }}" required placeholder="Phone" class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                <input name="national_id" value="{{ $client->national_id }}" required placeholder="National ID" class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
            </div>
            <textarea name="address" rows="2" required placeholder="Address" class="w-full border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 py-2 text-[13px] outline-none focus:border-brand">{{ $client->address }}</textarea>
            <div class="pt-1">
                <div class="text-[11.5px] font-semibold text-[#565b64] mb-1.5">Alternate contact <span class="text-[#9AA0AA] font-normal">(optional)</span></div>
                <div class="grid grid-cols-2 gap-2">
                    <input name="alt_contact_name" value="{{ $client->alt_contact_name }}" placeholder="Other person's name" class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                    <input name="alt_contact_phone" value="{{ $client->alt_contact_phone }}" placeholder="Their phone" class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                </div>
                <input name="alt_contact_relationship" value="{{ $client->alt_contact_relationship }}" placeholder="Relationship, e.g. spouse" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand mt-2">
            </div>
            <button type="submit" class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Save changes</button>
        </form>
    @endcan

    <div x-show="!editing" class="px-6 py-5">
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="border border-[#EEF0F3] rounded-xl p-4">
                <div class="text-[11px] text-[#9AA0AA]">Total balance</div>
                <div class="tnum text-[18px] font-bold mt-1">{{ money($totalBalance, 0) }}</div>
            </div>
            <div class="border border-[#EEF0F3] rounded-xl p-4">
                <div class="text-[11px] text-[#9AA0AA]">On-time rate</div>
                <div class="tnum text-[18px] font-bold mt-1 text-[#0F7B54]">{{ $onTime }}%</div>
            </div>
        </div>

        <div class="text-[13px] font-semibold mb-3">Devices held</div>
        <div class="space-y-2.5 mb-6">
            @forelse ($client->devices as $device)
                <a href="{{ route('admin.devices.show', $device) }}" class="flex items-center gap-3 border border-[#EEF0F3] rounded-[11px] px-4 py-3 hover:bg-[#FBFAFF] transition">
                    <div class="w-9 h-9 rounded-lg bg-brand-50 flex items-center justify-center text-brand flex-none"><x-icon name="monitor" class="w-[18px] h-[18px]" sw="1.8" /></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-medium truncate">{{ $device->model ?? ($device->plan->name ?? 'Device') }}</div>
                        <div class="text-[11.5px] text-[#9AA0AA] tnum">{{ $device->account_number }}</div>
                    </div>
                    <x-status-badge :status="$device->status" />
                </a>
            @empty
                <div class="text-[13px] text-[#9AA0AA]">No devices yet.</div>
            @endforelse
        </div>

        @can('manage-clients')
            <div class="rounded-xl border border-[#E9EBEF] p-4 mb-6">
                <div class="text-[13px] font-semibold mb-1">Start an installment</div>
                <div class="text-[11.5px] text-[#9AA0AA] mb-3">Assign a device from inventory and begin a plan for this client.</div>
                <form method="POST" action="{{ route('admin.clients.enroll', $client) }}">
                    @csrf
                    <div x-show="!picked" class="relative mb-2">
                        <x-icon name="search" class="w-[15px] h-[15px] text-[#A6ABB4] absolute left-3 top-1/2 -translate-y-1/2" sw="2" />
                        <input x-model="dq" @input="searchDevices()" placeholder="Search account, serial, model…" autocomplete="off"
                               class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg pl-9 pr-3 text-[13px] outline-none focus:border-brand">
                        <div x-show="results.length" x-cloak class="absolute z-10 left-0 right-0 mt-1 bg-white border border-[#E9EBEF] rounded-lg shadow-[0_4px_20px_rgba(16,20,28,.08)] max-h-48 overflow-auto">
                            <template x-for="d in results" :key="d.id">
                                <button type="button" @click="d.available && pick(d)" :disabled="!d.available"
                                        class="w-full text-left px-3 py-2.5 border-b border-[#F4F5F7] last:border-0 flex items-center justify-between gap-2"
                                        :class="d.available ? 'hover:bg-[#FBFAFF] cursor-pointer' : 'opacity-70 cursor-not-allowed'">
                                    <div class="min-w-0">
                                        <div class="text-[13px] font-medium"><span class="tnum text-brand" x-text="d.account_number"></span> · <span x-text="d.model || '—'"></span></div>
                                        <div class="text-[11px] text-[#9AA0AA]" x-text="d.available ? 'Available' : ('Taken · ' + d.client)"></div>
                                    </div>
                                    <span x-show="!d.available" class="text-[10px] font-semibold px-2 py-0.5 rounded-md flex-none" style="color:#B23A30;background:#FBEAE8;">Taken</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-show="picked" x-cloak class="flex items-center justify-between border border-[#E4E6EB] bg-[#FBFAFF] rounded-lg px-3 py-2.5 mb-2">
                        <div class="min-w-0">
                            <div class="text-[13px] font-medium"><span class="tnum text-brand" x-text="picked?.account_number"></span> · <span x-text="picked?.model || '—'"></span></div>
                        </div>
                        <button type="button" @click="picked=null" class="text-[#9AA0AA] hover:text-[#B23A30]"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                    <input type="hidden" name="device_id" :value="picked ? picked.id : ''">

                    <div x-show="picked" x-cloak class="mb-2">
                        <select name="plan_id" x-model="planId" :required="picked" class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                            <option value="">Select plan</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->term_months }}× · {{ rtrim(rtrim(number_format((float) $plan->deposit_percentage, 2), '0'), '.') }}% deposit</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="picked && plan" x-cloak class="rounded-lg border border-[#EEF0F3] p-3 mb-2">
                        <div class="grid grid-cols-3 gap-2 mb-2.5">
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Deposit</div><div class="tnum text-[13px] font-bold mt-0.5" x-text="zagaMoney(deposit, 0)"></div></div>
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Financed</div><div class="tnum text-[13px] font-bold mt-0.5" x-text="zagaMoney(financed, 0)"></div></div>
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Per pay</div><div class="tnum text-[13px] font-bold mt-0.5 text-brand" x-text="zagaMoney(per, 0)"></div></div>
                        </div>
                        <div class="flex items-center justify-between text-[11.5px] mb-1.5">
                            <span class="text-[#565b64] font-medium" x-text="'0 of ' + (plan ? plan.term : 0) + ' paid'"></span>
                            <span class="text-[#787E88] tnum" x-text="zagaMoney(financed, 0) + ' remaining'"></span>
                        </div>
                        <div class="flex gap-1 items-center">
                            <div class="h-2 rounded-[5px] bg-[#C69214]" style="flex: 1;"></div>
                            <div class="h-2 rounded-[5px] bg-[#ECEDF1]" :style="'flex: ' + Math.max((plan ? plan.term : 1) - 1, 0)"></div>
                        </div>
                        <div class="flex flex-wrap gap-1 mt-3">
                            <template x-for="i in (plan ? plan.term : 0)" :key="i">
                                <div class="w-[18px] h-[18px] rounded-[4px]" :class="i === 1 ? 'bg-[#FBF1DD] border border-[#E0B84B]' : 'bg-[#F1F2F4]'"></div>
                            </template>
                        </div>
                    </div>

                    <button type="submit" :disabled="!picked" class="w-full h-10 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)] disabled:opacity-50">Start installment</button>
                </form>
            </div>
        @endcan

        <div class="text-[13px] font-semibold mb-3">Recent payments</div>
        <div class="space-y-1">
            @forelse ($client->payments as $payment)
                <div class="flex items-center justify-between py-2 text-[13px] border-b border-[#F4F5F7] last:border-0">
                    <span class="tnum text-[#787E88]">{{ $payment->paid_at?->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</span>
                    <span class="tnum font-semibold">{{ money($payment->amount) }}</span>
                </div>
            @empty
                <div class="text-[13px] text-[#9AA0AA]">No payments.</div>
            @endforelse
        </div>
    </div>
</div>
