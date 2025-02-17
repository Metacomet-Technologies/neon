import { Alert, AlertActions, AlertDescription, AlertTitle } from '@/Components/alert';
import { Avatar } from '@/Components/avatar';
import { Button } from '@/Components/button';
import { Heading } from '@/Components/heading';
import { Link } from '@/Components/link';
import { Strong, Text } from '@/Components/text';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { PageProps } from '@/types';
import { faTwitch } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';

interface Integration {
    id: number;
    user_id: number;
    provider: string;
    provider_id: string;
    data: any;
    created_at: string;
    updated_at: string;
}

export default function Profile({ integrations }: { integrations: Integration[] }) {
    const { auth } = usePage<PageProps>().props;

    const handleClick = useCallback((provider: string) => {
        window.location.href = route('user-integration.create', provider);
    }, []);

    return (
        <>
            <Head title="Profile" />
            <Heading level={1}>Profile</Heading>
            <div className="flex flex-col gap-4 mt-4 max-w-2xl mx-auto">
                <div className="flex flex-row justify-between items-center">
                    <div className="flex shrink-0">
                        <div className="flex gap-4 items-center p-4 bg-zinc-100 rounded-lg border border-zinc-200 shadow dark:bg-zinc-800 dark:border-zinc-700">
                            <Avatar className="size-20" src={auth.user.avatar} alt={auth.user.name} square />
                            <div className="flex flex-col -space-y-2">
                                <Strong>{auth.user.name}</Strong>
                                <Text>{auth.user.email}</Text>
                            </div>
                        </div>
                    </div>
                    <Link className="mt-6 text-blue-600 dark:text-blue-400 hover:underline" href={route('join-server')}>
                        Request Bot Join Server
                    </Link>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Integrations</CardTitle>
                        <CardDescription>Connect your accounts to access more features.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 items-center gap-6">
                            <div className="flex flex-row gap-2">
                                <FontAwesomeIcon icon={faTwitch} className="fa-xl text-[#6441a5]" />
                                <Text>Twitch</Text>
                            </div>
                            {integrations.find((integration: Integration) => integration.provider === 'twitch') ? (
                                <DisconnectAlert provider="twitch" />
                            ) : (
                                <Button onClick={() => handleClick('twitch')} color="sky">
                                    Connect
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Profile.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;

function DisconnectAlert({ provider }: { provider: string }) {
    const [isOpen, setIsOpen] = useState<boolean>(false);

    return (
        <>
            <Button color="red" onClick={() => setIsOpen(true)}>
                Disconnect
            </Button>
            <Alert open={isOpen} onClose={setIsOpen}>
                <AlertTitle>Disconnect {provider}</AlertTitle>
                <AlertDescription>Are you sure you want to disconnect your {provider} account?</AlertDescription>
                <AlertActions>
                    <Button color="red" href={route('user-integration.destroy', provider)} method="delete">
                        Disconnect
                    </Button>
                    <Button onClick={() => setIsOpen(false)}>Cancel</Button>
                </AlertActions>
            </Alert>
        </>
    );
}
