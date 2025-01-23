import { Config } from 'ziggy-js';

export interface User {
    avatar: string;
    created_at: string;
    discord_id: string;
    email: string;
    email_verified_at?: string;
    id: number;
    name: string;
    updated_at: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
    appName: string;
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
