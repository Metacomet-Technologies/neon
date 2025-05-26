import { Button } from '@/Components/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Layout } from '@/Layout/Layout';
import { useForm } from '@inertiajs/react';

interface License {
    id: number;
    guild_id: string | null;
    previous_guild_id: string | null;
    stripe_status: string;
    assigned_at: string | null;
    last_moved_at: string | null;
    ends_at: string | null;
}

interface Props {
    licenses: License[];
}

export default function Index({ licenses }: Props) {
    const {
        data,
        setData,
        put,
        delete: destroy,
        processing,
        errors,
    } = useForm({
        guild_id: '',
    });

    return (
        <div className="max-w-4xl mx-auto space-y-6">
            <h1 className="text-2xl font-bold">Your Licenses</h1>
            {licenses.length === 0 && <p>You don't have any licenses yet.</p>}

            {licenses.map((license) => (
                <Card key={license.id} className="border border-gray-300">
                    <CardContent className="p-4 space-y-2">
                        <div className="text-sm text-gray-700">
                            <strong>Status:</strong> {license.stripe_status}
                        </div>
                        <div className="text-sm text-gray-700">
                            <strong>Assigned Guild:</strong> {license.guild_id || 'None'}
                        </div>
                        <div className="flex items-center gap-4 mt-2">
                            {license.guild_id ? (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={processing}
                                    onClick={() => destroy(route('licenses.destroy', { license: license.id }))}
                                >
                                    Unassign
                                </Button>
                            ) : (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        put(route('licenses.update', { license: license.id }));
                                    }}
                                    className="flex gap-2"
                                >
                                    <input
                                        type="text"
                                        placeholder="Guild ID"
                                        value={data.guild_id}
                                        onChange={(e) => setData('guild_id', e.target.value)}
                                        className="px-2 py-1 text-sm border border-gray-300 rounded"
                                    />
                                    <Button type="submit" color="emerald" disabled={processing}>
                                        Assign
                                    </Button>
                                </form>
                            )}
                            {errors.guild_id && <p className="text-sm text-red-500">{errors.guild_id}</p>}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

Index.layout = (page: React.ReactNode) => <Layout title="Licenses">{page}</Layout>;
