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
            export type PostData = {
                id: number;
                user_id: number;
                title: string;
                body: string | null;
                slug: string;
                published_on: string | null;
            };
            export type StorePostData = {
                title: string;
                body: string | null;
                published_on: string | null;
            };
            export type UpdatePostData = {
                title: string | null;
                body: string | null;
                published_on: string | null;
            };
        }
    }
}
