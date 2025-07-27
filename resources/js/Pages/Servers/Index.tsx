import { Avatar } from '@/Components/catalyst/avatar';
import { Button } from '@/Components/catalyst/button';
import { Heading } from '@/Components/catalyst/heading';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { getGuildIcon } from '@/lib/utils';
import { Guild, PageProps } from '@/types';
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
                {Array.isArray(auth.user?.guilds) ? (
                    auth.user.guilds.length > 0 ? (
                        auth.user.guilds.map((guild: Guild) => (
                    <Card key={guild.id}>
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
                                {guild.id && botInGuild(guild.id) ? (
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
                        ))
                    ) : (
                        <div className="col-span-full text-center py-8">
                            <p className="text-zinc-500 dark:text-zinc-400">No servers found. Make sure you have administrator permissions in at least one Discord server.</p>
                        </div>
                    )
                ) : (
                    <div className="col-span-full text-center py-8">
                        <p className="text-zinc-500 dark:text-zinc-400">Loading servers...</p>
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;
