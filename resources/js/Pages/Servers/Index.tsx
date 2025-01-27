import { Heading } from '@/Components/heading';
import { Text } from '@/Components/text';
import { Layout } from '@/Layout/Layout';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Heading>Choose a Server to get Started</Heading>
            {/* TODO: style this with cards and use the image from the discord server */}
            {auth.user?.guilds.map((guild) => (
                <div key={guild.id}>
                    <Link href={route('server.show', guild.id)}>
                        <Text>{guild.name}</Text>
                    </Link>
                </div>
            ))}
        </>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
