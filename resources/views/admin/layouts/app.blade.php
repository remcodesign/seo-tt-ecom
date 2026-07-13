<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'Laravel') . ' Admin' }}</title>

    @vite('resources/js/app.ts')
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div>
                    <a href="{{ route('blade.admin.dashboard') }}"
                        class="text-lg font-semibold text-slate-900">{{ config('app.name', 'Laravel') }} Admin</a>
                    <p class="text-sm text-slate-500">Administration</p>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        {{-- Show logout when authenticated --}}
                        <form method="POST" action="{{ route('blade.admin.logout') }}">
                            @csrf

                            <button type="submit"
                                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>
</body>

</html>
