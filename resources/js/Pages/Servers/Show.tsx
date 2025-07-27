import { Heading, Subheading } from '@/Components/catalyst/heading';
import { Text } from '@/Components/catalyst/text';
import ServerScopedLayout from '@/Layout/ServerScopedLayout';
import type { Guild, WelcomeSetting as WelcomeSettingProp } from '@/types';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import React from 'react';
import WelcomeSetting from './Partials/WelcomeSetting';

/**
 * Show component displays server information.
 *
 * @returns {React.JSX.Element} The JSX element to render.
 */
export default function Show({
    channels,
    existingSetting,
}: {
    channels: { id: string; name: string }[];
    existingSetting: WelcomeSettingProp;
}): React.JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const guild = auth.user?.guilds?.find((guild: Guild) => guild.id === auth.user?.current_server_id);

    return (
        <>
            <Head title="Server Information" />
            <Heading>{guild?.name}</Heading>

            <div>
                <Subheading>Server Information</Subheading>
                <Text>Server ID: {guild?.id}</Text>
                <Text>Server Name: {guild?.name}</Text>
                <WelcomeSetting channels={channels} existingSetting={existingSetting} />
            </div>
        </>
    );
}

Show.layout = (page: React.ReactNode) => <ServerScopedLayout>{page}</ServerScopedLayout>;
