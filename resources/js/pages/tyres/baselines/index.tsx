import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Head, Link, router } from "@inertiajs/react";
import { Plus, Search } from "lucide-react";

type Baseline = {
    id: number;
    tyre_code: string;
    brand_name: string | null;
    size_label: string | null;
    baseline_percentage: number;
    expected_life_km: number;
    baseline_date: string;
    created_by: string | null;
    created_at: string;
};

export default function BaselineIndex({
    baselines,
    filters,
}: {
    baselines: { data: Baseline[]; links: any[]; meta: any };
    filters: { search?: string };
}) {
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget as HTMLFormElement);
        router.get(route('tyres.baselines.index'), Object.fromEntries(formData), {
            preserveState: true,
        });
    };

    return (
        <AuthenticatedLayout header="Tyre Baselines">
            <Head title="Tyre Baselines" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Tyre Baselines</CardTitle>
                        <CardDescription>
                            Manage tyre baseline readings for usage tracking
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route('tyres.baselines.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Baseline
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSearch} className="mb-4">
                        <div className="flex gap-2">
                            <Input
                                name="search"
                                placeholder="Search by tyre code..."
                                defaultValue={filters.search}
                                className="max-w-sm"
                            />
                            <Button type="submit" variant="outline">
                                <Search className="h-4 w-4" />
                            </Button>
                        </div>
                    </form>

                    <div className="rounded-md border">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left text-sm font-medium">Tyre Code</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Brand</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Size</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Baseline %</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Expected Life</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Baseline Date</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Created By</th>
                                    <th className="px-4 py-2 text-left text-sm font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {baselines.data.map((baseline) => (
                                    <tr key={baseline.id} className="border-b hover:bg-muted/50">
                                        <td className="px-4 py-2 text-sm font-medium">{baseline.tyre_code}</td>
                                        <td className="px-4 py-2 text-sm">{baseline.brand_name || '—'}</td>
                                        <td className="px-4 py-2 text-sm">{baseline.size_label || '—'}</td>
                                        <td className="px-4 py-2 text-sm">{baseline.baseline_percentage}%</td>
                                        <td className="px-4 py-2 text-sm">{baseline.expected_life_km.toLocaleString()} KM</td>
                                        <td className="px-4 py-2 text-sm">{baseline.baseline_date}</td>
                                        <td className="px-4 py-2 text-sm">{baseline.created_by || '—'}</td>
                                        <td className="px-4 py-2 text-sm">
                                            <div className="flex gap-2">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route('tyres.baselines.show', baseline.id)}>
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route('tyres.baselines.edit', baseline.id)}>
                                                        Edit
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {baselines.data.length === 0 && (
                        <div className="text-center py-8 text-muted-foreground">
                            No baselines found. Create your first baseline to start tracking tyre usage.
                        </div>
                    )}

                    {baselines.links && baselines.links.length > 3 && (
                        <div className="flex justify-center gap-2 mt-4">
                            {baselines.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    asChild={!!link.url}
                                >
                                    {link.url ? (
                                        <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ) : (
                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                    )}
                                </Button>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
