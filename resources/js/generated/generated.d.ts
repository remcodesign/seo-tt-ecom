declare namespace App {
    namespace Data {
        namespace Auth {
            export type RegisterData = {
                name: string;
                email: string;
                password: string;
            };
            export type UserData = {
                id: number;
                name: string;
                email: string;
            };
        }
    }
}
