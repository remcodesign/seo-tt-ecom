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
export type PaginatedResponseData<T> = {
    data: T[];
    links: PaginationLinkData[];
    meta: PaginationMetaData;
};
export type PaginationLinkData = {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
};
export type PaginationMetaData = {
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
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
    comments_count: number;
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
    role_label: RoleLabel | null;
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
    user_id: number;
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
    user_id: number;
    title: string;
    body: string | null;
    published_on: string | null;
};
export type UpdateUserData = {
    id: number;
    name: string;
    email: string;
    role_label: RoleLabel;
    password: string | null;
};
export type UserDataResponse = {
    id: number;
    name: string;
    role_label: RoleLabel;
};
