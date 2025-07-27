import { Config } from 'ziggy-js';
import { User } from './models';

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
        warning?: string | null;
    };
    ziggy: Config & { location: string };
    appName: string;
    theme?: 'light' | 'dark' | 'system';
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

// Re-export model interfaces
export { Guild, User, WelcomeSetting } from './models';
