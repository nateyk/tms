import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreStatusBadge } from "@/components/tyres/tyre-status-badge";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Head, Link, router } from "@inertiajs/react";
import { Eye, Filter, Pencil, Plus, Search, Trash2, X } from "lucide-react";
import { FormEvent, useState } from "react";

type TyreRow = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    current_tread_depth: number | null;
    current_location_type: string;
    placement_label: string;
    current_position_code: string;
    position_type: string;
    status_label: string;
    status_color: string;
    has_baseline: boolean;
    baseline_percentage: number | null;
    baseline_odometer: number | null;
    used_km: number | null;
    effective_remaining_percentage: number | null;
    current_vehicle_odometer: number | null;
    health_status: string;
    health_color: string;
    latest_audit_percentage: number | null;
    latest_audit_date: string | null;
    latest_audit_odometer: number | null;
    view_url: string;
};

type PaginatedTyres = {
    data: TyreRow[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type StatusOption = { value: string; label: string };

const formatKm = (value: number | null) => value == null ? "-" : `${value.toLocaleString()} KM`;
const formatPercent = (value: number | null) => value == null ? "-" : `${value.toFixed(1)}%`;

export default function TyresIndex({
    tyres,
    filters,
    statusOptions,
}: {
    tyres: PaginatedTyres;
    filters: { status: string | null; q: string | null };
    statusOptions: StatusOption[];
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.q ?? "");

    const applyFilters = (event?: FormEvent) => {
        event?.preventDefault();
        router.get(
            route("tyres.index"),
            {
                q: search.trim() || undefined,
                status: filters.status || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const filterByStatus = (status: string) => {
        router.get(
            route("tyres.index"),
            { q: search.trim() || undefined, status: status === "all" ? undefined : status },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        setSearch("");
        router.get(route("tyres.index"), {}, { preserveState: true, replace: true });
    };

    const deleteTyre = (id: number) => {
        setDeletingId(id);
        router.delete(route("tyres.destroy", id), { onFinish: () => setDeletingId(null) });
    };

    return (
        <AuthenticatedLayout header="Tyres">
            <Head title="Tyres" />

            <div className="space-y-4">
                <Card>
                    <CardHeader className="gap-4 border-b sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Tyre register</CardTitle>
                            <CardDescription>Search identity, placement, health, and usage from one operational list.</CardDescription>
                        </div>
                        <Button asChild>
                            <Link href={route("tyres.create")}>
                                <Plus className="mr-2 h-4 w-4" />
                                Register tyre
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-4 pt-5">
                        <form className="flex flex-col gap-3 lg:flex-row" onSubmit={applyFilters}>
                            <div className="relative min-w-0 flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search tyre code, serial, brand, vehicle, or plate"
                                    className="pl-9"
                                    aria-label="Search tyres"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Select value={filters.status ?? "all"} onValueChange={filterByStatus}>
                                    <SelectTrigger className="w-[190px]">
                                        <Filter className="mr-2 h-4 w-4 text-muted-foreground" />
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All statuses</SelectItem>
                                        {statusOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button type="submit">Search</Button>
                                {(filters.q || filters.status) && (
                                    <Button type="button" variant="ghost" size="icon" onClick={clearFilters} aria-label="Clear filters" title="Clear filters">
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </form>

                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{tyres.from && tyres.to ? `Showing ${tyres.from}-${tyres.to} of ${tyres.total}` : "No tyres found"}</span>
                            <span>Placement and health update from the latest recorded data</span>
                        </div>

                        <div className="overflow-x-auto rounded-md border">
                            <Table className="min-w-[1120px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[190px]">Tyre identity</TableHead>
                                        <TableHead className="w-[220px]">Current placement</TableHead>
                                        <TableHead className="w-[190px]">Baseline / usage</TableHead>
                                        <TableHead className="w-[150px]">Health</TableHead>
                                        <TableHead className="w-[150px]">Latest audit</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tyres.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-28 text-center text-muted-foreground">
                                                No tyres match the current search or status filter.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {tyres.data.map((tyre) => (
                                        <TableRow key={tyre.id} className="align-top">
                                            <TableCell>
                                                <Link href={tyre.view_url} className="font-semibold text-foreground hover:underline">{tyre.tyre_code}</Link>
                                                <p className="mt-1 text-xs text-muted-foreground">Serial {tyre.serial_number || "Not recorded"}</p>
                                                <p className="mt-1 text-xs text-muted-foreground">{tyre.brand_name || "Brand not recorded"} <span className="px-1">•</span> {tyre.size_label || "Size not recorded"}</p>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium text-foreground">{tyre.placement_label}</p>
                                                <div className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                                    <span className="rounded border px-1.5 py-0.5 font-medium text-foreground">{tyre.current_position_code}</span>
                                                    <span>{tyre.position_type}</span>
                                                    <span>•</span>
                                                    <span>{tyre.current_location_type}</span>
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">Tread {tyre.current_tread_depth != null ? `${Number(tyre.current_tread_depth).toFixed(1)} mm` : "-"}</p>
                                            </TableCell>
                                            <TableCell>
                                                {tyre.has_baseline ? (
                                                    <>
                                                        <div className="flex items-baseline justify-between gap-2">
                                                            <span className="font-semibold">{formatPercent(tyre.baseline_percentage)}</span>
                                                            <span className="text-xs text-muted-foreground">Baseline</span>
                                                        </div>
                                                        <p className="mt-1 text-xs text-muted-foreground">{formatKm(tyre.baseline_odometer)} starting KM</p>
                                                        <p className="mt-2 text-sm font-medium">{formatKm(tyre.used_km)} used</p>
                                                    </>
                                                ) : (
                                                    <>
                                                        <TyreStatusBadge label="Baseline required" color="blue" />
                                                        <p className="mt-2 text-xs text-muted-foreground">Set the starting condition before using KM calculations.</p>
                                                    </>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <TyreStatusBadge label={tyre.health_status} color={tyre.health_color} />
                                                <div className="mt-2 flex items-center gap-2">
                                                    <div className="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                                                        <div className="h-full rounded-full bg-foreground" style={{ width: `${Math.max(0, Math.min(100, tyre.effective_remaining_percentage ?? 0))}%` }} />
                                                    </div>
                                                    <span className="text-xs font-medium">{formatPercent(tyre.effective_remaining_percentage)}</span>
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">{tyre.status_label}</p>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium">{formatPercent(tyre.latest_audit_percentage)}</p>
                                                <p className="mt-1 text-xs text-muted-foreground">{tyre.latest_audit_date ?? "No audit recorded"}</p>
                                                {tyre.latest_audit_odometer != null && <p className="mt-1 text-xs text-muted-foreground">{formatKm(tyre.latest_audit_odometer)}</p>}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button variant="outline" size="icon" asChild title="View tyre" aria-label={`View ${tyre.tyre_code}`}>
                                                        <Link href={tyre.view_url}><Eye className="h-4 w-4" /></Link>
                                                    </Button>
                                                    <Button variant="outline" size="icon" asChild title="Edit tyre" aria-label={`Edit ${tyre.tyre_code}`}>
                                                        <Link href={route("tyres.edit", tyre.id)}><Pencil className="h-4 w-4" /></Link>
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger asChild>
                                                            <Button variant="outline" size="icon" disabled={deletingId === tyre.id} title="Delete tyre" aria-label={`Delete ${tyre.tyre_code}`}>
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>Delete tyre?</AlertDialogTitle>
                                                                <AlertDialogDescription>This will soft-delete the tyre record.</AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                                <AlertDialogAction onClick={() => deleteTyre(tyre.id)}>Delete</AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {tyres.last_page > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {tyres.links.map((link, index) => link.url ? (
                                    <Button key={`${link.label}-${index}`} variant={link.active ? "default" : "outline"} size="sm" asChild>
                                        <Link href={link.url}>{link.label.replace(/&[^;]+;/g, "")}</Link>
                                    </Button>
                                ) : null)}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
