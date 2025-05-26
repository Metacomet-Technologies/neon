import { Head } from '@inertiajs/react';
import axios from 'axios';
import React, { useState } from 'react';

export default function PurchaseLicense() {
    const [quantity, setQuantity] = useState(1);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const handleCheckout = async (e: React.FormEvent) => {
        e.preventDefault();

        setLoading(true);
        setError(null);

        try {
            const res = await axios.post(route('licenses.checkout'), { quantity });

            if (res.data.checkout_url) {
                window.location.href = res.data.checkout_url;
            } else {
                throw new Error('Missing checkout_url in response');
            }
        } catch (err: any) {
            setError(err.response?.data?.message ?? 'Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Buy a License" />
            <h1>Buy License(s)</h1>

            <form onSubmit={handleCheckout}>
                <label htmlFor="quantity">Number of Licenses:</label>
                <input
                    type="number"
                    min="1"
                    id="quantity"
                    value={quantity}
                    onChange={(e) => setQuantity(parseInt(e.target.value, 10))}
                />

                <button type="submit" disabled={loading}>
                    {loading ? 'Redirecting...' : 'Proceed to Checkout'}
                </button>
            </form>

            {error && <p style={{ color: 'red' }}>{error}</p>}
        </>
    );
}
