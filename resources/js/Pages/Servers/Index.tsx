import { Avatar } from '@/Components/catalyst/avatar';
import { Button } from '@/Components/catalyst/button';
import { Heading } from '@/Components/catalyst/heading';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { getGuildIcon } from '@/lib/utils';
import { PageProps, DiscordGuild, DiscordGuildsResponse } from '@/types';
import { ArrowRightIcon } from '@heroicons/react/16/solid';
import { Head, usePage } from '@inertiajs/react';

interface IndexPageProps extends PageProps {
    guilds: DiscordGuildsResponse;
    botGuilds?: string[];
}

export default function Index({ guilds, botGuilds = [] }: IndexPageProps) {

    const handleClick = () => {
        window.open(route('join-server'), '_blank');
    };

    const botInGuild = (guildId: string): boolean => {
        return botGuilds.includes(guildId);
    };

    const guildArray = Object.values(guilds || {});

    return (
        <>
            <Head title="Servers" />
            <Heading>Choose a Server to get Started</Heading>
            <div className="mx-auto grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {guilds ? (
                    guildArray.length > 0 ? (
                        guildArray.map((guild: DiscordGuild) => (
                            <Card key={guild.id}>
                                <CardHeader>
                                    <CardTitle>{guild.name}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-center">
                                        <Avatar src={getGuildIcon({ id: guild.id, icon: guild.icon || undefined })} alt={guild.name} />
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
                            <p className="text-zinc-500 dark:text-zinc-400">
                                No servers found. Make sure you have administrator permissions in at least one Discord
                                server.
                            </p>
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
