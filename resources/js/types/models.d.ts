// Auto-generated TypeScript interfaces from Laravel models
// Generated at: 2025-07-27T03:29:31+00:00

export interface CommandUsageMetric {
    id?: number;
    command_type?: string;
    command_slug?: string;
    command_hash?: string;
    guild_id?: string;
    user_hash?: string;
    channel_type?: string;
    parameter_count?: number;
    had_errors?: boolean;
    execution_duration_ms?: string;
    executed_at?: string;
    date?: string;
    hour?: number;
    day_of_week?: number;
    status?: string;
    error_category?: string;
    created_at?: string;
    updated_at?: string;
}

export interface Guild {
    id?: string;
    name?: string;
    icon?: string;
    is_bot_member?: boolean;
    bot_joined_at?: string;
    bot_left_at?: string;
    last_bot_check_at?: string;
    created_at?: string;
    updated_at?: string;
    licenses?: License[];
    activeLicenses?: any[];
    neonCommands?: NeonCommand[];
    welcomeSettings?: WelcomeSetting[];
}

export interface License {
    id?: number;
    user_id?: number;
    type?: string;
    stripe_id?: string;
    status?: string;
    assigned_guild_id?: string;
    last_assigned_at?: string;
    created_at?: string;
    updated_at?: string;
    user?: User;
    guild?: Guild;
}

export interface NativeCommand {
    id?: number;
    slug?: string;
    description?: string;
    class?: string;
    usage?: string;
    example?: string;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
    parameters?: NativeCommandParameter[];
}

export interface NativeCommandParameter {
    id?: number;
    native_command_id?: number;
    name?: string;
    description?: string;
    is_required?: number;
    order?: number;
    data_type?: string;
    created_at?: string;
    updated_at?: string;
    command?: NativeCommand;
}

export interface NeonCommand {
    id?: number;
    command?: string;
    description?: string;
    response?: string;
    guild_id?: string;
    is_enabled?: boolean;
    is_public?: boolean;
    is_embed?: boolean;
    embed_title?: string;
    embed_description?: string;
    embed_color?: number;
    created_by?: number;
    updated_by?: number;
    created_at?: string;
    updated_at?: string;
    createdByUser?: User;
    updatedByUser?: User;
    guild?: Guild;
}

export interface TwitchEvent {
    id?: number;
    event_id?: string;
    event_timestamp?: string;
    event_type?: string;
    is_processed?: boolean;
    processed_at?: string;
    errored_at?: string;
    created_at?: string;
    updated_at?: string;
}

export interface User {
    id?: number;
    name?: string;
    email?: string;
    email_verified_at?: string;
    password?: string;
    two_factor_secret?: string;
    two_factor_recovery_codes?: string;
    two_factor_confirmed_at?: string;
    remember_token?: string;
    created_at?: string;
    updated_at?: string;
    avatar?: string;
    discord_id?: string;
    access_token?: string;
    refresh_token?: string;
    refresh_token_expires_at?: string;
    token_expires_at?: string;
    is_admin?: boolean;
    is_on_mailing_list?: boolean;
    current_server_id?: string;
    stripe_id?: string;
    pm_type?: string;
    pm_last_four?: string;
    trial_ends_at?: string;
    guilds?: Guild[];
}

export interface UserIntegration {
    id?: number;
    user_id?: number;
    provider?: string;
    provider_id?: string;
    created_at?: string;
    updated_at?: string;
}

export interface UserSetting {
    id?: number;
    user_id?: string;
    theme?: string;
    created_at?: string;
    updated_at?: string;
    preferences?: Record<string, any>;
    user?: User;
}

export interface WelcomeSetting {
    id?: number;
    guild_id?: string;
    channel_id?: string;
    message?: string;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
    guild?: Guild;
}