@extends('admin_blade.layouts.app')

@section('content')
    <div class="space-y-8">
        <section class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Admin dashboard</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Admin dashboard</h1>
                </div>

                {{-- Main entity counts --}}
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl bg-slate-50 p-4 text-center">
                        <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Users</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $userCount }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 text-center">
                        <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Posts</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $postCount }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 text-center">
                        <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Comments</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $commentCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Buttons for main entities CRUD actions --}}
            <div class="mt-10 grid gap-4 sm:grid-cols-3">
                <a href="{{ route('blade.admin.users.index') }}" class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                    <p class="text-sm font-semibold text-slate-900">Users</p>
                    <p class="mt-2 text-sm text-slate-600">Manage registered accounts.</p>
                </a>
                <a href="#" class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                    <p class="text-sm font-semibold text-slate-900">Posts</p>
                    <p class="mt-2 text-sm text-slate-600">Manage and publish content.</p>
                </a>
                <a href="#" class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                    <p class="text-sm font-semibold text-slate-900">Comments</p>
                    <p class="mt-2 text-sm text-slate-600">Moderate conversation threads.</p>
                </a>
            </div>
        </section>
    </div>
@endsection
