<section class="mt-20">
    <h2 class="text-lg font-semibold text-slate-900">Comments</h2>
    <div class="mt-4 space-y-4">
        @forelse($comments as $comment)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                <p class="mt-2 text-xs text-slate-500">
                    By {{ $comment->user->name }} on
                    {{ $comment->created_at->format('M d, Y') }} :: #{{ $comment->id }}
                </p>

                <p class="mt-1 text-sm text-slate-800">{{ $comment->comment }}</p>

                <div class="text-right">
                    <button type="button" wire:click="deleteComment({{ $comment->id }})"
                        wire:confirm="Are you sure you want to delete this comment?" wire:loading.attr="disabled"
                        class="mt-3 cursor-pointer  text-rose-700 transition  hover:text-rose-300">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No comments yet.</p>
        @endforelse
    </div>

    @if ($comments->hasPages())
        <div class="mt-4 pt-6">
            {{ $comments->links() }}
        </div>
    @endif
</section>
