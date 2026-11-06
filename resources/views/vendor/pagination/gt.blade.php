@if ($paginator->hasPages())
  <nav class="pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    @if ($paginator->onFirstPage())
      <span class="disabled">&larr; Sebelumnya</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Sebelumnya</a>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="dots">{{ $element }}</span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="current" aria-current="page">{{ $page }}</span>
          @else
            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rarr;</a>
    @else
      <span class="disabled">Berikutnya &rarr;</span>
    @endif
  </nav>
@endif
