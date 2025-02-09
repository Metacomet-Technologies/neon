import { Config } from 'ziggy-js';

export interface User {
    avatar: string;
    created_at: string;
    current_server_id?: string | null;
    discord_id: string;
    email: string;
    email_verified_at?: string;
    guilds: Guild[];
    id: number;
    is_admin: boolean;
    is_on_mailing_list: boolean;
    name: string;
    updated_at: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    flash?: {
        type: FlashType;
        message: string;
    };
    ziggy: Config & { location: string };
    appName: string;
    theme?: 'light' | 'dark';
};

export interface PaginationLink {
    url?: string;
    label: string;
    active: boolean;
}

export interface Pagination<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: any;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: any;
    path: string;
    per_page: number;
    prev_page_url: any;
    to: any;
    total: number;
}

export type FlashType = 'success' | 'error' | 'info' | 'warning';

export interface Guild {
    id: string;
    name: string;
    icon: string;
    banner?: string;
    owner: boolean;
    permissions: string;
    features: string[];
}
