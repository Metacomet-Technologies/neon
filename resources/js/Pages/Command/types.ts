import { User } from '@/types';
import { FormDataConvertible } from '@inertiajs/core';

export interface Command {
    id: number;
    command: string;
    description: string;
    response: string;
    guild_id: string | null;
    is_enabled: boolean;
    is_public: boolean;
    is_embed: boolean;
    embed_color?: number | null;
    embed_title?: string | null;
    embed_description?: string | null;
    created_by: number | null;
    updated_by: number | null;
    created_by_user?: User | null;
    updated_by_user?: User | null;
    deleted_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface CommandStore {
    command: string;
    description: string;
    response: string;
    is_enabled: boolean;
    is_public: boolean;
    is_embed: boolean;
    embed_color?: number | null;
    embed_title?: string | null;
    embed_description?: string | null;
    [key: string]: FormDataConvertible;
}
