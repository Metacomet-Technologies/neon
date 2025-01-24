import { Avatar } from '@/Components/avatar';
import { Dropdown, DropdownButton, DropdownItem, DropdownLabel, DropdownMenu } from '@/Components/dropdown';
import { Navbar, NavbarDivider, NavbarItem, NavbarSection, NavbarSpacer } from '@/Components/navbar';
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
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

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
    const { auth } = props;

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
                        <img
                            alt="Neon"
                            src="https://cdn.metacomet.tech/neon/logo/pink-600/PNG/neon@4x.png"
                            className="hidden dark:block size-8"
                        />
                        <img
                            alt="Neon"
                            src="https://cdn.metacomet.tech/neon/logo/cyan-300/PNG/neon@4x.png"
                            className="block dark:hidden size-8"
                        />
                    </NavbarSection>
                    <NavbarDivider className="max-lg:hidden" />
                    <NavbarSection className="max-lg:hidden">
                        <NavbarItem href={route('home')} current={component === 'Home'}>
                            Home
                        </NavbarItem>
                        <NavbarItem href={route('profile')} current={component === 'Profile'}>
                            Profile
                        </NavbarItem>
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
                        <div className="flex items-center space-x-2">
                            <img
                                alt="Neon"
                                src="https://cdn.metacomet.tech/neon/logo/pink-600/PNG/neon@4x.png"
                                className="hidden dark:block size-8"
                            />
                            <img
                                alt="Neon"
                                src="https://cdn.metacomet.tech/neon/logo/cyan-300/PNG/neon@4x.png"
                                className="block dark:hidden size-8"
                            />
                            <SidebarLabel>Discord Bot</SidebarLabel>
                        </div>
                    </SidebarHeader>
                    <SidebarBody>
                        <SidebarSection>
                            <SidebarItem href={route('home')} current={component === 'Home'}>
                                Home
                            </SidebarItem>
                            <SidebarItem href={route('profile')} current={component === 'Profile'}>
                                Profile
                            </SidebarItem>
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
            {children}
        </StackedLayout>
    );
}
