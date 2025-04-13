import { Avatar } from '@/Components/avatar';
import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { getGuildIcon } from '@/lib/utils';
import { PageProps } from '@/types';
import { ArrowRightIcon } from '@heroicons/react/16/solid';
import { Head, usePage } from '@inertiajs/react';

export default function Index({ botGuilds }: { botGuilds: string[] }) {
    const { auth } = usePage<PageProps>().props;

    const botInGuild = (guildId: string) => {
        return botGuilds.includes(guildId);
    };

    const handleClick = () => {
        window.open(route('join-server'), '_blank');
    };

    return (
        <>
            <Head title="Servers" />
            <Heading>Choose a Server to get Started</Heading>
            <div className="mx-auto grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {auth.user?.guilds.map((guild) => (
                    <Card>
                        <CardHeader>
                            <CardTitle>{guild.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-center">
                                <Avatar src={getGuildIcon(guild)} alt={guild.name} />
                            </div>
                        </CardContent>
                        <CardFooter>
                            <div className="w-full flex items-center justify-end">
                                {botInGuild(guild.id) ? (
                                    <Button color="green" href={route('server.show', guild.id)}>
                                        Manage
                                        <ArrowRightIcon />
                                    </Button>
                                ) : (
                                    <Button color="blue" onClick={handleClick}>
                                        Invite
                                        <ArrowRightIcon />
                                    </Button>
                                )}
                            </div>
                        </CardFooter>
                    </Card>
                ))}
            </div>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
