
@if (View::pagination())
<ul class="pagination m-0">
    <li class="{{ View::pagination()->first? '' : 'disabled' }}">
        @if (View::pagination()->first)
        <a href="?{{ View::pagination()->first }}">Primera</a>
        @else
        <a>Primera</a>
        @endif
    </li>
    <li class="{{ View::pagination()->second? '' : 'disabled' }}">
    @if (View::pagination()->second)
        <a href="?{{ View::pagination()->second }}">Anterior</a>
        @else
        <a>Anterior</a>
        @endif
    </li>
    <li class="{{ View::pagination()->third? '' : 'disabled' }}">
    @if (View::pagination()->third)
        <a href="?{{ View::pagination()->third }}">Siguiente</a>
        @else
        <a>Siguiente</a>
        @endif
    </li>
    <li class="{{ View::pagination()->fourth? '' : 'disabled' }}">
    @if (View::pagination()->fourth)
        <a href="?{{ View::pagination()->fourth }}">Ultima</a>
        @else
        <a>Ultima</a>
        @endif
    </li>
</ul>
@endif