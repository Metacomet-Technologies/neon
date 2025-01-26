import { Avatar } from '@/Components/avatar';
import { Heading } from '@/Components/heading';
import { Link } from '@/Components/link';
import { Strong, Text } from '@/Components/text';
import { Layout } from '@/Layout/Layout';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export default function Profile() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Heading level={1}>Profile</Heading>
            <div className="flex shrink-0">
                <div className="flex gap-4 items-center p-4 bg-zinc-100 rounded-lg border border-zinc-200 shadow dark:bg-zinc-800 dark:border-zinc-700">
                    <Avatar className="size-20" src={auth.user.avatar} alt={auth.user.name} square />
                    <div className="flex flex-col -space-y-2">
                        <Strong>{auth.user.name}</Strong>
                        <Text>{auth.user.email}</Text>
                    </div>
                </div>
            </div>
            <Link
                className="mt-6 text-blue-600 dark:text-blue-400 hover:underline"
                href={route('join-server')}
            >
                Request Bot Join Server
            </Link>
        </>
    );
}

Profile.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
