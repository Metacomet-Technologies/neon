import { Avatar } from '@/Components/avatar';
import { Heading } from '@/Components/heading';
import { Text } from '@/Components/text';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { getGuildIcon } from '@/lib/utils';
import { PageProps } from '@/types';
import { ArrowRightIcon } from '@heroicons/react/16/solid';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Servers" />
            <Heading>Choose a Server to get Started</Heading>
            <div className="mx-auto grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {auth.user?.guilds.map((guild) => (
                    <Link href={route('server.show', guild.id)} key={guild.id} className="w-full group">
                        <Card>
                            <CardHeader>
                                <CardTitle>{guild.name}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-center">
                                    {guild.icon ? <Avatar src={getGuildIcon(guild)} alt={guild.name} /> : null}
                                </div>
                            </CardContent>
                            <CardFooter>
                                <div className="w-full flex items-center justify-end">
                                    <Text className="flex items-center space-x-1 group-hover:underline">
                                        View Server
                                        <ArrowRightIcon className="w-4 h-4" />
                                    </Text>
                                </div>
                            </CardFooter>
                        </Card>
                    </Link>
                ))}
            </div>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
