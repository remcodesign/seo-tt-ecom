@use(\App\Livewire\Components\Blog\Posts\CommentLister)

<div class="mx-auto max-w-4xl rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <div class="space-y-3">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">
            {{ $form->post?->exists ? 'Edit post' : 'Create post' }}</p>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
            {{ $form->post?->exists ? 'Update post details' : 'Create a new post' }}</h1>
    </div>

    <form wire:submit="save" class="mt-8 space-y-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input id="title" wire:model="form.title" type="text" required
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('form.title')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="writer_id" class="block text-sm font-medium text-slate-700">Writer</label>
                <select id="writer_id" wire:model="form.user_id" required
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200">
                    <option value="">Select a writer</option>
                    @foreach ($writers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('form.user_id')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-1">
            <div>
                <label for="body" class="block text-sm font-medium text-slate-700">Body</label>
                <textarea rows="4" id="body" wire:model="form.body"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200"></textarea>

                @error('form.body')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="published_on" class="block text-sm font-medium text-slate-700">Published On</label>
                <input id="published_on" wire:model="form.published_on" type="date"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200" />
                @error('form.published_on')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.blog.posts.index') }}"
                class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-900 transition hover:border-slate-300 hover:bg-slate-100">Cancel</a>

            <button type="submit" wire:loading.attr="disabled"
                class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50">
                {{ $form->post?->exists ? 'Save changes' : 'Create post' }}
            </button>
        </div>
    </form>

    {{-- comment lister section --}}
    @livewire(CommentLister::class, ['post' => $form->post])
</div>
