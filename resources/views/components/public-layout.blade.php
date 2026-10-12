@props(['title' => null])

@include('layouts.public', [
    'title' => $title,
    'head' => $head ?? '',
    'slot' => $slot,
])