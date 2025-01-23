import { Avatar } from '@/Components/avatar';
import { Dropdown, DropdownButton, DropdownItem, DropdownLabel, DropdownMenu } from '@/Components/dropdown';
import { Navbar, NavbarDivider, NavbarItem, NavbarSection, NavbarSpacer } from '@/Components/navbar';
import { Sidebar, SidebarBody, SidebarHeader, SidebarItem, SidebarLabel, SidebarSection } from '@/Components/sidebar';
import { StackedLayout } from '@/Components/stacked-layout';
import { PageProps } from '@/types';
import { ChevronDownIcon } from '@heroicons/react/16/solid';
import { usePage } from '@inertiajs/react';

export function Layout({ children }: { children: React.ReactNode }) {
    const { component, props } = usePage<PageProps>();
    const { auth } = props;

    const handleLogin = () => {
        window.location.href = route('login');
    };

    return (
        <StackedLayout
            navbar={
                <Navbar>
                    <NavbarSection className="max-lg:hidden">
                        <img
                            className="size-8 rounded-full"
                            src="https://cdn.discordapp.com/embed/avatars/0.png"
                            alt="discord"
                        />
                    </NavbarSection>
                    <NavbarDivider className="max-lg:hidden" />
                    <NavbarSection className="max-lg:hidden">
                        <NavbarItem href={route('home')} current={component === 'Home'}>
                            Home
                        </NavbarItem>
                    </NavbarSection>
                    <NavbarSpacer />
                    <NavbarSection>
                        {auth.user ? (
                            <>
                                <Dropdown>
                                    <DropdownButton as={NavbarItem}>
                                        <Avatar
                                            className="size-8"
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                            square
                                            style={{ clipPath: 'inset(1px)' }}
                                        />
                                    </DropdownButton>
                                    <DropdownMenu>
                                        <DropdownItem href={route('logout')} method="post">
                                            <DropdownLabel>Logout</DropdownLabel>
                                        </DropdownItem>
                                    </DropdownMenu>
                                </Dropdown>
                            </>
                        ) : (
                            <>
                                <NavbarItem onClick={handleLogin} current={component === 'Login'}>
                                    Login
                                </NavbarItem>
                            </>
                        )}
                    </NavbarSection>
                </Navbar>
            }
            sidebar={
                <Sidebar>
                    <SidebarHeader>
                        <Dropdown>
                            <DropdownButton as={SidebarItem} className="lg:mb-2.5">
                                <Avatar src="/tailwind-logo.svg" />
                                <SidebarLabel>Tailwind Labs</SidebarLabel>
                                <ChevronDownIcon />
                            </DropdownButton>
                        </Dropdown>
                    </SidebarHeader>
                    <SidebarBody>
                        <SidebarSection>
                            <SidebarItem href={route('home')} current={component === 'Home'}>
                                Home
                            </SidebarItem>
                        </SidebarSection>
                    </SidebarBody>
                </Sidebar>
            }
        >
            {children}
        </StackedLayout>
    );
}
