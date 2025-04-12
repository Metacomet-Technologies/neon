import { Heading, Subheading } from '@/Components/heading';
import { Text } from '@/Components/text';
import ServerScopedLayout from '@/Layout/ServerScopedLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import React from 'react';
import WelcomeSetting from './Partials/WelcomeSetting';

/**
 * Show component displays server information.
 *
 * @returns {React.JSX.Element} The JSX element to render.
 */
export default function Show({ channels }: { channels: any }): React.JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const guild = auth.user?.guilds.find((guild) => guild.id === auth.user.current_server_id);

    console.log(channels);

    return (
        <>
            <Head title="Server Information" />
            <Heading>{guild?.name}</Heading>

            <div>
                <Subheading>Server Information</Subheading>
                <Text>Server ID: {guild?.id}</Text>
                <Text>Server Name: {guild?.name}</Text>
                <WelcomeSetting />
            </div>
        </>
    );
}

Show.layout = (page: React.ReactNode) => <ServerScopedLayout>{page}</ServerScopedLayout>;
