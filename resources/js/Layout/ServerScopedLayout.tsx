import { Avatar } from '@/Components/catalyst/avatar';
import { Dropdown, DropdownButton, DropdownItem, DropdownLabel, DropdownMenu } from '@/Components/catalyst/dropdown';
import { NavbarItem, NavbarLabel } from '@/Components/catalyst/navbar';
import { DiscordGuild, DiscordGuildsResponse, PageProps } from '@/types';
import { ChevronDownIcon } from '@heroicons/react/16/solid';
import { router, usePage } from '@inertiajs/react';
import { Layout } from './Layout';

import { getGuildIcon } from '@/lib/utils';
import axios from 'axios';

export default function ServerScopedLayout({ children }: { children: React.ReactNode }) {
    return <Layout scopeDropDown={<ServerDropDownMenu />}>{children}</Layout>;
}

/**
 * ServerDropDownMenu component that renders a dropdown menu with a list of guilds.
 */
function ServerDropDownMenu() {
    const { props } = usePage<PageProps>();
    const { auth } = props;

    const user = auth?.user || null;
    const guildsResponse = user.guilds || {};
    const guilds = Object.values(guildsResponse) as DiscordGuild[];

    const currentGuildId = user.current_server_id || null;

    const currentGuild = guilds.find((guild: DiscordGuild) => guild.id === currentGuildId) || null;

    const currentRoute: string = route().current() as string;
    const currentRouteParams: { [key: string]: string } = route().params;

    const replaceCurrentRoute = (guildId: string) => {
        // if serverId is not in currentRouteParams then do nothing
        if (!currentRouteParams.serverId) {
            return '#';
        }
        // replace the serverId param with the selected guildId
        const newRouteParams = { ...currentRouteParams, serverId: guildId };
        // return the new route with the updated params
        return route(currentRoute, newRouteParams);
    };

    const handleServerChange = (guildId: string) => {
        axios.patch(route('user.current-server', { user: user.id }), { server_id: guildId }).then(() => {
            router.get(replaceCurrentRoute(guildId));
        });
    };

    return (
        <Dropdown>
            <DropdownButton as={NavbarItem}>
                <Avatar src={currentGuild ? getGuildIcon(currentGuild) : ''} className="size-10" alt={currentGuild?.name} />
                <NavbarLabel>{currentGuild?.name || 'Servers'}</NavbarLabel>
                <ChevronDownIcon />
            </DropdownButton>
            <DropdownMenu className="min-w-80 lg:min-w-64" anchor="bottom start">
                {guilds.map((guild: DiscordGuild) => (
                    <DropdownItem
                        key={guild.id}
                        onClick={() => guild.id && handleServerChange(guild.id)}
                        disabled={guild.id === currentGuildId}
                    >
                        <Avatar src={getGuildIcon(guild)} className="size-10" alt={guild.name} />
                        <DropdownLabel>{guild.name}</DropdownLabel>
                    </DropdownItem>
                ))}
            </DropdownMenu>
        </Dropdown>
    );
}
