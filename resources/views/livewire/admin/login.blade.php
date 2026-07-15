<div class="mx-auto w-full max-w-md rounded-3xl bg-white p-8 shadow-lg ring-1 ring-slate-200">
    <div class="space-y-4 text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.32em] text-slate-500">Admin Login</p>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Sign in to your admin account</h1>
    </div>

    <form wire:submit="authenticate" class="mt-8 space-y-6">
        <div class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" wire:model="email" type="email" required autofocus
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('email')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" wire:model="password" type="password" required
                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('password')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit"
                wire:loading.attr="disabled"
                class="mt-4 w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50">
            Sign in
        </button>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Unable to sign in</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </form>
</div>