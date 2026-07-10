import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Plus } from "lucide-react";
import { useState } from "react";

type UsageRow = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    current_location_type: string;
    vehicle_plate: string;
    position_code: string;
    has_baseline: boolean;
    baseline_percentage: number | null;
    expected_life_km: number | null;
    total_used_km: number | null;
    usage_percentage: number | null;
    estimated_remaining_percentage: number | null;
    status: string;
    status_color: string;
};

type PaginatedTyres = {
    data: UsageRow[];
    links: { url: string | null; label: string; active: boolean }[];
    last_page: number;
};

export default function ReadingMonitoringIndex({
    tyres,
    filters,
}: {
    tyres: PaginatedTyres;
    filters: {
        search: string | null;
        status: string | null;
        location_type: string | null;
        baseline_status: string | null;
    };
}) {
    const [searchQuery, setSearchQuery] = useState(filters.search || "");

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            route("tyres.reading-monitoring.index"),
            { ...filters, search: searchQuery },
            { preserveState: true, replace: true }
        );
    };

    const updateFilter = (key: string, value: string) => {
        router.get(
            route("tyres.reading-monitoring.index"),
            { ...filters, [key]: value },
            { preserveState: true, replace: true }
        );
    };

    const getStatusColor = (color: string) => {
        const colorMap: Record<string, string> = {
            green: "bg-green-500",
            yellow: "bg-yellow-500",
            orange: "bg-orange-500",
            red: "bg-red-500",
            gray: "bg-gray-500",
        };
        return colorMap[color] || "bg-gray-500";
    };

    return (
        <AuthenticatedLayout header="Tyre Reading Monitoring">
            <Head title="Tyre Reading Monitoring" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Reading Monitoring</CardTitle>
                        <CardDescription>
                            Track tyre usage, KM consumption, and remaining life based on baselines.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <form onSubmit={handleSearch} className="flex items-center gap-2">
                            <Input
                                placeholder="Search tyre code or serial..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-[250px]"
                            />
                            <Button type="submit" variant="outline" size="sm">
                                Search
                            </Button>
                        </form>

                        <Select
                            value={filters.baseline_status ?? "all"}
                            onValueChange={(value) => updateFilter("baseline_status", value)}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Baseline status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All baselines</SelectItem>
                                <SelectItem value="with_baseline">With baseline</SelectItem>
                                <SelectItem value="baseline_required">Baseline required</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.status ?? "all"}
                            onValueChange={(value) => updateFilter("status", value)}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                <SelectItem value="available">Available</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="maintenance">Maintenance</SelectItem>
                                <SelectItem value="damaged">Damaged</SelectItem>
                                <SelectItem value="disposed">Disposed</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.location_type ?? "all"}
                            onValueChange={(value) => updateFilter("location_type", value)}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="Location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All locations</SelectItem>
                                <SelectItem value="store">Store</SelectItem>
                                <SelectItem value="power_vehicle">Power Vehicle</SelectItem>
                                <SelectItem value="trailer">Trailer</SelectItem>
                                <SelectItem value="maintenance_center">Maintenance</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Serial</TableHead>
                                    <TableHead>Brand</TableHead>
                                    <TableHead>Size</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Vehicle</TableHead>
                                    <TableHead>Position</TableHead>
                                    <TableHead>Baseline %</TableHead>
                                    <TableHead>Expected Life KM</TableHead>
                                    <TableHead>Used KM</TableHead>
                                    <TableHead>Usage %</TableHead>
                                    <TableHead>Remaining %</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tyres.data.map((tyre) => (
                                    <TableRow key={tyre.id}>
                                        <TableCell className="font-medium">{tyre.tyre_code}</TableCell>
                                        <TableCell>{tyre.serial_number}</TableCell>
                                        <TableCell>{tyre.brand_name || "—"}</TableCell>
                                        <TableCell>{tyre.size_label || "—"}</TableCell>
                                        <TableCell>{tyre.current_location_type}</TableCell>
                                        <TableCell>{tyre.vehicle_plate}</TableCell>
                                        <TableCell>{tyre.position_code}</TableCell>
                                        <TableCell>
                                            {tyre.has_baseline ? (
                                                <span>{tyre.baseline_percentage}%</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {tyre.has_baseline ? (
                                                <span>{tyre.expected_life_km?.toLocaleString()}</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {tyre.total_used_km !== null ? (
                                                <span>{tyre.total_used_km.toLocaleString()}</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {tyre.usage_percentage !== null ? (
                                                <span>{tyre.usage_percentage.toFixed(1)}%</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {tyre.estimated_remaining_percentage !== null ? (
                                                <span>{tyre.estimated_remaining_percentage.toFixed(1)}%</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getStatusColor(tyre.status_color)}>
                                                {tyre.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={route("tyres.show", tyre.id)}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {!tyre.has_baseline && (
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={route("tyres.baselines.create", { tyre_id: tyre.id })}>
                                                            <Plus className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {tyres.last_page > 1 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {tyres.links.map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={`${link.label}-${index}`}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        asChild
                                    >
                                        <Link href={link.url}>
                                            {link.label.replace(/&[^;]+;/g, "")}
                                        </Link>
                                    </Button>
                                ) : null,
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
