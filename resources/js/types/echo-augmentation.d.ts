declare module '@laravel/echo-react' {
    interface Events {
        '.reverb.completed': { type: string; status: string; message: [] | null | string | null };
    }
}

export {};
