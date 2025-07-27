import { Alert, AlertDescription } from '@/Components/ui/alert';
import { XMarkIcon } from '@heroicons/react/20/solid';
import { Badge } from '@/Components/catalyst/badge';
import { Button } from '@/Components/catalyst/button';
import { Heading, Subheading } from '@/Components/catalyst/heading';
import { Text } from '@/Components/catalyst/text';
import { Layout } from '@/Layout/Layout';
import { CheckIcon, StarIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import React, { useEffect, useState } from 'react';

interface CheckoutPageProps {
    stripePrices: {
        monthly: string;
        lifetime: string;
    };
}

const CheckoutPage: React.FC<CheckoutPageProps> = ({ stripePrices }) => {
    const [loading, setLoading] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [showRetryMessage, setShowRetryMessage] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('retry')) {
            setShowRetryMessage(true);
            // Remove retry param from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }, []);

    const handleCheckout = async (type: 'monthly' | 'lifetime', priceId: string) => {
        setLoading(type);
        setError(null);

        try {
            const endpoint = type === 'lifetime' ? '/api/checkout/lifetime' : '/api/checkout/subscription';
            const response = await axios.post(endpoint, { price_id: priceId });

            // Redirect to Stripe checkout
            window.location.href = response.data.checkout_url;
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to create checkout session');
            setLoading(null);
        }
    };

    return (
        <>
            <Head title="Choose Your License" />

            <div className="text-center mb-8">
                <Heading>Choose Your Neon License</Heading>
                <Text>Get access to premium Discord bot features and unlock the full potential of your server</Text>
            </div>

            {error && (
                <Alert variant="destructive" className="mb-6">
                    <AlertDescription className="flex justify-between items-center">
                        <span>{error}</span>
                        <button
                            type="button"
                            onClick={() => setError(null)}
                            className="ml-3 inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        >
                            <span className="sr-only">Dismiss</span>
                            <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                        </button>
                    </AlertDescription>
                </Alert>
            )}

            {showRetryMessage && (
                <Alert className="mb-6 border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-200">
                    <AlertDescription className="flex justify-between items-center">
                        <span>Ready to complete your purchase? Choose your license type below.</span>
                        <button
                            type="button"
                            onClick={() => setShowRetryMessage(false)}
                            className="ml-3 inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        >
                            <span className="sr-only">Dismiss</span>
                            <XMarkIcon className="h-5 w-5" aria-hidden="true" />
                        </button>
                    </AlertDescription>
                </Alert>
            )}

            <div className="grid lg:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {/* Monthly Subscription */}
                <div className="relative rounded-xl border border-zinc-950/10 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                    <div className="flex flex-col items-center text-center">
                        <Badge color="blue" className="mb-4">
                            Monthly
                        </Badge>

                        <div className="mb-2">
                            <span className="text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">
                                $9.99
                            </span>
                            <span className="text-lg font-medium text-zinc-500 dark:text-zinc-400">/month</span>
                        </div>

                        <Text className="mb-8">Perfect for trying out premium features</Text>

                        <ul className="space-y-3 text-left mb-8 w-full">
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Access to all premium commands</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Priority support</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Cancel anytime</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>30-day license transfer cooldown</Text>
                            </li>
                        </ul>

                        <Button
                            onClick={() => handleCheckout('monthly', stripePrices.monthly)}
                            disabled={!!loading}
                            color="blue"
                            className="w-full"
                        >
                            {loading === 'monthly' ? 'Creating checkout...' : 'Start Monthly Subscription'}
                        </Button>
                    </div>
                </div>

                {/* Lifetime License */}
                <div className="relative rounded-xl border-2 border-green-600 bg-white p-8 shadow-sm dark:border-green-500 dark:bg-zinc-900">
                    <div className="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                        <span className="inline-flex items-center gap-1 rounded-md bg-green-600 px-2 py-0.5 text-xs font-medium text-white dark:bg-green-500">
                            <StarIcon className="size-4" />
                            BEST VALUE
                        </span>
                    </div>

                    <div className="flex flex-col items-center text-center">
                        <Badge color="amber" className="mb-4 mt-4">
                            Lifetime
                        </Badge>

                        <div className="mb-2">
                            <span className="text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">
                                $49.99
                            </span>
                            <span className="text-lg font-medium text-zinc-500 dark:text-zinc-400"> once</span>
                        </div>

                        <Text className="mb-8">Pay once, use forever</Text>

                        <ul className="space-y-3 text-left mb-8 w-full">
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Access to all premium commands</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Priority support</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>No recurring charges</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>30-day license transfer cooldown</Text>
                            </li>
                            <li className="flex items-center gap-3">
                                <CheckIcon className="size-5 text-green-600 flex-shrink-0" />
                                <Text>Future updates included</Text>
                            </li>
                        </ul>

                        <Button
                            onClick={() => handleCheckout('lifetime', stripePrices.lifetime)}
                            disabled={!!loading}
                            color="green"
                            className="w-full"
                        >
                            {loading === 'lifetime' ? 'Creating checkout...' : 'Buy Lifetime License'}
                        </Button>
                    </div>
                </div>
            </div>

            <div className="mt-12 text-center">
                <Subheading>Secure & Simple</Subheading>
                <div className="mt-4 flex flex-wrap justify-center gap-6 text-sm">
                    <div className="flex items-center gap-2">
                        <span>💳</span>
                        <Text>Powered by Stripe</Text>
                    </div>
                    <div className="flex items-center gap-2">
                        <span>🔒</span>
                        <Text>Encrypted & secure</Text>
                    </div>
                    <div className="flex items-center gap-2">
                        <span>📧</span>
                        <Text>Email receipt included</Text>
                    </div>
                </div>
            </div>
        </>
    );
};

(CheckoutPage as any).layout = (page: React.ReactNode) => <Layout>{page}</Layout>;

export default CheckoutPage;
