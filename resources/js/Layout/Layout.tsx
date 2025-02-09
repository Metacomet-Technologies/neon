import { Avatar } from '@/Components/avatar';
import {
    Dropdown,
    DropdownButton,
    DropdownDivider,
    DropdownItem,
    DropdownLabel,
    DropdownMenu,
} from '@/Components/dropdown';
import { Navbar, NavbarDivider, NavbarItem, NavbarLabel, NavbarSection, NavbarSpacer } from '@/Components/navbar';
import {
    Sidebar,
    SidebarBody,
    SidebarFooter,
    SidebarHeader,
    SidebarItem,
    SidebarLabel,
    SidebarSection,
} from '@/Components/sidebar';
import { StackedLayout } from '@/Components/stacked-layout';
import { Guild, PageProps } from '@/types';
import {
    ArrowRightStartOnRectangleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    TableCellsIcon,
    WindowIcon,
} from '@heroicons/react/16/solid';
import { usePage } from '@inertiajs/react';

import { useCallback } from 'react';
import Flash from './Flash';
import ThemeToggleButton from './ThemeToggleButton';

/**
 * Layout component that provides the structure for the application.
 * It includes a navbar and a sidebar, and renders the children components.
 *
 * @param {Object} props - The component props.
 * @param {React.ReactNode} props.children - The children components to be rendered within the layout.
 */
