import { Avatar } from '@/Components/avatar';
import { Dropdown, DropdownButton, DropdownItem, DropdownLabel, DropdownMenu } from '@/Components/dropdown';
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
import { ChevronDownIcon } from '@heroicons/react/16/solid';
import { usePage } from '@inertiajs/react';

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

    /**
     * Redirects the user to the login page.
     */
    const handleLogin = () => {
        window.location.href = route('login');
    };

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
                        <Dropdown>
                            <DropdownButton as={NavbarItem} className="max-lg:hidden">
                                <NavbarLabel>{currentGuild?.name || 'Servers'}</NavbarLabel>
                                <ChevronDownIcon />
                            </DropdownButton>
                            <ServerDropDownMenu guilds={auth.user?.guilds || []} />
                        </Dropdown>
                    </NavbarSection>
                    <NavbarDivider className="max-lg:hidden" />
                    <NavbarSection className="max-lg:hidden">
                        <NavbarItem href={route('server.index')} current={component.startsWith('Servers')}>
                            Servers
                        </NavbarItem>
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
                        {auth.user ? (
                            <Dropdown>
                                <DropdownButton as={NavbarItem}>
                                    <Avatar src={auth.user.avatar} alt={auth.user.name} />
                                </DropdownButton>
                                <DropdownMenu>
                                    <DropdownItem href={route('logout')} method="post">
                                        <DropdownLabel>Logout</DropdownLabel>
                                    </DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                        ) : (
                            <NavbarItem onClick={handleLogin} current={component === 'Login'}>
                                Login
                            </NavbarItem>
                        )}
                        <ThemeToggleButton />
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
                            <Dropdown>
                                <DropdownButton as={SidebarItem}>
                                    <NavbarLabel>{currentGuild?.name || 'Servers'}</NavbarLabel>
                                    <ChevronDownIcon />
                                </DropdownButton>
                                <ServerDropDownMenu guilds={auth.user?.guilds || []} />
                            </Dropdown>
                        </SidebarSection>
                    </SidebarHeader>
                    <SidebarBody>
                        <SidebarSection>
                            <SidebarItem href={route('server.index')} current={component.startsWith('Servers')}>
                                Servers
                            </SidebarItem>
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
                        <SidebarSection>
                            {auth.user ? (
                                <SidebarItem href={route('logout')} method="post">
                                    Logout
                                </SidebarItem>
                            ) : (
                                <SidebarItem onClick={handleLogin}>Login</SidebarItem>
                            )}
                        </SidebarSection>
                    </SidebarFooter>
                </Sidebar>
            }
        >
            <Flash flash={flash} />
            {children}
        </StackedLayout>
    );
}

function ServerDropDownMenu({ guilds }: { guilds: Guild[] }) {
    return (
        <DropdownMenu className="min-w-80 lg:min-w-64" anchor="bottom start">
            {guilds.map((guild) => (
                <DropdownItem key={guild.id} href={route('server.show', guild.id)}>
                    <DropdownLabel>{guild.name}</DropdownLabel>
                </DropdownItem>
            ))}
        </DropdownMenu>
    );
}
