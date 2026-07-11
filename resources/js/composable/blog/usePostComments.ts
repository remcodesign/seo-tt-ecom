import type { Ref } from 'vue';
import type { CommentDataModifiedResponse, PostDataResponse, StoreCommentData, UpdateCommentData } from '@types';
import api from '@/api';

export function usePostComments(
    post: Ref<PostDataResponse | null>,
    refetchPost: () => Promise<void>,
) {
    const createComment = async (comment: string): Promise<void> => {
        if (!post.value) {
            throw new Error('Post not loaded.');
        }

        const payload: StoreCommentData = {
            post_id: post.value.id,
            comment,
        };

        await api.post('/blog/comments', payload);
        await refetchPost();
    };

    const updateComment = async (payload: { id: number; comment: string }): Promise<void> => {
        if (!post.value) {
            throw new Error('Post not loaded.');
        }

        const requestData: UpdateCommentData = {
            comment: payload.comment,
        };

        const response = await api.put<CommentDataModifiedResponse>(`/blog/comments/${payload.id}`, requestData);
        const updated = response.data;

        if (!post.value.comments) {
            return;
        }

        const existingComment = post.value.comments.find((item) => item.id === updated.id);

        if (existingComment) {
            existingComment.comment = updated.comment;
            existingComment.updated_at = updated.updated_at;
        }
    };

    const deleteComment = async (commentId: number): Promise<void> => {
        if (!post.value) {
            throw new Error('Post not loaded.');
        }

        await api.delete(`/blog/comments/${commentId}`);

        if (!post.value.comments) {
            return;
        }

        post.value = {
            ...post.value,
            comments: post.value.comments.filter((comment) => comment.id !== commentId),
            comments_count: Math.max((post.value.comments_count ?? 1) - 1, 0),
        };
    };

    return {
        createComment,
        updateComment,
        deleteComment,
    };
}
