<div {{ $attributes->merge(['class'=>"bg-$type-200 px-2 py-2"]) }}>
<p><b>{{ $title }}</b></p>
<p>{{ $slot }}</p>
<p>{{ $prueba() }}</p>
</div>