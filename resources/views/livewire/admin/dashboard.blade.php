<div class="space-y-8">
    <section class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Admin dashboard</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Admin dashboard</h1>
            </div>

            <div class="grid gap-3 sm:grid-cols-4">
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Users</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $userCount }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Posts</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $postCount }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Orders</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900 italic">14</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-center">
                    <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Revenue</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900 italic">4.542,-</p>
                </div>
            </div>
        </div>

        <h3 class="mt-8 font-bold">General</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a href="{{ route('admin.users.index') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Users ({{ $userCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage registered accounts.</p>
            </a>
        </div>

        <h3 class="mt-8 font-bold">Blog</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Categories ()</p>
                <p class="mt-2 text-sm text-slate-600">Manage content categories.</p>
            </a>
            <a href="{{ route('admin.blog.posts.index') }}"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Posts ({{ $postCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage and publish content.</p>
            </a>
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Comments ({{ $commentCount }})</p>
                <p class="mt-2 text-sm text-slate-600">Manage user comments.</p>
            </a>
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Tags ()</p>
                <p class="mt-2 text-sm text-slate-600">Manage content tags.</p>
            </a>
        </div>

        <h3 class="mt-8 font-bold">Webshop</h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-3 md:grid-cols-4">
        
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Categories</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop categories.</p>
            </a>
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Products</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop products.</p>
            </a>
            <a href="#"
                class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-5 text-center transition hover:border-slate-300 hover:bg-slate-100">
                <p class="text-sm font-semibold text-slate-900">Orders</p>
                <p class="mt-2 text-sm text-slate-600">Manage webshop orders.</p>
            </a>
        </div>
    </section>
</div>
