<div class="space-y-8">
    <section class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Admin dashboard</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Admin dashboard</h1>
            </div>

            <div class="grid gap-3 sm:grid-cols-4">
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm tracking-[0.24em] text-slate-500 uppercase">Users</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $userCount }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm tracking-[0.24em] text-slate-500 uppercase">Posts</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $postCount }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm tracking-[0.24em] text-slate-500 uppercase">Orders</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900 italic">14</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm tracking-[0.24em] text-slate-500 uppercase">Revenue</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900 italic">4.542,-</p>
                </div>
            </div>
        </div>

        <h3 class="mt-8 font-bold">General</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a
                href="{{ route('admin.users.index') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center shadow transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Users ({{ $userCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage registered accounts.</p>
            </a>
        </div>

        <h3 class="mt-8 font-bold">Blog</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Categories ({{ $postCategoryCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage content categories.</p>
            </a>
            <a
                href="{{ route('admin.blog.posts.index') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center shadow transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Posts ({{ $postCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage and publish content.</p>
            </a>
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Comments ({{ $commentCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage user comments.</p>
            </a>
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Tags ()</p>
                <p class="mt-2 text-sm text-slate-600">Manage content tags.</p>
            </a>
        </div>

        <h3 class="mt-8 font-bold">HubSpot test tools</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a
                href="{{ route('admin.hubspot.console') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center shadow transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Smart Quote console</p>
                <p class="mt-2 text-sm text-slate-600">Enter test data and run customer or pitch checks.</p>
            </a>
            <a
                href="{{ route('admin.hubspot.logs') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center shadow transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">HubSpot and AI logs</p>
                <p class="mt-2 text-sm text-slate-600">Inspect recent integration and model activity.</p>
            </a>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center">
                <p class="text-sm font-semibold text-slate-900">AI pitch service</p>
                <div class="mt-2 flex items-center justify-center gap-2 text-sm font-semibold {{ $openRouterConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                    <span
                        class="h-2.5 w-2.5 rounded-full {{ $openRouterConfigured ? 'bg-emerald-500' : 'bg-amber-500' }}"
                        aria-hidden="true"
                    ></span>
                    <span>{{ $openRouterConfigured ? 'Ready' : 'Fallback active' }}</span>
                </div>
            </div>
        </div>

        <h3 class="mt-8 font-bold">Webshop</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Categories</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop categories.</p>
            </a>
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Products</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop products.</p>
            </a>
            <a
                href="#"
                class="cursor-not-allowed rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center opacity-50 transition hover:border-slate-300 hover:bg-slate-100"
            >
                <p class="text-sm font-semibold text-slate-900">Orders</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop orders.</p>
            </a>
        </div>
    </section>
</div>
