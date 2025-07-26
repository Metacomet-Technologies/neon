import { Alert } from '@/Components/alert';
import { Badge } from '@/Components/badge';
import { Button } from '@/Components/button';
import { Heading, Subheading } from '@/Components/heading';
import { Text } from '@/Components/text';
import { Layout } from '@/Layout/Layout';
import { CheckIcon, StarIcon } from '@heroicons/react/16/solid';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import React, { useEffect, useState } from 'react';

const STRIPE_PRICES = {
    monthly: 'price_1Row5OHSbCCl70iIAbAlLKrU',
    lifetime: 'price_1Row5iHSbCCl70iIhRPa7TDZ',
};

const CheckoutPage: React.FC = () => {
    const [loading, setLoading] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [showCancelledMessage, setShowCancelledMessage] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('cancelled')) {
            setShowCancelledMessage(true);
            // Remove cancelled param from URL
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
                <Alert color="red" className="mb-6">
                    {error}
                </Alert>
            )}

            {showCancelledMessage && (
                <Alert color="amber" className="mb-6">
                    Payment was cancelled. You can try again when you're ready!
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
                            onClick={() => handleCheckout('monthly', STRIPE_PRICES.monthly)}
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
                    <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                        <Badge color="green" className="flex items-center gap-1">
                            <StarIcon className="size-4" />
                            BEST VALUE
                        </Badge>
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
                            onClick={() => handleCheckout('lifetime', STRIPE_PRICES.lifetime)}
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

CheckoutPage.layout = (page: React.ReactNode) => <Layout>{page}</Layout>;

export default CheckoutPage;
