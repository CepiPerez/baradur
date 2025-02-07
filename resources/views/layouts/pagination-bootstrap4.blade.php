
@if ($paginator)
<ul class="pagination m-0">
    <li class="{{ $paginator->first? '' : 'disabled' }}">
        @if ($paginator->first)
        <a href="?{{ $paginator->first }}">Primera</a>
        @else
        <a>Primera</a>
        @endif
    </li>
    <li class="{{ $paginator->previous? '' : 'disabled' }}">
    @if ($paginator->previous)
        <a href="?{{ $paginator->previous }}">Anterior</a>
        @else
        <a>Anterior</a>
        @endif
    </li>
    <li class="{{ $paginator->next? '' : 'disabled' }}">
    @if ($paginator->next)
        <a href="?{{ $paginator->next }}">Siguiente</a>
        @else
        <a>Siguiente</a>
        @endif
    </li>
    <li class="{{ $paginator->last? '' : 'disabled' }}">
    @if ($paginator->last)
        <a href="?{{ $paginator->last }}">Ultima</a>
        @else
        <a>Ultima</a>
        @endif
    </li>
</ul>
@endif