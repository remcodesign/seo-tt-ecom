declare namespace App {
    namespace Data {
        namespace Auth {
            export type CreateTokenData = {
                email: string;
                password: string;
                device_name: string;
            };
            export type RegisterData = {
                name: string;
                email: string;
                password: string;
            };
            export type RevokeTokenData = {
                message: string;
            };
            export type TokenData = {
                token: string;
            };
            export type UserData = {
                id: number;
                name: string;
                email: string;
            };
        }
        namespace Blog {
            namespace Requests {
                export type StoreCommentData = {
                    post_id: number;
                    comment: string;
                };
                export type StorePostData = {
                    title: string;
                    body: string | null;
                    published_on: string | null;
                };
                export type UpdateCommentData = {
                    comment: string | null;
                };
                export type UpdatePostData = {
                    title: string | null;
                    body: string | null;
                    published_on: string | null;
                };
            }
            namespace Responses {
                export type CommentData = {
                    id: number;
                    post_id: number;
                    user_id: number;
                    comment: string;
                    post: App.Data.Blog.Responses.PostData | null;
                    user: App.Data.Auth.UserData | null;
                };
                export type PostData = {
                    id: number;
                    user_id: number;
                    title: string;
                    body: string | null;
                    slug: string;
                    published_on: string | null;
                    user: App.Data.Auth.UserData | null;
                };
            }
        }
    }
}
