import { ref, type Ref, onMounted, nextTick } from 'vue';

export interface UseSlideDownOptions {
    /** Number of visible lines before truncation. Defaults to 8. */
    lines?: number;
}

export function useSlideDown(
    elementRef: Ref<HTMLElement | null>,
    { lines = 10 }: UseSlideDownOptions = {},
) {
    const showFull = ref(false);
    const isTruncated = ref(false);
    const hasMeasured = ref(false);
    const clampedHeight = ref(0);
    const contentHeight = ref(0);

    const measure = (): void => {
        const el = elementRef.value;
        if (!el) {
            return;
        }

        const lineHeight = parseFloat(getComputedStyle(el).lineHeight);
        const fullHeight = el.scrollHeight;
        const clamped = lineHeight * lines;

        isTruncated.value = fullHeight > clamped;
        clampedHeight.value = clamped;
        contentHeight.value = fullHeight;

        // Defer to next frame so the transition class is not applied
        // in the same paint as the max-height style
        requestAnimationFrame(() => {
            hasMeasured.value = true;
        });
    };

    const expand = (): void => {
        showFull.value = true;
    };

    const collapse = (): void => {
        showFull.value = false;
    };

    const toggle = (): void => {
        showFull.value = !showFull.value;
    };

    const reset = (): void => {
        showFull.value = false;
        hasMeasured.value = false;
    };

    const recheck = async (): Promise<void> => {
        reset();
        await nextTick();
        measure();
    };

    onMounted(() => {
        measure();
    });

    return {
        /** Whether the content exceeds the line limit. */
        isTruncated,
        /** Whether the full content is currently shown. */
        showFull,
        /** Whether the initial measurement has completed. */
        hasMeasured,
        /** The clamped height in pixels (lines × line-height). */
        clampedHeight,
        /** The full scroll height of the content. */
        contentHeight,
        /** Expand to show full content. */
        expand,
        /** Collapse back to truncated state. */
        collapse,
        /** Toggle between expanded and collapsed. */
        toggle,
        /** Re-measure the element (call when content changes). */
        recheck,
    };
}

// example

// const displayComment = computed(() => {
//     if (props.maxCommentLength && props.maxCommentLength > 0) {
//         if (props.comment.comment.length <= props.maxCommentLength) {
//             return props.comment.comment;
//         }

//         return `${props.comment.comment.substring(0, props.maxCommentLength)}…`;
//     }

//     return props.comment.comment;
// });

// const contentEl = ref<HTMLElement | null>(null);
// const {
//     isTruncated,
//     showFull,
//     hasMeasured,
//     clampedHeight,
//     contentHeight,
//     expand,
//     recheck,
// } = useSlideDown(contentEl, { lines: 8 });

// <div v-if="!editing" class="space-y-2">
//     <div
//         ref="contentEl"
//         class="text-sm whitespace-pre-wrap text-[#1b1b18] dark:text-[#EDEDEC] overflow-hidden"
//         :class="{ 'transition-all duration-300 ease-in-out': hasMeasured }"
//         :style="contentHeight ? { maxHeight: isTruncated && !showFull ? clampedHeight + 'px' : contentHeight + 'px' } : undefined"
//     >
//         {{ displayComment }}
//     </div>
//     <button
//         v-if="isTruncated && !showFull"
//         class="cursor-pointer text-xs font-medium text-[#f53003] hover:underline"
//         @click="expand"
//     >
//         Read more
//     </button>
// </div>
