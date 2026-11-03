@if ($paginator->hasPages())
    <nav class="d-flex justify-content-between" aria-label="Navigasi halaman">
        <div class="d-flex justify-content-between flex-fill d-sm-none">
            <ul class="pagination mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a></li>
                @endif
                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a></li>
                @else
                    <li class="page-item disabled"><span class="page-link">Berikutnya</span></li>
                @endif
            </ul>
        </div>

        <div class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between">
            <p class="small text-muted mb-0">
                Menampilkan <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                sampai <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                dari <span class="fw-semibold">{{ $paginator->total() }}</span> hasil
            </p>
            <ul class="pagination mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled"><span class="page-link" aria-label="Sebelumnya">&lsaquo;</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a></li>
                @endif
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a></li>
                @else
                    <li class="page-item disabled"><span class="page-link" aria-label="Berikutnya">&rsaquo;</span></li>
                @endif
            </ul>
        </div>
    </nav>
@endif
