@extends('layouts.admin')

@section('title', $device->name ?: $device->account_number)

@section('content')
<div class="flex items-center gap-2 text-[12.5px] text-[#9AA0AA] mb-4">
    <a href="{{ route('admin.devices.index') }}" class="text-[#787E88] hover:text-brand">Devices</a>
    <span>/</span>
    <span class="text-[#1A1D23] tnum">{{ $device->account_number }}</span>
</div>

@if (session('unlock_token'))
    <div class="mb-4 bg-white border-2 border-dashed border-brand-100 rounded-[14px] p-5"
         x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ session('unlock_token') }}'); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
        <div class="flex items-center gap-2 text-[13px] font-semibold text-brand mb-1"><x-icon name="unlock" class="w-4 h-4" sw="2" /> First unlock token</div>
        <p class="text-[12.5px] text-[#787E88] mb-3">Give this code to the client. They enter it on the computer to start the program.</p>
        <div class="flex items-center justify-between gap-3 bg-brand-50 rounded-xl px-4 py-3 flex-wrap">
            <span class="tnum text-[24px] sm:text-[26px] font-bold tracking-[0.12em] text-brand font-mono break-all">{{ session('unlock_token') }}</span>
            <button @click="copy()" class="h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium flex items-center gap-2">
                <x-icon name="copy" class="w-4 h-4" x-show="!copied" /><x-icon name="check" class="w-4 h-4 text-[#0F7B54]" x-show="copied" x-cloak />
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
    </div>
@endif

@if (session('uninstall_code'))
    <div class="mb-4 bg-white border-2 border-dashed border-[#E7C9C4] rounded-[14px] p-5"
         x-data="{ copied: false, copy() { navigator.clipboard.writeText('{{ session('uninstall_code') }}'); this.copied = true; setTimeout(() => this.copied = false, 1500); } }">
        <div class="flex items-center gap-2 text-[13px] font-semibold text-[#8A2B23] mb-1"><x-icon name="shield" class="w-4 h-4" sw="2" /> Uninstall authorization code</div>
        <p class="text-[12.5px] text-[#787E88] mb-3">This fixed code was set on the device at registration. Enter it in the offline app to authorize removing the lock client. Keep it private — reveal it only when the plan is complete.</p>
        <div class="flex items-center justify-between gap-3 bg-[#FCF3F1] rounded-xl px-4 py-3 flex-wrap">
            <span class="tnum text-[22px] sm:text-[24px] font-bold tracking-[0.12em] text-[#8A2B23] font-mono break-all">{{ session('uninstall_code') }}</span>
            <button @click="copy()" class="h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium flex items-center gap-2">
                <x-icon name="copy" class="w-4 h-4" x-show="!copied" /><x-icon name="check" class="w-4 h-4 text-[#0F7B54]" x-show="copied" x-cloak />
                <span x-text="copied ? 'Copied' : 'Copy'"></span>
            </button>
        </div>
    </div>
@endif

