
@if ($paginator)
<ul class="no_print pagination flex w-fit h-fit rounded-lg border border-slate-300 dark:border-zinc-700 overflow-hidden shadow-md h-9 bg-white dark:bg-zinc-800">

    <div class="py-1">
        @if ($paginator->first)
        <a href="?{{ $paginator->first }}"class="py-1.5 px-4 dark:text-zinc-300 hover:text-sky-600 dark:hover:text-sky-500">
            {{ __('pagination.first') }}
        </a>
        @else
        <span class="py-1.5 px-4 text-gray-500 dark:text-zinc-500">
            {{ __('pagination.first') }}
        </span>
        @endif
    </div>

    <div class="py-1 border-l border-slate-300 dark:border-zinc-700">
        @if ($paginator->previous)
        <a href="?{{ $paginator->previous }}" class="py-1.5 px-4 dark:text-zinc-300 hover:text-sky-600 dark:hover:text-sky-500">
            {{ __('pagination.previous') }}
        </a>
        @else
        <span class="py-1.5 px-4 text-gray-500 dark:text-zinc-500">
            {{ __('pagination.previous') }}
        </span>
        @endif
    </div>

    <div class="py-1 border-l border-r border-slate-300 dark:border-zinc-700">
        @if ($paginator->next)
        <a href="?{{ $paginator->next }}" class="py-1.5 px-4 dark:text-zinc-300 hover:text-sky-600 dark:hover:text-sky-500">
            {{ __('pagination.next') }}
        </a>
        @else
        <span class="py-1.5 px-4 text-gray-500 dark:text-zinc-500">
            {{ __('pagination.next') }}
        </span>
        @endif
    </div>

    <div class="py-1">
        @if ($paginator->last)
        <a href="?{{ $paginator->last }}" class="py-1.5 px-4 dark:text-zinc-300 hover:text-sky-600 dark:hover:text-sky-500">
            {{ __('pagination.last') }}
        </a>
        @else
        <span class="py-1.5 px-4 text-gray-500 dark:text-zinc-500">
            {{ __('pagination.last') }}
        </span>
        @endif
    </div>
        
</ul>
@endif