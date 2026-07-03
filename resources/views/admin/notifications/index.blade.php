@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<x-page-header title="Notifications" subtitle="Click to read · right-click or long-press for more.">
    <x-slot name="actions">
        <form method="POST" action="{{ route('admin.notifications.readAll') }}">
            @csrf
            <button class="flex items-center gap-2 h-9 px-3.5 rounded-lg border border-[#E4E6EB] bg-white text-[12.5px] font-medium text-[#4A4F58] hover:bg-[#FBFBFC]">
                <x-icon name="check" class="w-4 h-4" sw="2" /> Mark all read
            </button>
        </form>
    </x-slot>
</x-page-header>

<div class="max-w-3xl bg-white border border-[#E9EBEF] rounded-[14px] shadow-[0_1px_2px_rgba(16,20,28,.03)] overflow-hidden">
    @forelse ($notifications as $n)
        <div x-data="notifRow({{ $n->read_at ? 'true' : 'false' }}, '{{ route('admin.notifications.read', $n) }}', '{{ route('admin.notifications.unread', $n) }}', '{{ route('admin.notifications.destroy', $n) }}')"
             x-show="!gone" @contextmenu.prevent="menu=true" @click.outside="menu=false"
             class="relative border-b border-[#F4F5F7] last:border-0">
            <div @click="toggle()" @touchstart="press()" @touchend="release()" @touchmove="release()"
                 class="flex gap-3.5 px-5 py-4 cursor-pointer select-none" :class="read ? '' : 'bg-[#FBFAFF]'">
                @include('partials.notification-icon', ['type' => $n->type, 'size' => 36])
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[13.5px]" :class="read ? 'font-medium text-[#565b64]' : 'font-semibold text-[#1A1D23]'">{{ $n->title }}</span>
                        <span x-show="!read" class="w-2 h-2 rounded-full bg-brand flex-none"></span>
                    </div>
                    <div class="text-[12.5px] text-[#787E88] mt-0.5" :class="expanded ? '' : 'truncate'">{{ $n->body }}</div>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-[11px] text-[#A6ABB4]">{{ $n->created_at->diffForHumans() }}</span>
                        @if ($n->link)
                            <a href="{{ $n->link }}" @click.stop class="text-[11px] text-brand font-medium">Open</a>
                        @endif
                    </div>
                </div>
                <button @click.stop="menu = !menu" class="text-[#C3C7CE] hover:text-[#787E88] flex-none"><x-icon name="dots" class="w-4 h-4" /></button>
            </div>
            <div x-show="menu" x-cloak class="absolute right-5 top-12 z-20 w-44 bg-white border border-[#E9EBEF] rounded-lg shadow-[0_4px_20px_rgba(16,20,28,.08)] py-1">
                <button @click="read ? unread() : markRead(); menu=false" class="w-full text-left px-3 py-2 text-[13px] hover:bg-[#F7F8FA]" x-text="read ? 'Mark as unread' : 'Mark as read'"></button>
                <button @click="remove()" class="w-full text-left px-3 py-2 text-[13px] text-[#B23A30] hover:bg-[#FDF6F5]">Delete</button>
            </div>
        </div>
    @empty
        <div class="px-5 py-12 text-center text-[13px] text-[#9AA0AA]">No notifications yet.</div>
    @endforelse
</div>

<div class="mt-4 max-w-3xl">{{ $notifications->links() }}</div>

<script>
    function notifRow(read, readUrl, unreadUrl, delUrl) {
        return {
            read: read, expanded: false, menu: false, gone: false, timer: null, longFired: false,
            csrf() { return document.querySelector('meta[name=csrf-token]').content; },
            toggle() {
                if (this.longFired) { this.longFired = false; return; }
                this.expanded = !this.expanded;
                if (this.expanded) this.markRead();
            },
            async markRead() {
                if (this.read) return;
                this.read = true;
                await fetch(readUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf() } });
            },
            async unread() {
                this.read = false;
                await fetch(unreadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf() } });
            },
            async remove() {
                this.gone = true; this.menu = false;
                await fetch(delUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrf() } });
            },
            press() { this.timer = setTimeout(() => { this.menu = true; this.longFired = true; if (navigator.vibrate) navigator.vibrate(15); }, 550); },
            release() { clearTimeout(this.timer); },
        }
    }
</script>
@endsection
