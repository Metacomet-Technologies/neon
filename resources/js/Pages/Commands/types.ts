import { User } from '@/types';

export interface Command {
    id: number;
    name: string;
    description: string;
    command: string;
    guild_id: string | null;
    is_enabled: boolean;
    is_public: boolean;
    created_by: number | null;
    updated_by: number | null;
    created_by_user?: User | null;
    updated_by_user?: User | null;
    deleted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface CommandStore {
    name: string;
    description: string;
    command: string;
    guild_id: string | null;
    is_enabled: boolean;
    is_public: boolean;
    [key: string]: any;
}

export interface Guild {
    id: string;
    name: string;
    icon: string;
    banner?: string;
    owner: boolean;
    permissions: string;
    features: string[];
}