export function Layout({ children }: { children: React.ReactNode }) {
    const { component, props } = usePage<PageProps>();
    const { auth, flash } = props;

    const currentServerId = auth?.user?.current_server_id || null;

    const currentGuild = auth?.user?.guilds?.find((guild) => guild.id === currentServerId) || null;

    const handleExternalLinkClick = useCallback((routeName: string) => {
        window.open(route(routeName), '_blank');
    }, []);

    return (
        <StackedLayout
            navbar={
                <Navbar>
                    <NavbarSection className="max-lg:hidden">
                        <NavbarItem href={route('home')}>
                            <img
                                alt="Neon"
                                src="https://cdn.neon-bot.com/logo/pink-600/PNG/neon@4x.png"
                                className="size-8"
                            />
                        </NavbarItem>
                        {auth.user && (
                            <Dropdown>
                                <DropdownButton as={NavbarItem} className="max-lg:hidden">
                                    <NavbarLabel>{currentGuild?.name || 'Servers'}</NavbarLabel>
                                    <ChevronDownIcon />
                                </DropdownButton>
                                <ServerDropDownMenu
                                    guilds={auth.user?.guilds || []}
                                    currentGuildId={currentServerId || ''}
                                />
                            </Dropdown>
                        )}
                    </NavbarSection>
                    <NavbarDivider className="max-lg:hidden" />
                    <NavbarSection className="max-lg:hidden">
                        {auth.user && (
                            <NavbarItem href={route('server.index')} current={component.startsWith('Servers')}>
                                Servers
                            </NavbarItem>
                        )}
                        {currentServerId && (
                            <NavbarItem
                                href={route('server.command.index', { serverId: currentServerId || '' })}
                                current={component.startsWith('Commands')}
                            >
                                Commands
                            </NavbarItem>
                        )}
                    </NavbarSection>
                    <NavbarSpacer />
                    <NavbarSection>
                        <Dropdown>
                            <DropdownButton as={NavbarItem}>
                                <Avatar src={auth.user.avatar} alt={auth.user.name} />
                            </DropdownButton>
                            <DropdownMenu>
                                {auth.user.is_admin && (
                                    <>
                                        <DropdownItem onClick={() => handleExternalLinkClick('nova.pages.home')}>
                                            <TableCellsIcon />
                                            <DropdownLabel>Nova</DropdownLabel>
                                        </DropdownItem>
                                        <DropdownItem onClick={() => handleExternalLinkClick('pulse')}>
                                            <CpuChipIcon />
                                            <DropdownLabel>Pulse</DropdownLabel>
                                        </DropdownItem>
                                        <DropdownDivider />
                                    </>
                                )}
                                <DropdownItem href={route('privacy-policy')}>
                                    <ShieldCheckIcon />
                                    <DropdownLabel>Privacy policy</DropdownLabel>
                                </DropdownItem>
                                <DropdownItem href={route('terms-of-service')}>
                                    <WindowIcon />
                                    <DropdownLabel>Terms of Service</DropdownLabel>
                                </DropdownItem>
                                <DropdownDivider />
                                <DropdownItem href={route('logout')} method="post">
                                    <ArrowRightStartOnRectangleIcon />
                                    <DropdownLabel>Sign out</DropdownLabel>
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>

                        <ThemeToggleButton name="theme-toggle-button" />
                    </NavbarSection>
                </Navbar>
            }
            sidebar={
                <Sidebar>
                    <SidebarHeader>
                        <SidebarSection>
                            <SidebarItem href={route('home')}>
                                <img
                                    alt="Neon"
                                    src="https://cdn.neon-bot.com/logo/pink-600/PNG/neon@4x.png"
                                    className="size-8"
                                />
                                <SidebarLabel>Discord Bot</SidebarLabel>
                            </SidebarItem>
                            {auth.user && (
                                <Dropdown>
                                    <DropdownButton as={SidebarItem}>
                                        <NavbarLabel>{currentGuild?.name || 'Servers'}</NavbarLabel>
                                        <ChevronDownIcon />
                                    </DropdownButton>
                                    <ServerDropDownMenu
                                        guilds={auth.user?.guilds || []}
                                        currentGuildId={currentServerId || ''}
                                    />
                                </Dropdown>
                            )}
                        </SidebarSection>
                    </SidebarHeader>
                    <SidebarBody>
                        <SidebarSection>
                            {auth.user && (
                                <SidebarItem href={route('server.index')} current={component.startsWith('Servers')}>
                                    Servers
                                </SidebarItem>
                            )}
                            {currentServerId && (
                                <SidebarItem
                                    href={route('server.command.index', { serverId: currentServerId || '' })}
                                    current={component.startsWith('Commands')}
                                >
                                    Commands
                                </SidebarItem>
                            )}
                        </SidebarSection>
                    </SidebarBody>
                    <SidebarFooter>
                        <Dropdown>
                            <DropdownButton as={SidebarItem}>
                                <span className="flex min-w-0 items-center gap-3">
                                    <Avatar src={auth?.user.avatar} className="size-10" square alt={auth?.user.name} />
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm/5 font-medium text-zinc-950 dark:text-white">
                                            {auth.user?.name}
                                        </span>
                                        <span className="block truncate text-xs/5 font-normal text-zinc-500 dark:text-zinc-400">
                                            {auth.user?.email}
                                        </span>
                                    </span>
                                </span>
                                <ChevronUpIcon />
                            </DropdownButton>
                            <DropdownMenu className="min-w-64" anchor="top start">
                                {auth.user.is_admin && (
                                    <>
                                        <DropdownItem onClick={() => handleExternalLinkClick('nova.pages.home')}>
                                            <TableCellsIcon />
                                            <DropdownLabel>Nova</DropdownLabel>
                                        </DropdownItem>
                                        <DropdownItem onClick={() => handleExternalLinkClick('pulse')}>
                                            <CpuChipIcon />
                                            <DropdownLabel>Pulse</DropdownLabel>
                                        </DropdownItem>
                                        <DropdownDivider />
                                    </>
                                )}
                                <DropdownItem href={route('privacy-policy')}>
                                    <ShieldCheckIcon />
                                    <DropdownLabel>Privacy policy</DropdownLabel>
                                </DropdownItem>
                                <DropdownItem href={route('terms-of-service')}>
                                    <WindowIcon />
                                    <DropdownLabel>Terms of Service</DropdownLabel>
                                </DropdownItem>
                                <DropdownDivider />
                                <DropdownItem href={route('logout')} method="post">
                                    <ArrowRightStartOnRectangleIcon />
                                    <DropdownLabel>Sign out</DropdownLabel>
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </SidebarFooter>
                </Sidebar>
            }
        >
            <Flash flash={flash} />
            {children}
        </StackedLayout>
    );
}

/**
 * ServerDropDownMenu component that renders a dropdown menu with a list of guilds.
 *
 * @param {Object} props - The component props.
 * @param {Guild[]} props.guilds - The list of guilds to be displayed in the dropdown menu.
 * @param {string} [props.currentGuildId] - The ID of the currently selected guild.
 */
function ServerDropDownMenu({ guilds, currentGuildId }: { guilds: Guild[]; currentGuildId?: string }) {
    const currentRoute: string = route().current() as string;
    const currentRouteParams: { [key: string]: string } = route().params;

    const replaceCurrentRoute = (guildId: string) => {
        if (guildId === currentGuildId) {
            return '#';
        }
        const newRouteParams = { ...currentRouteParams, serverId: guildId };
        return route(currentRoute, newRouteParams);
    };

    return (
        <DropdownMenu className="min-w-80 lg:min-w-64" anchor="bottom start">
            {guilds.map((guild) => (
                <DropdownItem key={guild.id} href={replaceCurrentRoute(guild.id)}>
                    <DropdownLabel>{guild.name}</DropdownLabel>
                </DropdownItem>
            ))}
        </DropdownMenu>
    );
}
