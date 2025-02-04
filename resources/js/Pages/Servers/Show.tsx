import { Heading, Subheading } from '@/Components/heading';
import { Text } from '@/Components/text';
import { Layout } from '@/Layout/Layout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import React from 'react';

/**
 * Show component displays server information.
 *
 * @returns {React.JSX.Element} The JSX element to render.
 */
export default function Show(): React.JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const guild = auth.user?.guilds.find((guild) => guild.id === auth.user.current_server_id);

    return (
        <>
            <Head title="Server Information" />
            <Heading>{guild?.name}</Heading>

            <div>
                <Subheading>Server Information</Subheading>
                <Text>Server ID: {guild?.id}</Text>
                <Text>Server Name: {guild?.name}</Text>
            </div>
        </>
    );
}

Show.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
