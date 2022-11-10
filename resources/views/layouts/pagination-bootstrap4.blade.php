
@if ($paginator)
<ul class="pagination m-0">
    <li class="{{ $paginator->first? '' : 'disabled' }}">
        @if ($paginator->first)
        <a href="?{{ $paginator->first }}">Primera</a>
        @else
        <a>Primera</a>
        @endif
    </li>
    <li class="{{ $paginator->second? '' : 'disabled' }}">
    @if ($paginator->second)
        <a href="?{{ $paginator->second }}">Anterior</a>
        @else
        <a>Anterior</a>
        @endif
    </li>
    <li class="{{ $paginator->third? '' : 'disabled' }}">
    @if ($paginator->third)
        <a href="?{{ $paginator->third }}">Siguiente</a>
        @else
        <a>Siguiente</a>
        @endif
    </li>
    <li class="{{ $paginator->fourth? '' : 'disabled' }}">
    @if ($paginator->fourth)
        <a href="?{{ $paginator->fourth }}">Ultima</a>
        @else
        <a>Ultima</a>
        @endif
    </li>
</ul>
@endif