@props(['title' => null, 'header' => 'Painel', 'fullWidth' => false])

<!DOCTYPE html>
<html lang="pt-BR" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('chatflow.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet" />
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased" data-authenticated-user="{{ auth()->id() }}">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-[1px] lg:hidden"></div>

    {{-- Sidebar --}}
    @include('components.layout.sidebar')

    <div class="flex min-h-screen flex-col lg:pl-56">
        @include('components.layout.navbar')

        <main class="flex-1 {{ $fullWidth ? 'p-0 pb-16 lg:pb-0' : 'p-4 pb-20 sm:p-5 lg:p-6 lg:pb-6' }}">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile bottom nav --}}
    @include('components.layout.mobile-nav')

</body>
</html>
