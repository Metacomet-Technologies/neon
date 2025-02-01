import { Avatar } from '@/Components/avatar';
import {
    Dropdown,
    DropdownButton,
    DropdownDivider,
    DropdownItem,
    DropdownLabel,
    DropdownMenu,
} from '@/Components/dropdown';
import { Navbar, NavbarSection, NavbarSpacer } from '@/Components/navbar';
import {
    Sidebar,
    SidebarBody,
    SidebarFooter,
    SidebarHeader,
    SidebarItem,
    SidebarLabel,
    SidebarSection,
} from '@/Components/sidebar';
import { SidebarLayout } from '@/Components/sidebar-layout';
import { PageProps } from '@/types';
import {
    ArrowLeftIcon,
    ArrowRightStartOnRectangleIcon,
    ChevronUpIcon,
    CircleStackIcon,
    MoonIcon,
    ShieldCheckIcon,
    SunIcon,
    WindowIcon,
} from '@heroicons/react/16/solid';
import { usePage } from '@inertiajs/react';
import Flash from './Flash';
import { useTheme } from './ThemeContext';

export default function AdminLayout({ children }: { children: React.ReactNode }) {
    const { component, props } = usePage<PageProps>();
    const { auth, flash } = props;

    return (
        <SidebarLayout
            navbar={
                <Navbar>
                    <NavbarSpacer />
                    <NavbarSection>
                        <UserDropdown user={auth?.user} />
                    </NavbarSection>
                </Navbar>
            }
            sidebar={
                <Sidebar>
                    <SidebarHeader>
                        <SidebarItem href={route('server.index')}>
                            <ArrowLeftIcon />
                            <SidebarLabel>Back to Neon</SidebarLabel>
                        </SidebarItem>
                    </SidebarHeader>
                    <SidebarBody>
                        <SidebarSection>
                            <SidebarItem
                                href={route('admin.native-command.index')}
                                current={component.startsWith('Admin/NativeCommand')}
                            >
                                <CircleStackIcon />
                                <SidebarLabel>Native Commands</SidebarLabel>
                            </SidebarItem>
                        </SidebarSection>
                    </SidebarBody>
                    <SidebarFooter className="max-lg:hidden">
                        <UserDropdown user={auth?.user} />
                    </SidebarFooter>
                </Sidebar>
            }
        >
            <Flash flash={flash} />
            {children}
        </SidebarLayout>
    );
}

function UserDropdown({ user }: { user: { name: string; email: string; avatar: string } }) {
    const { theme, toggleTheme } = useTheme();

    return (
        <Dropdown>
            <DropdownButton as={SidebarItem}>
                <span className="flex min-w-0 items-center gap-3">
                    <Avatar src={user.avatar} className="size-10" square alt={user.name} />
                    <span className="min-w-0">
                        <span className="block truncate text-sm/5 font-medium text-zinc-950 dark:text-white">
                            {user.name}
                        </span>
                        <span className="block truncate text-xs/5 font-normal text-zinc-500 dark:text-zinc-400">
                            {user.email}
                        </span>
                    </span>
                </span>
                <ChevronUpIcon />
            </DropdownButton>
            <DropdownMenu className="min-w-64" anchor="top start">
                <DropdownItem onClick={toggleTheme}>
                    {theme === 'light' ? <MoonIcon /> : <SunIcon />}
                    <DropdownLabel>Toggle theme</DropdownLabel>
                </DropdownItem>
                <DropdownDivider />
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
    );
}
