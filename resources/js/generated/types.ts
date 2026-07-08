export type CommentDataModifiedResponse = {
    id: number;
    post_id: number;
    user_id: number;
    comment: string;
    created_at: string | null;
    updated_at: string | null;
};
export type CommentDataResponse = {
    id: number;
    post_id: number;
    user_id: number;
    comment: string;
    user: UserDataResponse;
    post: PostForCommentDataResponse | null;
    created_at: string | null;
    updated_at: string | null;
};
export type CreateTokenData = {
    email: string;
    password: string;
    device_name: string;
};
export type PostDataModifiedResponse = {
    id: number;
    user_id: number;
    title: string;
    slug: string;
    body: string | null;
    published_on: string | null;
    created_at: string | null;
    updated_at: string | null;
};
export type PostDataResponse = {
    id: number;
    user_id: number;
    title: string;
    slug: string;
    user: UserDataResponse;
    body: string | null;
    comments: CommentDataResponse[] | null;
    published_on: string | null;
    created_at: string | null;
    updated_at: string | null;
};
export type PostForCommentDataResponse = {
    id: number;
    user_id: number;
    title: string;
    slug: string;
    user: UserDataResponse;
    published_on: string | null;
    created_at: string | null;
    updated_at: string | null;
};
export type RegisterData = {
    name: string;
    email: string;
    password: string;
};
export type RevokeTokenDataResponse = {
    message: string;
};
export type RoleLabel = "guest" | "user" | "writer" | "admin";
export type StoreCommentData = {
    post_id: number;
    comment: string;
};
export type StorePostData = {
    title: string;
    body: string | null;
    published_on: string | null;
};
export type TokenDataResponse = {
    token: string;
};
export type UpdateCommentData = {
    comment: string | null;
};
export type UpdatePostData = {
    title: string | null;
    body: string | null;
    published_on: string | null;
};
export type UserDataResponse = {
    id: number;
    name: string;
    role_label: RoleLabel;
};
