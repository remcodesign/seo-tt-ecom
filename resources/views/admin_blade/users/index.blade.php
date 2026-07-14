@extends('admin_blade.layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Users</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Registered accounts</h1>
            </div>

            <a href="{{ route('blade.admin.users.create') }}"
                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">
                Create user
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-sm text-slate-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Email</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Role</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $user->id }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">{{ $user->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $user->email }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ ucfirst($user->role_label->value) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <a href="{{ route('blade.admin.users.edit', $user) }}"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">Edit</a>

                                <form action="{{ route('blade.admin.users.destroy', $user) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to delete this user?');"
                                        class="cursor-pointer ml-2 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
