import { Avatar } from '@/Components/catalyst/avatar';
import {
    Dropdown,
    DropdownButton,
    DropdownDivider,
    DropdownItem,
    DropdownLabel,
    DropdownMenu,
} from '@/Components/catalyst/dropdown';
import { Navbar, NavbarDivider, NavbarItem, NavbarSection, NavbarSpacer } from '@/Components/catalyst/navbar';
import {
    Sidebar,
    SidebarBody,
    SidebarFooter,
    SidebarHeader,
    SidebarItem,
    SidebarLabel,
    SidebarSection,
} from '@/Components/catalyst/sidebar';
import { StackedLayout } from '@/Components/catalyst/stacked-layout';
import { PageProps } from '@/types';
import {
    ArrowRightStartOnRectangleIcon,
    ChevronUpIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    TableCellsIcon,
    UserCircleIcon,
    WindowIcon,
} from '@heroicons/react/16/solid';
import { usePage } from '@inertiajs/react';

import ThemeToggle from '@/Components/ThemeToggle';
import { ToastHandler } from '@/Components/ToastHandler';
import { useCallback } from 'react';

/**
 * Layout component that provides the structure for the application.
 * It includes a navbar and a sidebar, and renders the children components.
 *
 * @param {Object} props - The component props.
 * @param {React.ReactNode} props.children - The children components to be rendered within the layout.
 */
export function Layout({
    children,
    scopeDropDown = null,
}: {
    children: React.ReactNode;
    scopeDropDown?: React.ReactNode | null;
}) {
    const { component, props } = usePage<PageProps>();
    const { auth } = props;

    const currentServerId = auth?.user?.current_server_id || null;

    const handleExternalLinkClick = useCallback((routeName: string) => {
        window.open(route(routeName), '_blank');
    }, []);

    return (
        <StackedLayout
            navbar={
                <Navbar>
                    <NavbarSection>
                        <NavbarItem href={route('home')}>
                            <img
                                alt="Neon"
                                src="https://cdn.neon-bot.com/logo/pink-600/PNG/neon@4x.png"
                                className="size-8"
                            />
                        </NavbarItem>
                        {scopeDropDown}
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
                        {auth.user && (
                            <NavbarItem href={route('billing.index')} current={component.startsWith('Billing')}>
                                Billing
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
                                <DropdownItem href={route('profile')}>
                                    <UserCircleIcon />
                                    <DropdownLabel>Profile</DropdownLabel>
                                </DropdownItem>
                                <DropdownItem href={route('logout')} method="post">
                                    <ArrowRightStartOnRectangleIcon />
                                    <DropdownLabel>Sign out</DropdownLabel>
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>

                        <ThemeToggle />
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
                            {auth.user && (
                                <SidebarItem href={route('billing.index')} current={component.startsWith('Billing')}>
                                    Billing
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
                                <DropdownItem href={route('profile')}>
                                    <UserCircleIcon />
                                    <DropdownLabel>Profile</DropdownLabel>
                                </DropdownItem>
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
            <ToastHandler />
            {children}
        </StackedLayout>
    );
}
