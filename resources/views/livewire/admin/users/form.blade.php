<div class="mx-auto max-w-3xl rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <div class="space-y-3">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $form->user?->exists ? 'Edit user' : 'Create user' }}</p>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $form->user?->exists ? 'Update account details' : 'Create a new user account' }}</h1>
    </div>

    <form wire:submit="save" class="mt-8 space-y-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" wire:model="form.name" type="text" required
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('name')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" wire:model="form.email" type="email" required
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('email')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="role_label" class="block text-sm font-medium text-slate-700">Role</label>
                <select id="role_label" wire:model="form.role_label" required
                        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200">
                    @foreach ($roleLabels as $roleLabel)
                        <option value="{{ $roleLabel->value }}">{{ ucfirst($roleLabel->value) }}</option>
                    @endforeach
                </select>
                @error('role_label')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" wire:model="form.password" type="password" {{ $form->user?->exists ? '' : 'required' }}
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('password')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <input id="password_confirmation" wire:model="form.password_confirmation" type="password" {{ $form->user?->exists ? '' : 'required' }}
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.users.index') }}"
               class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-900 transition hover:border-slate-300 hover:bg-slate-100">Cancel</a>

            <button type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50">
                {{ $form->user?->exists ? 'Save changes' : 'Create user' }}
            </button>
        </div>
    </form>
</div>