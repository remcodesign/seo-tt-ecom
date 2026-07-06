// Re-export ambient types from the TypeScript transformer as proper module types.
// These are consumed by components via `import type { PostData } from '@/types'`.

export type PostData = App.Data.Blog.Responses.PostData;
export type PostForCommentData = App.Data.Blog.Responses.PostForCommentData;
export type CommentData = App.Data.Blog.Responses.CommentData;

export type UserData = App.Data.Auth.UserData;
export type RoleLabel = App.Enums.RoleLabel;

export type StorePostData = App.Data.Blog.Requests.StorePostData;
export type UpdatePostData = App.Data.Blog.Requests.UpdatePostData;
export type StoreCommentData = App.Data.Blog.Requests.StoreCommentData;
export type UpdateCommentData = App.Data.Blog.Requests.UpdateCommentData;

export type CreateTokenData = App.Data.Auth.CreateTokenData;
export type RegisterData = App.Data.Auth.RegisterData;
export type TokenData = App.Data.Auth.TokenData;