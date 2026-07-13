import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Head, router } from "@inertiajs/react";
import { Download, FileText } from "lucide-react";

type TyreStockItem = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    status: string;
    current_location_type: string;
};

type LifecycleItem = {
    tyre_code: string;
    status: string;
    location: string;
    assignments_count: number;
    movements_count: number;
    maintenance_count: number;
    disposed: string;
    total_km: number;
};

type KmPerformanceItem = {
    tyre_code: string;
    serial_number: string;
    brand: string | null;
    status: string;
    total_km: number;
    purchase_price: number;
    cost_per_km: number | null;
};

type MovementItem = {
    id: number;
    movement_no: string | null;
    tyre_code: string | null;
    tyre: {
        tyre_code: string | null;
    } | null;
    movement_type: string;
    movement_date: string | null;
    status: string;
};

export default function ReportsIndex({
    tyreStock,
    tyreLifecycle,
    tyreKmPerformance,
    movements,
    filters,
}: {
    tyreStock: TyreStockItem[];
    tyreLifecycle: LifecycleItem[];
    tyreKmPerformance: KmPerformanceItem[];
    movements: MovementItem[];
    filters: { from: string | null; to: string | null };
}) {
    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        const formData = new FormData(e.target as HTMLFormElement);
        router.get(route('reports.index'), {
            from: formData.get('from'),
            to: formData.get('to'),
        }, { preserveState: true });
    };

    return (
        <AuthenticatedLayout header="Reports">
            <Head title="Reports" />

            <div className="space-y-6">
                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilter} className="flex flex-wrap items-end gap-4">
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium mb-1">From Date</label>
                                <Input
                                    type="date"
                                    name="from"
                                    defaultValue={filters.from || ''}
                                />
                            </div>
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-sm font-medium mb-1">To Date</label>
                                <Input
                                    type="date"
                                    name="to"
                                    defaultValue={filters.to || ''}
                                />
                            </div>
                            <Button type="submit">Apply Filters</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Report Cards */}
                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Tyre Stock Report
                            </CardTitle>
                            <CardDescription>
                                Current tyre inventory by brand, size, and status
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                {tyreStock.length} tyres in stock
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Tyre Lifecycle Report
                            </CardTitle>
                            <CardDescription>
                                Tyre history including assignments, movements, and maintenance
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                {tyreLifecycle.length} tyre records
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Tyre KM Performance
                            </CardTitle>
                            <CardDescription>
                                Cost per kilometer analysis for all tyres
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                {tyreKmPerformance.length} performance records
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Tyre Movement Report
                            </CardTitle>
                            <CardDescription>
                                Movement history with date filters
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                {movements.length} movements
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
