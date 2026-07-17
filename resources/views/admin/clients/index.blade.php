@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
<div x-data="{
        open: false, loading: false,
        selected: [], pageIds: window.zagaClientIds || [],
        email: '{{ old('email') }}', emailStatus: '', emailTimer: null,
        checkEmail() {
            clearTimeout(this.emailTimer);
            if (! this.email.trim()) { this.emailStatus = ''; return; }
            this.emailStatus = 'checking';
            this.emailTimer = setTimeout(async () => { this.emailStatus = await window.zagaCheckEmail(this.email); }, 350);
        },
        async show(url) { this.open = true; this.loading = true; this.$refs.panelBody.innerHTML = '';
            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await r.text(); this.loading = false;
            this.$refs.panelBody.innerHTML = html; window.Alpine.initTree(this.$refs.panelBody); },
        addOpen: {{ $errors->any() ? 'true' : 'false' }},
        dq: '', results: [], picked: null, searching: false, timer: null,
        planId: '{{ old('plan_id') }}', plans: window.zagaPlans || {},
        get plan() { return this.plans[this.planId] || null; },
        get price() { return this.picked ? Number(this.picked.price || 0) : 0; },
        get deposit() { return this.plan ? Math.round(this.price * this.plan.deposit / 100) : 0; },
        get financed() { return this.plan ? (this.price - this.deposit) : 0; },
        get per() { return (this.plan && this.plan.term) ? Math.round(this.financed / this.plan.term) : 0; },
        searchDevices() { clearTimeout(this.timer); const q = this.dq;
            this.timer = setTimeout(async () => {
                if (q.trim().length < 1) { this.results = []; return; }
                this.searching = true;
                const r = await fetch('{{ route('admin.devices.search') }}?q=' + encodeURIComponent(q));
                this.results = await r.json(); this.searching = false;
            }, 250); },
        pick(d) { this.picked = d; this.results = []; this.dq = ''; }
    }">

    <x-page-header title="Clients" subtitle="{{ number_format($clients->total()) }} clients on the books.">
        @can('manage-clients')
            <x-slot name="actions">
                <button @click="addOpen=true" class="flex items-center gap-2 bg-brand text-white rounded-[9px] px-4 py-2.5 text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">
                    <x-icon name="plus" class="w-[15px] h-[15px]" sw="2.2" /> Add client
                </button>
            </x-slot>
        @endcan
    </x-page-header>

    {{-- Sort and page size submit on change, so the list is one interaction away from
         any arrangement rather than a form to fill in. --}}
    <form method="GET" class="flex items-center gap-2.5 mb-3.5 flex-wrap">
        <div class="relative flex-1 min-w-[200px] max-w-sm">
            <x-icon name="search" class="w-[15px] h-[15px] text-[#A6ABB4] absolute left-3 top-1/2 -translate-y-1/2" sw="2" />
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, phone, ID, email…"
                   class="w-full h-9 border border-[#E4E6EB] bg-white rounded-lg pl-9 pr-8 text-[13px] outline-none focus:border-brand">
            @if (! empty($filters['search']))
                <a href="{{ route('admin.clients.index', array_filter(['sort' => $filters['sort'] ?? null, 'per_page' => $perPage !== 15 ? $perPage : null])) }}"
                   aria-label="Clear search" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[#A6ABB4] hover:text-[#565b64]">
                    <x-icon name="x" class="w-3.5 h-3.5" sw="2.2" />
                </a>
            @endif
        </div>
        <button class="h-9 px-3.5 rounded-lg bg-brand text-white text-[12.5px] font-medium">Search</button>

        <select name="sort" onchange="this.form.submit()"
                class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none focus:border-brand cursor-pointer">
            @foreach (\App\Repositories\ClientRepository::SORTS as $key => [$label, , ])
                <option value="{{ $key }}" @selected(($filters['sort'] ?? 'recent') === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="per_page" onchange="this.form.submit()"
                class="h-9 border border-[#E4E6EB] bg-white rounded-lg px-2.5 text-[12.5px] text-[#4A4F58] outline-none focus:border-brand cursor-pointer">
            @foreach (\App\Repositories\ClientRepository::PER_PAGE_OPTIONS as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} per page</option>
            @endforeach
        </select>

        @if (! empty($filters['search']))
            <span class="text-[12.5px] text-[#787E88]">
                <span class="font-semibold text-[#1A1D23] tnum">{{ $clients->total() }}</span>
                {{ Str::plural('match', $clients->total()) }} for “{{ $filters['search'] }}”
            </span>
        @endif
    </form>

    @php
        $canManage = auth()->user()->can('manage-clients');
        $cols = $canManage ? 'grid-cols-[auto_1.4fr_1.6fr_0.7fr_1fr_auto]' : 'grid-cols-[1.4fr_1.6fr_0.7fr_1fr_auto]';
    @endphp

    @if ($canManage)
        <div x-show="selected.length" x-cloak class="flex items-center justify-between gap-3 mb-3 px-4 py-2.5 rounded-xl bg-brand-50 border border-brand-100">
            <span class="text-[13px] font-medium text-brand"><span x-text="selected.length"></span> selected</span>
            <div class="flex items-center gap-2">
                <button @click="selected=[]" class="h-8 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] text-[#4A4F58]">Clear</button>
                <form method="POST" action="{{ route('admin.clients.bulkDestroy') }}" data-confirm="Delete the selected clients? Their devices return to inventory.">
                    @csrf
                    @method('DELETE')
                    <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <button type="submit" class="h-8 px-3 rounded-lg bg-[#C2453D] text-white text-[12.5px] font-semibold flex items-center gap-1.5"><x-icon name="trash" class="w-3.5 h-3.5" /> Delete selected</button>
                </form>
            </div>
        </div>
    @endif

    <div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[680px]">
                <div class="grid {{ $cols }} gap-3.5 px-5 py-3 text-[10.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] bg-[#FBFBFC] border-b border-[#EEF0F3] items-center">
                    @if ($canManage)
                        <input type="checkbox" @change="selected = $event.target.checked ? [...pageIds] : []" :checked="pageIds.length && selected.length === pageIds.length" class="rounded border-[#D8DBE0] w-4 h-4">
                    @endif
                    <span>Name</span><span>Contact</span><span class="text-center">Devices</span><span class="text-right">Total balance</span><span></span>
                </div>
                @forelse ($clients as $index => $client)
                    @php $variant = ['brand', 'purple', 'orange', 'red'][$index % 4]; @endphp
                    <div @click="show('{{ route('admin.clients.panel', $client) }}')"
                         class="grid {{ $cols }} gap-3.5 px-5 py-3.5 text-[13px] items-center border-b border-[#F4F5F7] last:border-0 hover:bg-[#FBFAFF] transition cursor-pointer"
                         :class="selected.includes({{ $client->id }}) ? 'bg-[#FBFAFF]' : ''">
                        @if ($canManage)
                            <input type="checkbox" value="{{ $client->id }}" x-model.number="selected" @click.stop class="rounded border-[#D8DBE0] w-4 h-4">
                        @endif
                        <span class="flex items-center gap-2.5 min-w-0"><x-avatar :name="$client->name" :image="$client->avatarUrl()" :size="30" :variant="$variant" /><span class="font-medium truncate">{{ $client->name }}</span></span>
                        {{-- Phone leads: it is what anyone scanning this list is going to use. --}}
                        <span class="min-w-0">
                            <span class="block tnum truncate">{{ $client->phone ?? '—' }}</span>
                            @if ($client->email)
                                <span class="block text-[11.5px] text-[#9AA0AA] truncate">{{ $client->email }}</span>
                            @endif
                        </span>
                        <span class="tnum text-center">{{ $client->devices_count }}</span>
                        <span class="tnum text-right font-semibold">{{ money($client->total_balance, 0) }}</span>
                        <span class="text-[#C3C7CE]"><x-icon name="chevron-right" class="w-4 h-4" /></span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-[13px] text-[#9AA0AA]">No clients found.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- The count sits outside links(), which renders nothing at all on a single page.
         With a large list, knowing where you are is the point. --}}
    <div class="mt-4 flex items-center justify-between gap-3 flex-wrap">
        <span class="text-[12.5px] text-[#787E88]">
            @if ($clients->total() > 0)
                Showing <span class="tnum font-medium text-[#1A1D23]">{{ $clients->firstItem() }}–{{ $clients->lastItem() }}</span>
                of <span class="tnum font-medium text-[#1A1D23]">{{ $clients->total() }}</span> {{ Str::plural('client', $clients->total()) }}
            @else
                No clients to show
            @endif
        </span>
        <div>{{ $clients->links() }}</div>
    </div>

    {{-- Detail drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div x-show="open" x-transition.opacity @click="open=false" class="absolute inset-0 bg-[rgba(20,22,28,.28)]"></div>
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute top-0 right-0 bottom-0 w-full sm:w-[400px] bg-white shadow-[-8px_0_30px_rgba(16,20,28,.12)] flex flex-col">
            <button @click="open=false" class="absolute top-4 right-4 w-8 h-8 rounded-lg border border-[#E4E6EB] flex items-center justify-center text-[#9AA0AA] z-10"><x-icon name="x" class="w-4 h-4" /></button>
            <div class="flex-1 overflow-auto">
                <div x-show="loading" class="p-8 text-center text-[13px] text-[#9AA0AA]">Loading…</div>
                <div x-ref="panelBody"></div>
            </div>
        </div>
    </div>

    {{-- Add-client drawer --}}
    @can('manage-clients')
    <div x-show="addOpen" x-cloak class="fixed inset-0 z-50">
        <div x-show="addOpen" x-transition.opacity @click="addOpen=false" class="absolute inset-0 bg-[rgba(20,22,28,.28)]"></div>
        <div x-show="addOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute top-0 right-0 bottom-0 w-full sm:w-[420px] bg-white shadow-[-8px_0_30px_rgba(16,20,28,.12)] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#EEF0F3] flex-none">
                <div class="text-[15px] font-bold tracking-[-0.01em]">Add client</div>
                <button @click="addOpen=false" class="w-8 h-8 rounded-lg border border-[#E4E6EB] flex items-center justify-center text-[#9AA0AA]"><x-icon name="x" class="w-4 h-4" /></button>
            </div>
            <form method="POST" action="{{ route('admin.clients.store') }}" enctype="multipart/form-data" class="flex-1 overflow-auto px-6 py-5 space-y-4">
                @csrf
                @if ($errors->any())
                    <div class="px-4 py-3 rounded-lg bg-[#FBEAE8] border border-[#F1C9C4] text-[13px] text-[#B23A30]">{{ $errors->first() }}</div>
                @endif
                <div class="flex items-center gap-4" x-data="{ preview: '' }">
                    <template x-if="preview"><span class="w-14 h-14 rounded-full overflow-hidden bg-[#EFF1F4] flex-none"><img :src="preview" class="w-full h-full object-cover" alt=""></span></template>
                    <template x-if="!preview"><span class="w-14 h-14 rounded-full bg-[#EDECFB] text-brand flex items-center justify-center flex-none"><x-icon name="clients" class="w-6 h-6" /></span></template>
                    <label class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC] cursor-pointer">
                        <x-icon name="camera" class="w-4 h-4" sw="1.8" /> Add photo
                        <input type="file" name="avatar" accept="image/*" class="hidden" @change="const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); }">
                    </label>
                </div>
                <div>
                    <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Full name <span class="text-[#C2453D]">*</span></label>
                    <input name="name" value="{{ old('name') }}" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                </div>
                <div>
                    <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Email <span class="text-[#C2453D]">*</span></label>
                    <input name="email" type="email" x-model="email" @input="checkEmail()" required
                           class="w-full h-10 border rounded-lg px-3 text-[13px] outline-none focus:border-brand"
                           :class="emailStatus==='taken' || emailStatus==='invalid' ? 'border-[#E7C9C4] bg-[#FDF6F5]' : 'border-[#E4E6EB] bg-[#F7F8FA]'">
                    <div class="mt-1 text-[11px] h-3.5">
                        <span x-show="emailStatus==='checking'" x-cloak class="text-[#9AA0AA]">Checking…</span>
                        <span x-show="emailStatus==='invalid'" x-cloak class="text-[#B23A30]">Enter a valid email address.</span>
                        <span x-show="emailStatus==='taken'" x-cloak class="text-[#B23A30]">This email is already registered.</span>
                        <span x-show="emailStatus==='ok'" x-cloak class="text-[#0F7B54]">Email is available.</span>
                    </div>
                </div>
                <div>
                    <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Phone <span class="text-[#C2453D]">*</span></label>
                    <input name="phone" value="{{ old('phone') }}" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                </div>
                <div>
                    <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">National ID <span class="text-[#C2453D]">*</span></label>
                    <input name="national_id" value="{{ old('national_id') }}" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                </div>
                <div>
                    <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Address <span class="text-[#C2453D]">*</span></label>
                    <textarea name="address" rows="2" required class="w-full border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 py-2 text-[13px] outline-none focus:border-brand">{{ old('address') }}</textarea>
                </div>

                {{-- Someone to reach when the client's own phone is off. Optional, and
                     collapsed by default so it never slows down a routine sign-up —
                     but it reopens on validation errors so a half-filled entry is not
                     silently hidden. --}}
                <div class="rounded-xl border border-[#E9EBEF] p-4"
                     x-data="{ open: {{ $errors->has('alt_contact_name') || $errors->has('alt_contact_phone') || old('alt_contact_phone') ? 'true' : 'false' }} }">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[13px] font-semibold">Alternate contact <span class="text-[#9AA0AA] font-normal">(optional)</span></div>
                            <div class="text-[11.5px] text-[#9AA0AA] mt-0.5">Someone else to call if the client's phone is off. Shown on the device page.</div>
                        </div>
                        <button type="button" @click="open = !open"
                                class="flex-none h-8 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12px] font-semibold text-[#565b64] hover:border-brand hover:text-brand">
                            <span x-show="!open">Add</span>
                            <span x-show="open" x-cloak>Skip</span>
                        </button>
                    </div>
                    <div x-show="open" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Full name</label>
                            <input name="alt_contact_name" value="{{ old('alt_contact_name') }}"
                                   class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        </div>
                        <div>
                            <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Phone</label>
                            <input name="alt_contact_phone" value="{{ old('alt_contact_phone') }}"
                                   class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11.5px] font-medium text-[#565b64] mb-1.5">Relationship</label>
                            <input name="alt_contact_relationship" value="{{ old('alt_contact_relationship') }}" placeholder="e.g. spouse, brother, employer"
                                   class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] outline-none focus:border-brand">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-[#E9EBEF] p-4">
                    <div class="text-[13px] font-semibold mb-1">Start an installment <span class="text-[#9AA0AA] font-normal">(optional)</span></div>
                    <div class="text-[11.5px] text-[#9AA0AA] mb-3">Assign a device from inventory and begin a plan for the client.</div>

                    <div x-show="!picked" class="relative">
                        <x-icon name="search" class="w-[15px] h-[15px] text-[#A6ABB4] absolute left-3 top-1/2 -translate-y-1/2" sw="2" />
                        <input x-model="dq" @input="searchDevices()" placeholder="Search account, serial, model…" autocomplete="off"
                               class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg pl-9 pr-3 text-[13px] outline-none focus:border-brand">
                        <div x-show="searching" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-[#9AA0AA]">…</div>
                        <div x-show="results.length" x-cloak class="absolute z-10 left-0 right-0 mt-1 bg-white border border-[#E9EBEF] rounded-lg shadow-[0_4px_20px_rgba(16,20,28,.08)] max-h-56 overflow-auto">
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
                    <div x-show="picked" x-cloak class="flex items-center justify-between border border-[#E4E6EB] bg-[#FBFAFF] rounded-lg px-3 py-2.5">
                        <div class="min-w-0">
                            <div class="text-[13px] font-medium"><span class="tnum text-brand" x-text="picked?.account_number"></span> · <span x-text="picked?.model || '—'"></span></div>
                            <div class="text-[11px] text-[#0F7B54]">Available · ready to enroll</div>
                        </div>
                        <button type="button" @click="picked=null" class="text-[#9AA0AA] hover:text-[#B23A30]"><x-icon name="x" class="w-4 h-4" /></button>
                    </div>
                    <input type="hidden" name="device_id" :value="picked ? picked.id : ''">

                    <div x-show="picked" x-cloak class="grid grid-cols-2 gap-2 mt-3">
                        <select name="plan_id" x-model="planId" :required="picked" class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                            <option value="">Select plan</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->term_months }}× · {{ rtrim(rtrim(number_format((float) $plan->deposit_percentage, 2), '0'), '.') }}% deposit</option>
                            @endforeach
                        </select>
                        <input name="first_installment_days" type="number" min="0" max="365" value="{{ old('first_installment_days', 30) }}" placeholder="Days to first payment"
                               class="h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[13px] tnum outline-none focus:border-brand">
                    </div>

                    <div x-show="picked && plan" x-cloak class="rounded-lg border border-[#EEF0F3] p-3 mt-3">
                        <div class="grid grid-cols-3 gap-2 mb-2.5">
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Deposit</div><div class="tnum text-[13.5px] font-bold mt-0.5" x-text="zagaMoney(deposit, 0)"></div></div>
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Financed</div><div class="tnum text-[13.5px] font-bold mt-0.5" x-text="zagaMoney(financed, 0)"></div></div>
                            <div><div class="text-[10.5px] text-[#9AA0AA]">Per pay</div><div class="tnum text-[13.5px] font-bold mt-0.5 text-brand" x-text="zagaMoney(per, 0)"></div></div>
                        </div>
                        <div class="flex items-center justify-between text-[11.5px] mb-1.5">
                            <span class="text-[#565b64] font-medium" x-text="'0 of ' + (plan ? plan.term : 0) + ' paid'"></span>
                            <span class="text-[#787E88] tnum" x-text="zagaMoney(financed, 0) + ' remaining'"></span>
                        </div>
                        <div class="flex gap-1 items-center">
                            <div class="h-2 rounded-[5px] bg-[#C69214]" style="flex: 1;"></div>
                            <div class="h-2 rounded-[5px] bg-[#ECEDF1]" :style="'flex: ' + Math.max((plan ? plan.term : 1) - 1, 0)"></div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            <template x-for="i in (plan ? plan.term : 0)" :key="i">
                                <div class="w-[20px] h-[20px] rounded-[4px]" :class="i === 1 ? 'bg-[#FBF1DD] border border-[#E0B84B]' : 'bg-[#F1F2F4]'"></div>
                            </template>
                        </div>
                        <div class="flex flex-wrap gap-4 mt-3 text-[11px] text-[#787E88]">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-brand"></span>Paid</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#FBF1DD] border border-[#E0B84B]"></span>Due now</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#F1F2F4]"></span>Upcoming</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" :disabled="emailStatus==='invalid' || emailStatus==='taken' || emailStatus==='checking'"
                            class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)] disabled:opacity-50 disabled:cursor-not-allowed">Save client</button>
                    <button type="button" @click="addOpen=false" class="h-10 px-4 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@php
    $planData = $plans->mapWithKeys(fn ($p) => [$p->id => [
        'name' => $p->name,
        'term' => (int) $p->term_months,
        'deposit' => (float) $p->deposit_percentage,
        'cadence' => $p->cadenceLabel(),
    ]]);
@endphp
<script>
    window.zagaPlans = {!! json_encode($planData) !!};
    window.zagaClientIds = {!! json_encode($clients->pluck('id')->values()->all()) !!};
    window.zagaCheckEmailUrl = '{{ route('admin.clients.checkEmail') }}';
    window.zagaCheckEmail = async function (email) {
        const v = (email || '').trim();
        if (!v) return '';
        if (! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v)) return 'invalid';
        try {
            const r = await fetch(window.zagaCheckEmailUrl + '?email=' + encodeURIComponent(v));
            const d = await r.json();
            return d.available ? 'ok' : 'taken';
        } catch (e) { return ''; }
    };
</script>
@endsection
