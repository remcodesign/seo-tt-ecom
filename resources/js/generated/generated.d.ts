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
    }
}
