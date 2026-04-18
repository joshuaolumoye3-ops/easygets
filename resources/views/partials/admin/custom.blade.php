@if ($paginator->hasPages())
<nav>
    <ul class="laravel-pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li><span class="disabled">‹</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a></li>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li><span class="dots">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><span aria-current="page">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">›</a></li>
        @else
            <li><span class="disabled">›</span></li>
        @endif
    </ul>
</nav>
@endif