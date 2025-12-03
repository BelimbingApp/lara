@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ $description }}</p>
</div>
