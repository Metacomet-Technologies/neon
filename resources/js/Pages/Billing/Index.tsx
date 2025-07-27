import { Badge } from '@/Components/catalyst/badge';
import { Button } from '@/Components/catalyst/button';
import { Combobox, ComboboxLabel, ComboboxOption } from '@/Components/catalyst/combobox';
import { Divider } from '@/Components/catalyst/divider';
import { Heading, Subheading } from '@/Components/catalyst/heading';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/catalyst/table';
import { Text } from '@/Components/catalyst/text';
import { Layout } from '@/Layout/Layout';
import { Head, router, usePage } from '@inertiajs/react';
import { Guild } from '@/types';
import React, { useState } from 'react';

interface BillingProps {
    billing: {
        licenses: any[];
        subscriptions: any[];
        payment_methods: any[];
    };
    guilds: Guild[];
    checkout?: {
        message?: string;
        type?: 'success' | 'error';
    };
}

const BillingDashboard: React.FC<BillingProps> = ({ billing, guilds, checkout }) => {
    const { props } = usePage();
    const [actionLoading, setActionLoading] = useState<string | null>(null);
    const [assignGuild, setAssignGuild] = useState<{ [key: string]: Guild | null }>({});
    const [transferGuild, setTransferGuild] = useState<{ [key: string]: Guild | null }>({});

    const openBillingPortal = () => {
        router.get('/billing/portal');
    };

    const handleAssign = (licenseId: string) => {
        const selectedGuild = assignGuild[licenseId];
        if (!selectedGuild) return;

        setActionLoading(licenseId + '-assign');
        router.post(
            `/billing/licenses/${licenseId}/assign`,
            {
                guild_id: selectedGuild.id,
            },
            {
                onFinish: () => setActionLoading(null),
            }
        );
    };

    const handlePark = (licenseId: string) => {
        setActionLoading(licenseId + '-park');
        router.post(
            `/billing/licenses/${licenseId}/park`,
            {},
            {
                onFinish: () => setActionLoading(null),
            }
        );
    };

    const handleTransfer = (licenseId: string) => {
        const selectedGuild = transferGuild[licenseId];
        if (!selectedGuild) return;

        setActionLoading(licenseId + '-transfer');
        router.post(
            `/billing/licenses/${licenseId}/transfer`,
            {
                guild_id: selectedGuild.id,
            },
            {
                onFinish: () => setActionLoading(null),
            }
        );
    };

    return (
        <>
            <Head title="Billing Dashboard" />

            <div className="flex flex-wrap items-end justify-between gap-4 mb-6">
                <div className="max-sm:w-full sm:flex-1">
                    <Heading>Billing Dashboard</Heading>
                    <Text>Manage your Neon licenses and subscriptions</Text>
                </div>
                <div className="flex gap-4">
                    {billing.payment_methods.length > 0 && (
                        <Button color="blue" onClick={openBillingPortal}>
                            Billing Portal
                        </Button>
                    )}
                    <Button color="green" href="/checkout">
                        Buy License
                    </Button>
                </div>
            </div>

            {checkout?.message && (
                <div
                    className={`mb-6 border rounded-md p-4 ${
                        checkout.type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
                    }`}
                >
                    <Text className={checkout.type === 'success' ? 'text-green-800' : 'text-red-800'}>
                        {checkout.message}
                    </Text>
                    {checkout.type === 'error' && (
                        <Text className="text-red-600 mt-2 text-sm">
                            You can{' '}
                            <a href="/checkout" className="underline">
                                try purchasing again
                            </a>{' '}
                            or contact support if the issue persists.
                        </Text>
                    )}
                </div>
            )}

            {(props as any).flash?.success && (
                <div className="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                    <Text className="text-green-800">{(props as any).flash.success}</Text>
                </div>
            )}

            {(props as any).flash?.error && (
                <div className="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <Text className="text-red-800">{(props as any).flash.error}</Text>
                </div>
            )}

            <Subheading className="mt-8 mb-4">Your Licenses</Subheading>

            {billing.licenses.length === 0 ? (
                <div className="text-center py-8">
                    <Text>No licenses found. Purchase a license to get started!</Text>
                    <Button color="green" href="/checkout" className="mt-4">
                        Buy Your First License
                    </Button>
                </div>
            ) : (
                <Table dense striped className="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]">
                    <TableHead>
                        <TableRow>
                            <TableHeader>Type</TableHeader>
                            <TableHeader>Status</TableHeader>
                            <TableHeader>Guild</TableHeader>
                            <TableHeader>Cooldown</TableHeader>
                            <TableHeader>Actions</TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {billing.licenses.map((license: any) => {
                            const cooldown = license.last_assigned_at
                                ? (() => {
                                      const last = new Date(license.last_assigned_at);
                                      const now = new Date();
                                      const diff = Math.floor((now.getTime() - last.getTime()) / (1000 * 60 * 60 * 24));
                                      return diff < 30 ? 30 - diff : 0;
                                  })()
                                : 0;

                            return (
                                <TableRow key={license.id}>
                                    <TableCell>
                                        <Badge color={license.type === 'lifetime' ? 'amber' : 'blue'}>
                                            {license.type === 'lifetime' ? 'Lifetime' : 'Subscription'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge color={license.status === 'active' ? 'green' : 'zinc'}>
                                            {license.status === 'active' ? 'Active' : 'Parked'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Text>{license.assigned_guild_name || '—'}</Text>
                                    </TableCell>
                                    <TableCell>
                                        <Text>{cooldown > 0 ? `${cooldown} days` : '—'}</Text>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-2">
                                            {/* Assign Form */}
                                            {license.status === 'parked' && (
                                                <form
                                                    onSubmit={(e) => {
                                                        e.preventDefault();
                                                        handleAssign(license.id);
                                                    }}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Combobox
                                                        options={guilds}
                                                        value={assignGuild[license.id] || null}
                                                        onChange={(selectedGuild) =>
                                                            setAssignGuild({
                                                                ...assignGuild,
                                                                [license.id]: selectedGuild,
                                                            })
                                                        }
                                                        displayValue={(guild) => guild?.name || ''}
                                                        placeholder="Select server"
                                                        disabled={!!actionLoading}
                                                        className="block w-full"
                                                    >
                                                        {(guild) => (
                                                            <ComboboxOption
                                                                key={guild.id}
                                                                value={guild}
                                                                disabled={!guild.is_bot_member}
                                                            >
                                                                <ComboboxLabel>{guild.name}</ComboboxLabel>
                                                                {!guild.is_bot_member && (
                                                                    <span className="text-zinc-500 text-sm ml-2">
                                                                        (Bot not in server)
                                                                    </span>
                                                                )}
                                                            </ComboboxOption>
                                                        )}
                                                    </Combobox>
                                                    <Button type="submit" color="green" disabled={!!actionLoading}>
                                                        {actionLoading === license.id + '-assign'
                                                            ? 'Assigning...'
                                                            : 'Assign'}
                                                    </Button>
                                                </form>
                                            )}

                                            {/* Park Button */}
                                            {license.status === 'active' && (
                                                <Button
                                                    onClick={() => handlePark(license.id)}
                                                    color="amber"
                                                    disabled={!!actionLoading}
                                                >
                                                    {actionLoading === license.id + '-park' ? 'Parking...' : 'Park'}
                                                </Button>
                                            )}

                                            {/* Transfer Form */}
                                            {license.status === 'active' && (
                                                <form
                                                    onSubmit={(e) => {
                                                        e.preventDefault();
                                                        handleTransfer(license.id);
                                                    }}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Combobox
                                                        options={guilds}
                                                        value={transferGuild[license.id] || null}
                                                        onChange={(selectedGuild) =>
                                                            setTransferGuild({
                                                                ...transferGuild,
                                                                [license.id]: selectedGuild,
                                                            })
                                                        }
                                                        displayValue={(guild) => guild?.name || ''}
                                                        placeholder="New server"
                                                        disabled={!!actionLoading}
                                                        className="block w-full"
                                                    >
                                                        {(guild) => (
                                                            <ComboboxOption
                                                                key={guild.id}
                                                                value={guild}
                                                                disabled={!guild.is_bot_member}
                                                            >
                                                                <ComboboxLabel>{guild.name}</ComboboxLabel>
                                                                {!guild.is_bot_member && (
                                                                    <span className="text-zinc-500 text-sm ml-2">
                                                                        (Bot not in server)
                                                                    </span>
                                                                )}
                                                            </ComboboxOption>
                                                        )}
                                                    </Combobox>
                                                    <Button type="submit" color="purple" disabled={!!actionLoading}>
                                                        {actionLoading === license.id + '-transfer'
                                                            ? 'Transferring...'
                                                            : 'Transfer'}
                                                    </Button>
                                                </form>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            )}

            <Divider className="my-8" />

            <Subheading className="mb-4">Subscriptions</Subheading>
            {billing.subscriptions.length === 0 ? (
                <Text>No active subscriptions.</Text>
            ) : (
                <Table dense className="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]">
                    <TableHead>
                        <TableRow>
                            <TableHeader>Status</TableHeader>
                            <TableHeader>Trial Ends</TableHeader>
                            <TableHeader>Ends At</TableHeader>
                            <TableHeader>Created</TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {billing.subscriptions.map((sub: any) => (
                            <TableRow key={sub.id}>
                                <TableCell>
                                    <Badge color={sub.stripe_status === 'active' ? 'green' : 'zinc'}>
                                        {sub.stripe_status}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Text>{sub.trial_ends_at || '—'}</Text>
                                </TableCell>
                                <TableCell>
                                    <Text>{sub.ends_at || '—'}</Text>
                                </TableCell>
                                <TableCell>
                                    <Text>{sub.created_at || '—'}</Text>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}

            <Divider className="my-8" />

            <Subheading className="mb-4">Payment Methods</Subheading>
            {billing.payment_methods.length === 0 ? (
                <Text>No payment methods on file.</Text>
            ) : (
                <Table dense className="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]">
                    <TableHead>
                        <TableRow>
                            <TableHeader>Type</TableHeader>
                            <TableHeader>Brand</TableHeader>
                            <TableHeader>Last Four</TableHeader>
                            <TableHeader>Expires</TableHeader>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {billing.payment_methods.map((pm: any) => (
                            <TableRow key={pm.id}>
                                <TableCell>
                                    <Text>{pm.type}</Text>
                                </TableCell>
                                <TableCell>
                                    <Text>{pm.brand}</Text>
                                </TableCell>
                                <TableCell>
                                    <Text>•••• {pm.last_four}</Text>
                                </TableCell>
                                <TableCell>
                                    <Text>
                                        {pm.exp_month}/{pm.exp_year}
                                    </Text>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </>
    );
};

(BillingDashboard as any).layout = (page: React.ReactNode) => <Layout>{page}</Layout>;

export default BillingDashboard;
