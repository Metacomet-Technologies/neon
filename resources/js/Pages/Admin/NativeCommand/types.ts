export interface NativeCommand {
    id: number;
    slug: string;
    description?: string | null;
    class: string;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface NativeCommandStore {
    slug: string;
    description?: string | null;
    class: string;
    is_active: boolean;
    [key: string]: any;
}