<div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,20,28,.03)] mb-4">
    <div class="flex items-start gap-4 flex-wrap">
        <div class="w-14 h-14 rounded-[13px] bg-brand-50 flex items-center justify-center text-brand flex-none">
            <x-icon name="monitor" class="w-6 h-6" sw="1.8" />
        </div>
        <div class="flex-1 min-w-[220px]">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-[22px] font-bold tracking-[-0.02em]">{{ $device->name ?: $device->account_number }}</h1>
                <x-status-badge :status="$device->status" />
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 mt-4">
                <div><div class="text-[11px] text-[#9AA0AA]">Account</div><div class="text-[13px] font-medium mt-0.5 tnum text-brand">{{ $device->account_number }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Model</div><div class="text-[13px] font-medium mt-0.5">{{ $device->model ?? '—' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Serial</div><div class="text-[13px] font-medium mt-0.5 tnum">{{ $device->serial ?? '— awaiting enrollment' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Manufacturer</div><div class="text-[13px] font-medium mt-0.5">{{ $device->manufacturer ?? '—' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Hostname</div><div class="text-[13px] font-medium mt-0.5">{{ $device->hostname ?? '—' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Price</div><div class="text-[13px] font-medium mt-0.5 tnum">{{ money($device->price) }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Client</div><div class="text-[13px] font-medium mt-0.5">{{ $device->client->name ?? 'Unassigned' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Plan</div><div class="text-[13px] font-medium mt-0.5">{{ $device->plan->name ?? '—' }}</div></div>
                <div><div class="text-[11px] text-[#9AA0AA]">Enrolled</div><div class="text-[13px] font-medium mt-0.5 tnum">{{ $device->activated_at?->format('M j, Y') ?? '—' }}</div></div>
                <div>
                    <div class="text-[11px] text-[#9AA0AA]">Renews / Due</div>
                    @if ($device->next_due_at)
                        @php $dueTone = in_array($device->status, ['overdue', 'locked'], true) ? 'text-[#B23A30]' : ($device->status === 'grace' ? 'text-[#A05A00]' : 'text-[#1A1D23]'); @endphp
                        <div class="text-[13px] font-medium mt-0.5 tnum {{ $dueTone }}">
                            {{ $device->next_due_at->format('M j, Y') }}
                            <span class="text-[11px] font-normal text-[#9AA0AA]">({{ $device->next_due_at->isPast() ? $device->next_due_at->diffForHumans() : 'in ' . $device->next_due_at->diffForHumans(null, true) }})</span>
                        </div>
                    @else
                        <div class="text-[13px] font-medium mt-0.5 text-[#9AA0AA]">— no unlock issued yet</div>
                    @endif
                </div>
            </div>
        </div>
        @can('manage-devices')
            <div class="flex items-center gap-2 flex-none">
                <a href="{{ route('admin.devices.edit', $device) }}" class="flex items-center gap-1.5 h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="pencil" class="w-3.5 h-3.5" /> Edit
                </a>
                <form method="POST" action="{{ route('admin.devices.destroy', $device) }}" data-confirm="Delete {{ $device->account_number }}? This removes its payments and unlock codes.">
                    @csrf
                    @method('DELETE')
                    <button class="flex items-center gap-1.5 h-9 px-3 rounded-lg border border-[#E7C9C4] bg-white text-[12.5px] font-medium text-[#B23A30] hover:bg-[#FDF6F5]"><x-icon name="trash" class="w-3.5 h-3.5" /> Delete</button>
                </form>
            </div>
        @endcan
    </div>
</div>

@if ($device->client)
    <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)] mb-4">
        <div class="flex items-center gap-2 mb-4">
            <x-icon name="phone" class="w-[17px] h-[17px] text-brand" />
            <span class="text-[14.5px] font-semibold">Contact</span>
            @if (in_array($device->status, ['grace', 'overdue', 'locked'], true))
                <span class="px-2 h-5 inline-flex items-center rounded-full bg-[#FBEAE8] border border-[#F1C9C4] text-[10.5px] font-semibold text-[#B23A30] capitalize">{{ $device->status }}</span>
            @endif
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="border border-[#EEF0F3] rounded-xl p-4">
                <div class="text-[11px] text-[#9AA0AA] mb-1.5">Client</div>
                <a href="{{ route('admin.clients.index', ['client' => $device->client->id]) }}" class="text-[14px] font-semibold hover:text-brand">{{ $device->client->name }}</a>
                <div class="mt-2 space-y-1">
                    @if ($device->client->phone)
                        <a href="tel:{{ $device->client->phone }}" class="flex items-center gap-1.5 text-[13px] tnum font-medium text-[#1A1D23] hover:text-brand">
                            <x-icon name="phone" class="w-3.5 h-3.5 text-[#9AA0AA]" /> {{ $device->client->phone }}
                        </a>
                    @endif
                    @if ($device->client->email)
                        <div class="text-[12px] text-[#787E88] truncate">{{ $device->client->email }}</div>
                    @endif
                    @if ($device->client->national_id)
                        <div class="text-[11.5px] text-[#9AA0AA] tnum">ID {{ $device->client->national_id }}</div>
                    @endif
                </div>
            </div>

            <div class="border border-[#EEF0F3] rounded-xl p-4">
                <div class="text-[11px] text-[#9AA0AA] mb-1.5">Alternate contact</div>
                @if ($device->client->hasAltContact())
                    <div class="text-[14px] font-semibold">{{ $device->client->alt_contact_name ?: 'Not named' }}</div>
                    @if ($device->client->alt_contact_relationship)
                        <div class="text-[11.5px] text-[#9AA0AA] capitalize">{{ $device->client->alt_contact_relationship }}</div>
                    @endif
                    <div class="mt-2">
                        <a href="tel:{{ $device->client->alt_contact_phone }}" class="flex items-center gap-1.5 text-[13px] tnum font-medium text-[#1A1D23] hover:text-brand">
                            <x-icon name="phone" class="w-3.5 h-3.5 text-[#9AA0AA]" /> {{ $device->client->alt_contact_phone }}
                        </a>
                    </div>
                @else
                    <div class="text-[13px] text-[#9AA0AA] mt-1">None recorded.</div>
                    @can('manage-clients')
                        <a href="{{ route('admin.clients.index', ['client' => $device->client->id]) }}" class="inline-flex items-center gap-1 text-[12px] font-medium text-brand hover:underline mt-2">
                            <x-icon name="plus" class="w-3 h-3" /> Add one
                        </a>
                    @endcan
                @endif
            </div>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        @if ($device->isEnrolled())
            @php
                $progress = $device->progress();
                $paid = $progress['paid'];
                $total = $progress['total'];
                $per = $device->installmentAmount();
                $financed = $device->financedAmount();
                $deposit = $device->depositAmount();
            @endphp
            <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                    <div class="text-[14.5px] font-semibold">Installment plan</div>
                    <div class="text-[13px] text-[#787E88]">{{ $device->plan->name }} · <span class="tnum">{{ money($per, 0) }}</span>/{{ $device->plan->cadenceLabel() }}</div>
                </div>
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="border border-[#EEF0F3] rounded-xl p-3">
                        <div class="text-[11px] text-[#9AA0AA]">Device price</div>
                        <div class="tnum text-[16px] font-bold mt-0.5">{{ money($device->price, 0) }}</div>
                    </div>
                    <div class="border border-[#EEF0F3] rounded-xl p-3">
                        <div class="text-[11px] text-[#9AA0AA]">Deposit ({{ rtrim(rtrim(number_format((float) $device->plan->deposit_percentage, 2), '0'), '.') }}%)</div>
                        <div class="tnum text-[16px] font-bold mt-0.5">{{ money($deposit, 0) }}</div>
                    </div>
                    <div class="border border-[#EEF0F3] rounded-xl p-3">
                        <div class="text-[11px] text-[#9AA0AA]">Financed</div>
                        <div class="tnum text-[16px] font-bold mt-0.5">{{ money($financed, 0) }}</div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[12px] mb-2">
                    <span class="text-[#565b64] font-medium">{{ $paid }} of {{ $total }} paid</span>
                    <span class="text-[#787E88] tnum">{{ money($device->balance, 0) }} remaining of {{ money($financed, 0) }}</span>
                </div>
                <x-progress-bar :paid="$paid" :total="$total" />
                <div class="flex flex-wrap gap-1.5 mt-5">
                    @for ($i = 1; $i <= $total; $i++)
                        @php $cls = $i <= $paid ? 'bg-brand' : ($i === $progress['current'] ? 'bg-[#FBF1DD] border-[1.5px] border-[#E0B84B]' : 'bg-[#F1F2F4]'); @endphp
                        <div class="w-[26px] h-[26px] rounded-[6px] {{ $cls }}"></div>
                    @endfor
                </div>
                <div class="flex flex-wrap gap-4 mt-4 text-[11.5px] text-[#787E88]">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-brand"></span>Paid</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#FBF1DD] border border-[#E0B84B]"></span>Due now</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#F1F2F4]"></span>Upcoming</span>
                </div>
            </div>

            <div class="bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
                <div class="px-5 py-4 border-b border-[#EEF0F3] text-[14.5px] font-semibold">Payment history</div>
                <div class="overflow-x-auto">
                    <div class="min-w-[440px]">
                        <div class="grid grid-cols-[0.9fr_1fr_0.7fr_auto] gap-3 px-5 py-2.5 text-[10.5px] font-semibold uppercase tracking-wide text-[#A6ABB4] border-b border-[#F1F2F4]">
                            <span>Date</span><span>Method</span><span class="text-right">Amount</span><span class="text-right">Status</span>
                        </div>
                        @forelse ($device->payments->sortByDesc('created_at') as $payment)
                            <div class="grid grid-cols-[0.9fr_1fr_0.7fr_auto] gap-3 px-5 py-3 text-[13px] items-center border-b border-[#F4F5F7] last:border-0">
                                <span class="tnum text-[#787E88]">{{ $payment->paid_at?->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</span>
                                <span class="text-[#565b64]">{{ $payment->method_label ?? 'PesaPal' }}</span>
                                <span class="tnum text-right font-semibold">{{ money($payment->amount) }}</span>
                                <span class="text-right"><x-status-badge :status="$payment->status" /></span>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-[13px] text-[#9AA0AA]">No payments recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-5 shadow-[0_1px_2px_rgba(16,20,28,.03)]">
                <div class="flex items-center gap-2 mb-1"><x-icon name="clients" class="w-[18px] h-[18px] text-brand" /><span class="text-[14.5px] font-semibold">Enroll to a client</span></div>
                <p class="text-[12.5px] text-[#8A909A] mb-4">This device is in inventory at <span class="tnum font-medium">{{ money($device->price) }}</span>. Assign a client and installment plan to activate it.</p>
                @can('manage-devices')
                    @php
                        $planTerms = $plans->mapWithKeys(fn ($p) => [$p->id => [
                            'name' => $p->name,
                            'term' => (int) $p->term_months,
                            'deposit' => (float) $p->deposit_percentage,
                            'cadence' => $p->cadenceLabel(),
                        ]]);
                    @endphp
                    <form method="POST" action="{{ route('admin.devices.enroll', $device) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                          x-data="{
                              price: {{ (float) $device->price }},
                              planId: '{{ old('plan_id') }}',
                              plans: {!! json_encode($planTerms) !!},
                              get plan() { return this.plans[this.planId] || null; },
                              get deposit() { return this.plan ? Math.ceil((this.price * this.plan.deposit / 100) / 100) * 100 : 0; },
                              get financed() { return this.plan ? (this.price - this.deposit) : 0; },
                              get per() { return (this.plan && this.plan.term) ? Math.ceil(this.financed / this.plan.term / 100) * 100 : 0; }
                          }">
                        @csrf
                        <div>
                            <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Client <span class="text-[#C2453D]">*</span></label>
                            <select name="client_id" required class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                                <option value="">Select client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-[#4A4F58] mb-1.5">Plan <span class="text-[#C2453D]">*</span></label>
                            <select name="plan_id" x-model="planId" required
                                    class="w-full h-10 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-2.5 text-[13px] outline-none focus:border-brand">
                                <option value="">Select plan</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>{{ $plan->name }} — {{ $plan->term_months }}× · {{ rtrim(rtrim(number_format((float) $plan->deposit_percentage, 2), '0'), '.') }}% deposit</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2" x-show="plan" x-cloak>
                            <div class="rounded-xl border border-[#EEF0F3] p-4">
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div><div class="text-[11px] text-[#9AA0AA]">Deposit</div><div class="tnum text-[15px] font-bold mt-0.5" x-text="zagaMoney(deposit, 0)"></div></div>
                                    <div><div class="text-[11px] text-[#9AA0AA]">Financed</div><div class="tnum text-[15px] font-bold mt-0.5" x-text="zagaMoney(financed, 0)"></div></div>
                                    <div><div class="text-[11px] text-[#9AA0AA]">Per installment</div><div class="tnum text-[15px] font-bold mt-0.5 text-brand" x-text="zagaMoney(per, 0)"></div></div>
                                </div>
                                <div class="text-[12px] text-[#787E88]" x-text="plan ? (plan.term + ' payments of ' + zagaMoney(per, 0) + ' · ' + plan.cadence) : ''"></div>
                            </div>
                        </div>
                        <div class="sm:col-span-2 flex items-center justify-between gap-3">
                            <p class="text-[11.5px] text-[#9AA0AA]">The first due date is set by the plan — the deposit grants the first period, and the first unlock code starts the term.</p>
                            <button class="h-10 px-4 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)] flex-none">Enroll device</button>
                        </div>
                    </form>
                @else
                    <p class="text-[12.5px] text-[#9AA0AA]">You don't have permission to enroll devices.</p>
                @endcan
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="bg-white border border-[#E7C9C4] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden"
             x-data="{ revealed:false, loading:false, bios:'', recovery:'', copiedBios:false, copiedKey:false,
                async reveal(){ this.loading=true;
                    const r = await fetch('{{ route('admin.devices.vault', $device) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, credentials:'same-origin'});
                    const d = await r.json(); this.bios=d.bios_password; this.recovery=d.recovery_key; this.revealed=true; this.loading=false; },
                copy(text, which){ navigator.clipboard.writeText(text); this[which]=true; setTimeout(() => { this[which]=false; }, 1500); } }">
            <div class="flex items-center justify-between px-[18px] py-3.5 bg-[#FCF3F1] border-b border-[#F1DDD8]">
                <div class="flex items-center gap-2 text-[13.5px] font-semibold text-[#8A2B23]"><x-icon name="shield" class="w-4 h-4" sw="1.9" /> Secure vault</div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-[5px]" style="color:#B23A30;background:#FBEAE8;">SENSITIVE</span>
            </div>
            <div class="p-[18px] space-y-3">
                <div>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">BIOS password</div>
                    <div class="flex items-center gap-2">
                        <div class="text-[13px] font-mono tnum flex-1 break-all" x-text="revealed ? (bios || '—') : '•••• •••• ••••'"></div>
                        <button type="button" x-show="revealed && bios" x-cloak @click="copy(bios, 'copiedBios')" title="Copy BIOS password"
                                class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="!copiedBios" />
                            <x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copiedBios" x-cloak />
                        </button>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">BitLocker recovery key</div>
                    <div class="flex items-center gap-2">
                        <div class="text-[13px] font-mono tnum flex-1 break-all" x-text="revealed ? (recovery || '—') : '••••••-••••••-••••••-••••••'"></div>
                        <button type="button" x-show="revealed && recovery" x-cloak @click="copy(recovery, 'copiedKey')" title="Copy recovery key"
                                class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="!copiedKey" />
                            <x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copiedKey" x-cloak />
                        </button>
                    </div>
                </div>
                @can('reveal-vault')
                    <button @click="reveal()" x-show="!revealed" :disabled="loading"
                            class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                        <x-icon name="eye" class="w-4 h-4" sw="1.9" /> <span x-text="loading ? 'Revealing…' : 'Reveal credentials'"></span>
                    </button>
                    <p x-show="revealed" x-cloak class="text-[11px] text-[#9AA0AA]">Reveal was recorded in the audit log.</p>
                @else
                    <p class="text-[11px] text-[#9AA0AA]">You do not have permission to reveal these credentials.</p>
                @endcan
            </div>
        </div>

        @can('manage-devices')
        <div class="bg-white border border-[#C7DDF2] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden"
             x-data="{ code:@js(session('enrollment_code') ?? ''), expires:@js(session('enrollment_code') ? 'in 24 hours' : ''), loading:false, copied:false,
                async issue(){ this.loading=true;
                    const r = await fetch('{{ route('admin.devices.enrollCode', $device) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, credentials:'same-origin'});
                    const d = await r.json(); this.code=d.code; this.expires=d.expires_human; this.loading=false; },
                copy(){ navigator.clipboard.writeText(this.code); this.copied=true; setTimeout(() => { this.copied=false; }, 1500); } }">
            <div class="flex items-center justify-between px-[18px] py-3.5 bg-[#EFF6FD] border-b border-[#DBE9F7]">
                <div class="flex items-center gap-2 text-[13.5px] font-semibold text-[#1F5C99]"><x-icon name="shield" class="w-4 h-4" sw="1.9" /> Enrollment code</div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-[5px]" style="color:#1F5C99;background:#E1EDFA;">ONE-TIME</span>
            </div>
            <div class="p-[18px] space-y-3">
                <p class="text-[11.5px] text-[#8A909A]">For a device that can reach this portal. Run the Zaga app on the device, choose <span class="font-medium text-[#4A4F58]">Enroll device</span>, and enter the portal address and this code. It provisions itself over the network — no secret is typed by hand. Valid 24 hours, single use.</p>
                <div x-show="code" x-cloak>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">Code</div>
                    <div class="flex items-center gap-2">
                        <div class="text-[18px] font-mono font-semibold tracking-[.08em] flex-1 break-all" x-text="code"></div>
                        <button type="button" @click="copy()" class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="!copied" /><x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copied" x-cloak />
                        </button>
                    </div>
                    <p class="text-[11px] text-[#9AA0AA] mt-1.5">Expires <span x-text="expires"></span>. Issuing a new code replaces this one.</p>
                </div>
                <button @click="issue()" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="shield" class="w-4 h-4" sw="1.9" /> <span x-text="loading ? 'Generating…' : (code ? 'Generate a new code' : 'Generate enrollment code')"></span>
                </button>
            </div>
        </div>
        @endcan

        @can('reveal-provisioning')
        <div class="bg-white border border-[#D8C4E7] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden"
             x-data="{ code:'', account:'', loading:false, copied:false,
                async issue(){ if (! await window.zagaConfirm('Issue the offline enrollment code? It carries the device secret, and typing it into the Zaga app enrolls and immediately locks the device.')) return; this.loading=true;
                    const r = await fetch('{{ route('admin.devices.offlineEnrollCode', $device) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, credentials:'same-origin'});
                    const d = await r.json(); this.code=d.code; this.account=d.account_number; this.loading=false; },
                copy(){ navigator.clipboard.writeText(this.code); this.copied=true; setTimeout(() => { this.copied=false; }, 1500); } }">
            <div class="flex items-center justify-between px-[18px] py-3.5 bg-[#F4EEFA] border-b border-[#E4D8F0]">
                <div class="flex items-center gap-2 text-[13.5px] font-semibold text-[#5B3A8A]"><x-icon name="lock" class="w-4 h-4" sw="1.9" /> Offline enrollment code</div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-[5px]" style="color:#5B3A8A;background:#EDE3F7;">SUPER ADMIN</span>
            </div>
            <div class="p-[18px] space-y-3">
                <p class="text-[11.5px] text-[#8A909A]">No internet at the device? Type this code into the Zaga app under <span class="font-medium text-[#5B3A8A]">Enroll device</span> — it enrolls entirely offline. The device locks the moment it enrolls, so <span class="font-medium text-[#5B3A8A]">take an unlock code with you</span>. The account number registered here must match the one the device's app displays — check it before issuing.</p>
                <div x-show="code" x-cloak>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">Code — for account <span class="font-mono" x-text="account"></span></div>
                    <div class="flex items-start gap-2">
                        <div class="text-[15px] font-mono font-semibold tracking-[.06em] flex-1 break-all" x-text="code"></div>
                        <button type="button" @click="copy()" class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="!copied" /><x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copied" x-cloak />
                        </button>
                    </div>
                    <p class="text-[11px] text-[#9AA0AA] mt-1.5">Issuing again shows the same code — it never expires. Each issue is recorded in the audit log.</p>
                </div>
                <button @click="issue()" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="lock" class="w-4 h-4" sw="1.9" /> <span x-text="loading ? 'Issuing…' : (code ? 'Show the code again' : 'Issue offline enrollment code')"></span>
                </button>
            </div>
        </div>

        <div class="bg-white border border-[#D8C4E7] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden"
             x-data="{ revealed:false, loading:false, secret:'', account:'', copied:'',
                async reveal(){ if (! await window.zagaConfirm('Reveal the provisioning bundle? This exposes the device HMAC secret for burning into the offline client.')) return; this.loading=true;
                    const r = await fetch('{{ route('admin.devices.provisioning', $device) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, credentials:'same-origin'});
                    const d = await r.json(); this.account=d.account_number; this.secret=d.hmac_secret; this.revealed=true; this.loading=false; },
                copy(text, which){ navigator.clipboard.writeText(text); this.copied=which; setTimeout(() => { this.copied=''; }, 1500); } }">
            <div class="flex items-center justify-between px-[18px] py-3.5 bg-[#F4EEFA] border-b border-[#E4D8F0]">
                <div class="flex items-center gap-2 text-[13.5px] font-semibold text-[#5B3A8A]"><x-icon name="shield" class="w-4 h-4" sw="1.9" /> Provisioning bundle</div>
                <span class="text-[10px] font-semibold px-2 py-1 rounded-[5px]" style="color:#5B3A8A;background:#EDE3F7;">SUPER ADMIN</span>
            </div>
            <div class="p-[18px] space-y-3">
                <p class="text-[11.5px] text-[#8A909A]">Load these into the offline lock client at install time so it can verify unlock tokens without internet. The HMAC secret never leaves this bundle — store it in the device key store, never in plain text.</p>
                <div>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">Account number</div>
                    <div class="flex items-center gap-2">
                        <div class="text-[13px] font-mono tnum flex-1 break-all" x-text="revealed ? account : '••••••••'"></div>
                        <button type="button" x-show="revealed" x-cloak @click="copy(account, 'acct')" class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="copied!=='acct'" /><x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copied==='acct'" x-cloak />
                        </button>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] text-[#9AA0AA] mb-1">HMAC secret (32 bytes, hex)</div>
                    <div class="flex items-center gap-2">
                        <div class="text-[12px] font-mono flex-1 break-all" x-text="revealed ? secret : '•••••••••••••••••••••••••••••••••'"></div>
                        <button type="button" x-show="revealed" x-cloak @click="copy(secret, 'sec')" class="w-8 h-8 rounded-lg border border-[#E4E6EB] bg-white flex items-center justify-center text-[#787E88] hover:bg-[#FBFBFC] flex-none">
                            <x-icon name="copy" class="w-3.5 h-3.5" x-show="copied!=='sec'" /><x-icon name="check" class="w-3.5 h-3.5 text-[#0F7B54]" x-show="copied==='sec'" x-cloak />
                        </button>
                    </div>
                </div>
                <button @click="reveal()" x-show="!revealed" :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                    <x-icon name="eye" class="w-4 h-4" sw="1.9" /> <span x-text="loading ? 'Revealing…' : 'Reveal provisioning bundle'"></span>
                </button>
                <p x-show="revealed" x-cloak class="text-[11px] text-[#9AA0AA]">Reveal was recorded in the audit log.</p>

                <div class="pt-3 border-t border-[#EFE7F7]">
                    <p class="text-[11.5px] text-[#8A909A] mb-2">No internet at the device? Download the bundle to a USB stick and use <span class="font-medium text-[#5B3A8A]">Provision offline</span> in the Zaga app. The device locks on provisioning, so take an unlock code with you.</p>
                    <a href="{{ route('admin.devices.provisioning.export', $device) }}"
                       class="w-full flex items-center justify-center gap-2 h-10 rounded-lg border border-[#D8C4E7] bg-white text-[12.5px] font-medium text-[#5B3A8A] hover:bg-[#FBF8FE]">
                        <x-icon name="download" class="w-4 h-4" sw="1.9" /> Download bundle file
                    </a>
                </div>
            </div>
        </div>
        @endcan

        <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-[18px] shadow-[0_1px_2px_rgba(16,20,28,.03)]">
            <div class="text-[14.5px] font-semibold mb-3">Actions</div>
            <div class="space-y-2.5">
                @can('issue-codes')
                    @if ($device->isEnrolled())
                        <button type="button" @click="$dispatch('open-collect')"
                                class="w-full h-10 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)]">Collect payment</button>
                    @else
                        <p class="text-[12px] text-[#9AA0AA]">Enroll this device to a client before collecting payments.</p>
                    @endif
                @endcan
                @can('manage-devices')
                    @if ($device->isLocked())
                        <form method="POST" action="{{ route('admin.devices.unlock', $device) }}">
                            @csrf
                            <button class="w-full h-10 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">Unlock device</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.devices.uninstallAuth', $device) }}"
                          data-confirm="Reveal the recorded uninstall code for this device?">
                        @csrf
                        <button class="w-full h-10 rounded-lg border border-[#E7C9C4] bg-white text-[13px] font-medium text-[#B23A30] hover:bg-[#FDF6F5]">Uninstall authorization</button>
                    </form>
                    @error('uninstall')
                        <p class="text-[11.5px] text-[#B23A30]">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-[#9AA0AA]">The uninstall code is generated by the app on the device and recorded here. @if (! $device->uninstall_code)<span class="text-[#8A6410]">None recorded yet — add it on <a href="{{ route('admin.devices.edit', $device) }}" class="underline">Edit</a>.</span>@endif</p>
                @endcan
            </div>
        </div>

        <div class="bg-white border border-[#E9EBEF] rounded-[14px] p-[18px] shadow-[0_1px_2px_rgba(16,20,28,.03)]">
            <div class="text-[14.5px] font-semibold mb-3">Unlock codes</div>
            @forelse ($device->unlockCodes->sortByDesc('created_at') as $code)
                <div class="flex items-center justify-between py-2 text-[13px] border-b border-[#F4F5F7] last:border-0">
                    <div>
                        <div class="font-medium">{{ ucfirst($code->type) }} · {{ $code->issuer->name ?? 'System' }}</div>
                        <div class="text-[11px] text-[#9AA0AA] mt-0.5">Expires {{ $code->expires_at->format('M j, g:i A') }}</div>
                    </div>
                    <span class="text-[11px] font-medium {{ $code->isExpired() ? 'text-[#B23A30]' : 'text-[#0F7B54]' }}">{{ $code->isExpired() ? 'Expired' : 'Valid' }}</span>
                </div>
            @empty
                <div class="py-4 text-center text-[13px] text-[#9AA0AA]">None issued yet.</div>
            @endforelse
        </div>
    </div>
</div>

@can('issue-codes')
@if ($device->isEnrolled())
<div x-data="{
        open: false, method: 'cash', periods: 1, per: {{ $device->installmentAmount() }}, maxPeriods: {{ max((int) $device->progress()['remaining'], 1) }}, phone: @js($device->client->phone ?? ''),
        stage: 'form', loading: false, code: '', message: '', iframeUrl: '', reference: '', timer: null,
        get amount() { return Math.round(this.per * this.periods * 100) / 100; },
        start() { this.open = true; this.stage = 'form'; this.periods = 1; this.code=''; this.message=''; this.iframeUrl=''; this.reference=''; },
        close() { this.open = false; if (this.timer) { clearInterval(this.timer); this.timer = null; }
            if (this.stage === 'done') { window.location.reload(); } },
        async submit() {
            this.loading = true; this.message = '';
            const body = new URLSearchParams({ method: this.method, installments: this.periods });
            if (this.method === 'mobile_money') body.append('phone', this.phone);
            try {
                const r = await fetch('{{ route('admin.devices.collect', $device) }}', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body });
                const d = await r.json();
                if (d.status === 'paid') { this.code = d.code; this.stage = 'done'; }
                else if (d.status === 'pending' && d.redirect_url) { this.iframeUrl = d.redirect_url; this.reference = d.reference; this.stage = 'iframe'; this.poll(); }
                else { this.message = d.message || 'Payment could not be started.'; this.stage = 'error'; }
            } catch (e) { this.message = 'Network error. Please try again.'; this.stage = 'error'; }
            this.loading = false;
        },
        poll() {
            this.timer = setInterval(async () => {
                const r = await fetch('{{ route('admin.devices.paymentStatus', $device) }}?reference=' + encodeURIComponent(this.reference), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (d.status === 'paid') { clearInterval(this.timer); this.timer = null; this.code = d.code; this.stage = 'done'; }
            }, 4000);
        }
     }"
     @open-collect.window="start()"
     x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(16,20,28,.45)"
     @keydown.escape.window="close()">
    <div @click.outside="close()" class="bg-white w-full max-w-md rounded-[16px] shadow-[0_20px_60px_rgba(16,20,28,.25)] overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#EEF0F3]">
            <div class="text-[15px] font-semibold">Collect payment</div>
            <button @click="close()" class="text-[#9AA0AA] hover:text-[#4A4F58]"><x-icon name="x" class="w-4 h-4" /></button>
        </div>

        <template x-if="stage === 'form'">
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="method='cash'"
                            class="h-11 rounded-lg text-[13px] font-medium border transition flex items-center justify-center gap-2"
                            :class="method==='cash' ? 'bg-brand-50 text-brand border-brand-100' : 'bg-[#FBFBFC] text-[#787E88] border-[#E4E6EB]'">
                        <x-icon name="dollar" class="w-4 h-4" sw="2" /> Cash
                    </button>
                    <button type="button" @click="method='mobile_money'"
                            class="h-11 rounded-lg text-[13px] font-medium border transition flex items-center justify-center gap-2"
                            :class="method==='mobile_money' ? 'bg-brand-50 text-brand border-brand-100' : 'bg-[#FBFBFC] text-[#787E88] border-[#E4E6EB]'">
                        <x-icon name="phone" class="w-4 h-4" sw="2" /> Mobile Money
                    </button>
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-[#565b64] mb-1.5">How many {{ \Illuminate\Support\Str::plural($device->plan->periodLabel()) }} to pay?</label>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg h-11 overflow-hidden">
                            <button type="button" @click="periods = Math.max(1, periods - 1)" class="w-11 h-full text-[18px] text-[#787E88] hover:bg-[#EFF1F4]">−</button>
                            <input type="number" min="1" :max="maxPeriods" x-model.number="periods" @input="periods = Math.min(Math.max(1, periods||1), maxPeriods)"
                                   class="w-14 h-full text-center bg-transparent text-[14px] tnum font-semibold outline-none">
                            <button type="button" @click="periods = Math.min(maxPeriods, periods + 1)" class="w-11 h-full text-[18px] text-[#787E88] hover:bg-[#EFF1F4]">+</button>
                        </div>
                        <div class="flex-1 text-right">
                            <div class="tnum text-[20px] font-bold" x-text="zagaMoney(amount)"></div>
                            <div class="text-[11px] text-[#9AA0AA]"><span x-text="periods"></span> × {{ money($device->installmentAmount()) }} / {{ $device->plan->periodLabel() }}</div>
                        </div>
                    </div>
                    <p class="text-[11px] text-[#9AA0AA] mt-1.5">Unlocks the device for <span x-text="periods * {{ $device->plan->cadenceDays() }}"></span> days from the current due date.</p>
                </div>
                <div x-show="method==='mobile_money'" x-cloak>
                    <label class="block text-[12px] font-medium text-[#565b64] mb-1.5">Phone number</label>
                    <input type="tel" x-model="phone" placeholder="+2547…"
                           class="w-full h-11 border border-[#E4E6EB] bg-[#F7F8FA] rounded-lg px-3 text-[14px] tnum outline-none focus:border-brand">
                    <p class="text-[11px] text-[#9AA0AA] mt-1.5">Defaults to the client's number. Edit it to bill a different phone.</p>
                </div>
                <button @click="submit()" :disabled="loading"
                        class="w-full h-11 rounded-lg bg-brand text-white text-[13px] font-semibold shadow-[0_1px_3px_rgba(75,69,199,.35)] disabled:opacity-60">
                    <span x-show="!loading" x-text="method==='cash' ? 'Record cash payment' : 'Request mobile money'"></span>
                    <span x-show="loading">Processing…</span>
                </button>
            </div>
        </template>

        <template x-if="stage === 'iframe'">
            <div class="p-5 space-y-3">
                <p class="text-[12.5px] text-[#787E88]">Complete the mobile money prompt below. This closes automatically once payment clears.</p>
                <iframe :src="iframeUrl" class="w-full h-[420px] rounded-lg border border-[#E9EBEF]"></iframe>
                <div class="flex items-center gap-2 text-[12px] text-[#9AA0AA]"><span class="w-2 h-2 rounded-full bg-[#C69214] animate-pulse"></span> Waiting for confirmation…</div>
            </div>
        </template>

        <template x-if="stage === 'done'">
            <div class="p-6 text-center" x-data="{ copied:false }">
                <div class="w-12 h-12 rounded-full bg-[#E9F6EF] text-[#0F7B54] flex items-center justify-center mx-auto mb-3"><x-icon name="check" class="w-6 h-6" sw="2.2" /></div>
                <div class="text-[15px] font-semibold mb-1">Payment recorded</div>
                <p class="text-[12.5px] text-[#787E88] mb-4">Share this unlock token with the client.</p>
                <div class="flex items-center justify-between gap-3 bg-brand-50 rounded-xl px-4 py-3">
                    <span class="tnum text-[22px] font-bold tracking-[0.12em] text-brand font-mono break-all" x-text="code"></span>
                    <button @click="navigator.clipboard.writeText(code); copied=true; setTimeout(()=>copied=false,1500)" class="h-9 px-3 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium flex items-center gap-2">
                        <x-icon name="copy" class="w-4 h-4" x-show="!copied" /><x-icon name="check" class="w-4 h-4 text-[#0F7B54]" x-show="copied" x-cloak />
                        <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                </div>
                <button @click="close()" class="mt-5 w-full h-10 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Done</button>
            </div>
        </template>

        <template x-if="stage === 'error'">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-[#FBEAE8] text-[#B23A30] flex items-center justify-center mx-auto mb-3"><x-icon name="x" class="w-6 h-6" sw="2.2" /></div>
                <div class="text-[15px] font-semibold mb-1">Could not process</div>
                <p class="text-[12.5px] text-[#787E88] mb-4" x-text="message"></p>
                <button @click="stage='form'" class="w-full h-10 rounded-lg border border-[#E4E6EB] bg-white text-[13px] font-medium text-[#4A4F58]">Try again</button>
            </div>
        </template>
    </div>
</div>
@endif
@endcan
@endsection
