@use(\App\Livewire\Components\Blog\Posts\FilterSearch)

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Posts</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">All blog posts</h1>
        </div>

        <a href="{{ route('admin.blog.posts.create') }}"
            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">
            Create post
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-sm text-slate-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- filter / search section --}}
    @livewire(FilterSearch::class)

    {{-- lister --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">

            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        <button type="button" wire:click="sortBy('id')"
                            class="cursor-pointer inline-flex items-center gap-2 text-slate-500 transition hover:text-slate-900">
                            ID
                            @if ($orderBy === 'id')
                                <span aria-hidden="true">{{ $orderDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        <button type="button" wire:click="sortBy('title')"
                            class="cursor-pointer inline-flex items-center gap-2 text-slate-500 transition hover:text-slate-900">
                            Title
                            @if ($orderBy === 'title')
                                <span aria-hidden="true">{{ $orderDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold  tracking-[0.24em] text-slate-500">

                        Author
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        <button type="button" wire:click="sortBy('created_at')"
                            class="cursor-pointer inline-flex items-center gap-2 text-slate-500 transition hover:text-slate-900">
                            Created At
                            @if ($orderBy === 'created_at')
                                <span aria-hidden="true">{{ $orderDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        <button type="button" wire:click="sortBy('published_on')"
                            class="cursor-pointer inline-flex items-center gap-2 text-slate-500 transition hover:text-slate-900">
                            Published On
                            @if ($orderBy === 'published_on')
                                <span aria-hidden="true">{{ $orderDirection === 'asc' ? '▲' : '▼' }}</span>
                            @endif
                        </button>
                    </th>

                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                        Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200">
                @forelse ($posts as $post)
                    <tr wire:key="post-{{ $post->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $post->id }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">{{ $post->title }}
                            
                            @if ($post->comments_count > 0)
                                <span class="ml-2 text-xs text-blue-400">({{ $post->comments_count }})</span>
                            @endif

                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $post->user->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $post->created_at }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $post->published_on }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('admin.blog.posts.edit', $post) }}"
                                class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">Edit</a>


                            <button type="button" wire:click="delete({{ $post->id }})"
                                wire:confirm="Are you sure you want to delete this post?" wire:loading.attr="disabled"
                                class="ml-2 cursor-pointer rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">
                            No posts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($posts->hasPages())
            <div class="mt-4 p-6">
                {{ $posts->links() }}
            </div>
        @endif

    </div>
</div>
