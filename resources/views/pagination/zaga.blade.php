@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center flex-wrap gap-1.5">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="w-9 h-9 rounded-lg border border-[#E9EBEF] bg-white flex items-center justify-center text-[#C3C7CE]">
                <x-icon name="chevron-left" class="w-4 h-4" sw="2" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"
               class="w-9 h-9 rounded-lg border border-[#E9EBEF] bg-white flex items-center justify-center text-[#4A4F58] hover:bg-[#FBFAFF] hover:border-[#D8DBE0] transition">
                <x-icon name="chevron-left" class="w-4 h-4" sw="2" />
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="w-9 h-9 flex items-center justify-center text-[13px] text-[#9AA0AA]">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="min-w-9 h-9 px-2 rounded-lg bg-brand text-white text-[13px] font-semibold flex items-center justify-center shadow-[0_1px_3px_rgba(75,69,199,.35)]">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="min-w-9 h-9 px-2 rounded-lg border border-[#E9EBEF] bg-white text-[13px] font-medium text-[#4A4F58] flex items-center justify-center hover:bg-[#FBFAFF] hover:border-[#D8DBE0] transition tnum">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"
               class="w-9 h-9 rounded-lg border border-[#E9EBEF] bg-white flex items-center justify-center text-[#4A4F58] hover:bg-[#FBFAFF] hover:border-[#D8DBE0] transition">
                <x-icon name="chevron-right" class="w-4 h-4" sw="2" />
            </a>
        @else
            <span aria-disabled="true" class="w-9 h-9 rounded-lg border border-[#E9EBEF] bg-white flex items-center justify-center text-[#C3C7CE]">
                <x-icon name="chevron-right" class="w-4 h-4" sw="2" />
            </span>
        @endif
    </nav>
@endif
