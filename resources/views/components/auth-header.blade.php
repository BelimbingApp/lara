@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-3xl font-bold">{{ $title }}</h1>
    <p class="text-muted mt-2">{{ $description }}</p>
</div>
