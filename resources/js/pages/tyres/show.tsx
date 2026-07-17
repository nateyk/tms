import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreStatusBadge } from "@/components/tyres/tyre-status-badge";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Head, Link, router } from "@inertiajs/react";
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    History,
    MapPin,
    Pencil,
    QrCode,
    RefreshCw,
    Activity,
    Gauge,
} from "lucide-react";
import { useState, type ReactNode } from "react";

type TyreDetail = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    pattern: string;
    supplier: string;
    source_label: string;
    purchase_date: string;
    purchase_price: number;
    invoice_number: string;
    initial_tread_depth: number | null;
    current_tread_depth: number | null;
    notes: string;
    status: string;
    status_label: string;
    status_color: string;
    current_location_type: string;
    current_location_id: number | null;
    current_position_code: string;
    vehicle_plate: string;
    qr_public_url: string | null;
    qr_scan_url: string;
    total_km: number;
    cost_per_km: number | null;
    created_at: string | null;
    updated_at: string | null;
    recent_movements: {
        movement_no: string;
        movement_type: string;
        status: string;
    }[];
    recent_maintenance: {
        maintenance_no: string;
        problem_type: string;
        status: string;
    }[];
    usage_summary: {
        has_baseline: boolean;
        baseline_percentage: number | null;
        baseline_odometer: number | null;
        baseline_date: string | null;
        expected_life_km: number | null;
        used_km: number | null;
        km_since_baseline: number | null;
        calculated_remaining_percentage: number | null;
        latest_audited_remaining_percentage: number | null;
        effective_remaining_percentage: number | null;
        audit_variance_percentage: number | null;
        current_vehicle_odometer: number | null;
        calculated_status: string;
        effective_status: string;
    };
    baseline: {
        id: number;
        baseline_percentage: number;
        baseline_odometer: number | null;
        expected_life_km: number | null;
        baseline_date: string | null;
        edit_url: string;
        view_url: string;
    } | null;
    latest_audit: {
        audited_remaining_percentage: number | null;
        calculated_remaining_percentage: number | null;
        variance_percentage: number | null;
        tread_depth_mm: number | null;
        condition_status: string | null;
        audit_odometer: number | null;
        odometer_km: number | null;
        vehicle_code: string | null;
        position_code: string | null;
        audited_by: string | null;
        recorded_at: string | null;
        audit_date: string | null;
        reason: string | null;
        notes: string | null;
    } | null;
    audit_history: {
        id: number;
        date: string | null;
        recorded_at: string | null;
        odometer: number | null;
        vehicle_code: string | null;
        position_code: string | null;
        calculated_remaining_percentage: number | null;
        audited_remaining_percentage: number | null;
        variance_percentage: number | null;
        tread_depth_mm: number | null;
        status: string | null;
        audited_by: string | null;
        reason: string | null;
        notes: string | null;
    }[];
    action_urls: {
        record_audit: string | null;
        create_movement: string;
        set_baseline: string;
        view_baseline: string | null;
    };
};

type Permissions = {
    update: boolean;
    delete: boolean;
    approve: boolean;
};

function DetailItem({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{value || "—"}</dd>
        </div>
    );
}

function IdentityTile({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-l-2 border-muted px-3 py-1">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="mt-1 text-sm font-semibold">{value}</p>
        </div>
    );
}

function formatPercent(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toFixed(1)}%` : "-";
}

function formatKm(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toLocaleString()} KM` : "-";
}

function formatVariance(value: number | null | undefined): ReactNode {
    if (typeof value !== "number") return "-";

    return <span className={value < 0 ? "text-amber-700" : value > 0 ? "text-blue-700" : "text-muted-foreground"}>{`${value > 0 ? "+" : ""}${value.toFixed(1)}%`}</span>;
}

function HealthMetric({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) {
    return (
        <div className={strong ? "rounded-lg border border-primary bg-primary/5 p-4" : "rounded-lg border p-4"}>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className={strong ? "mt-1 text-2xl font-semibold text-primary" : "mt-1 text-xl font-semibold"}>{value}</p>
        </div>
    );
}

function DetailLine({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{value}</dd>
        </div>
    );
}

export default function TyresShow({ tyre, can }: { tyre: TyreDetail; can: Permissions }) {
    const [approving, setApproving] = useState(false);
    const [regenerating, setRegenerating] = useState(false);

    const approve = () => {
        setApproving(true);
        router.post(route("tyres.approve", tyre.id), {}, { onFinish: () => setApproving(false) });
    };

    const regenerateQr = () => {
        setRegenerating(true);
        router.post(
            route("tyres.regenerate-qr", tyre.id),
            {},
            { onFinish: () => setRegenerating(false) },
        );
    };

    const isPending = tyre.status === "pending_approval";

    return (
        <AuthenticatedLayout header={tyre.tyre_code}>
            <Head title={tyre.tyre_code} />

            <div className="space-y-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route("tyres.index")}><ArrowLeft className="mr-2 h-4 w-4" />Tyres</Link>
                    </Button>
                    <span className="text-sm text-muted-foreground">/ Tyre detail</span>
                </div>

                <div className="grid gap-6 xl:grid-cols-3">
                <div className="space-y-6 xl:col-span-2">
                    <Card>
                        <CardHeader className="flex flex-col gap-4 border-b bg-muted/20 pb-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tyre identity</p>
                                <CardTitle className="mt-1 flex flex-wrap items-center gap-2 text-2xl">
                                    {tyre.tyre_code}
                                    <TyreStatusBadge
                                        label={tyre.status_label}
                                        color={tyre.status_color}
                                    />
                                </CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">Serial {tyre.serial_number}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button size="sm" asChild>
                                    <Link href={tyre.action_urls.create_movement}>
                                        <Gauge className="mr-2 h-4 w-4" />
                                        {tyre.current_position_code ? `Move from ${tyre.current_position_code}` : "Place tyre"}
                                    </Link>
                                </Button>
                                {can.update && (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route("tyres.edit", tyre.id)}>
                                            <Pencil className="mr-2 h-4 w-4" />Edit
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-5 p-5 sm:p-6">
                            <div className="grid gap-3 sm:grid-cols-3">
                                <IdentityTile label="Brand" value={tyre.brand_name || "Not recorded"} />
                                <IdentityTile label="Size" value={tyre.size_label || "Not recorded"} />
                                <IdentityTile label="Total tyre KM" value={formatKm(tyre.total_km)} />
                            </div>
                            <div className="border-y border-primary/20 bg-primary/5 px-1 py-4 sm:px-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div className="flex items-start gap-3">
                                        <div className="p-2 text-primary"><MapPin className="h-5 w-5" /></div>
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Current placement</p>
                                            <p className="mt-1 text-base font-semibold">{tyre.vehicle_plate || tyre.current_location_type}</p>
                                            <p className="text-sm text-muted-foreground">{tyre.current_location_id ? "Mounted on the fleet" : "Stored and ready to mount"}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline">Position {tyre.current_position_code || "Not mounted"}</Badge>
                                        <Badge variant={tyre.current_position_code ? "default" : "secondary"}>{tyre.current_position_code ? "Mounted" : "In store"}</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b bg-muted/20 pb-4">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle className="text-lg">Tyre health</CardTitle>
                                    <p className="mt-1 text-sm text-muted-foreground">Use effective remaining life for the next decision.</p>
                                </div>
                                <Badge variant={tyre.usage_summary.effective_status === "Good" ? "default" : "outline"}>{tyre.usage_summary.effective_status}</Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6 p-5 sm:p-6">
                            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <HealthMetric label="Effective Remaining" value={formatPercent(tyre.usage_summary.effective_remaining_percentage)} strong />
                                <HealthMetric label="Calculated Remaining" value={formatPercent(tyre.usage_summary.calculated_remaining_percentage)} />
                                <HealthMetric label="Latest Audited" value={formatPercent(tyre.usage_summary.latest_audited_remaining_percentage)} />
                                <HealthMetric label="Used KM" value={formatKm(tyre.usage_summary.used_km)} />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <section className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-sm font-semibold"><Gauge className="h-4 w-4 text-muted-foreground" />Calculation source</h3>
                                    <p className="text-xs text-muted-foreground">Estimated from baseline and vehicle odometer usage.</p>
                                    {tyre.baseline ? (
                                        <dl className="grid gap-2 text-sm">
                                            <DetailLine label="Baseline %" value={formatPercent(tyre.baseline.baseline_percentage)} />
                                            <DetailLine label="Baseline odometer" value={formatKm(tyre.baseline.baseline_odometer)} />
                                            <DetailLine label="Expected life KM" value={formatKm(tyre.baseline.expected_life_km)} />
                                            <DetailLine label="Used KM" value={formatKm(tyre.usage_summary.used_km)} />
                                            <DetailLine label="Current vehicle KM" value={formatKm(tyre.usage_summary.current_vehicle_odometer)} />
                                            <DetailLine label="Calculated remaining" value={formatPercent(tyre.usage_summary.calculated_remaining_percentage)} />
                                        </dl>
                                    ) : (
                                        <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                            Baseline required
                                        </div>
                                    )}
                                </section>

                                <section className="space-y-3">
                                    <h3 className="flex items-center gap-2 text-sm font-semibold"><ClipboardCheck className="h-4 w-4 text-muted-foreground" />Latest condition audit</h3>
                                    {tyre.latest_audit ? (
                                        <dl className="grid gap-2 text-sm">
                                            <DetailLine label="Audited remaining" value={formatPercent(tyre.latest_audit.audited_remaining_percentage)} />
                                            <DetailLine label="Calculated at audit" value={formatPercent(tyre.latest_audit.calculated_remaining_percentage)} />
                                            <DetailLine label="Variance" value={formatVariance(tyre.latest_audit.variance_percentage)} />
                                            <DetailLine label="Vehicle" value={tyre.latest_audit.vehicle_code || "-"} />
                                            <DetailLine label="Position" value={tyre.latest_audit.position_code || "-"} />
                                            <DetailLine label="Odometer at audit" value={formatKm(tyre.latest_audit.odometer_km)} />
                                            <DetailLine label="Audit date" value={tyre.latest_audit.audit_date || "-"} />
                                            <DetailLine label="Recorded by" value={tyre.latest_audit.audited_by || "-"} />
                                            <DetailLine label="Recorded at" value={tyre.latest_audit.recorded_at || "-"} />
                                            <DetailLine label="Reason" value={tyre.latest_audit.reason || "-"} />
                                            <DetailLine label="Tread depth" value={tyre.latest_audit.tread_depth_mm !== null ? `${tyre.latest_audit.tread_depth_mm} mm` : "-"} />
                                            <DetailLine label="Notes" value={tyre.latest_audit.notes || "-"} />
                                        </dl>
                                    ) : (
                                        <div className="rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                                            No condition audit recorded yet. Record an inspection when the tyre is checked.
                                        </div>
                                    )}
                                </section>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="border-b bg-muted/20 pb-4">
                            <CardTitle className="flex items-center gap-2 text-lg"><History className="h-5 w-5" />Audit history</CardTitle>
                            <p className="text-sm text-muted-foreground">Manual inspections are checkpoints. Baseline and usage history remain unchanged.</p>
                        </CardHeader>
                        <CardContent className="p-0">
                            {tyre.audit_history.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <Table className="min-w-[1050px]">
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Audit Date</TableHead><TableHead>Recorded At</TableHead><TableHead>Recorded By</TableHead>
                                                <TableHead>Vehicle / Position</TableHead><TableHead>Odometer</TableHead><TableHead>Calculated</TableHead>
                                                <TableHead>Audited</TableHead><TableHead>Variance</TableHead><TableHead>Tread</TableHead><TableHead>Status</TableHead><TableHead>Reason</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {tyre.audit_history.map((audit) => (
                                                <TableRow key={audit.id}>
                                                    <TableCell>{audit.date || "-"}</TableCell>
                                                    <TableCell>{audit.recorded_at || "-"}</TableCell>
                                                    <TableCell>{audit.audited_by || "-"}</TableCell>
                                                    <TableCell>{audit.vehicle_code || "-"}<div className="text-xs text-muted-foreground">{audit.position_code || "-"}</div></TableCell>
                                                    <TableCell>{formatKm(audit.odometer)}</TableCell>
                                                    <TableCell>{formatPercent(audit.calculated_remaining_percentage)}</TableCell>
                                                    <TableCell>{formatPercent(audit.audited_remaining_percentage)}</TableCell>
                                                    <TableCell>{formatVariance(audit.variance_percentage)}</TableCell>
                                                    <TableCell>{audit.tread_depth_mm !== null ? `${audit.tread_depth_mm} mm` : "-"}</TableCell>
                                                    <TableCell>{audit.status || "-"}</TableCell>
                                                    <TableCell>{audit.reason || "-"}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : <div className="p-6 text-sm text-muted-foreground">No condition audit recorded yet.</div>}
                        </CardContent>
                    </Card>

                </div>

                {/* Right Column - Actions & QR */}
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-4">
                            <CardTitle className="text-lg">Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {tyre.action_urls.record_audit && (
                                <Button asChild className="w-full justify-start" size="sm">
                                    <Link href={tyre.action_urls.record_audit}>
                                        <Activity className="mr-2 h-4 w-4" />
                                        Record Audit
                                    </Link>
                                </Button>
                            )}
                            {!isPending && (
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    size="sm"
                                    onClick={regenerateQr}
                                    disabled={regenerating}
                                >
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Regenerate QR
                                </Button>
                            )}
                            <Button variant="outline" className="w-full justify-start" size="sm" asChild>
                                <a href={tyre.qr_scan_url} target="_blank" rel="noreferrer">
                                    <QrCode className="mr-2 h-4 w-4" />
                                    QR Profile
                                </a>
                            </Button>
                            <Button variant="outline" className="w-full justify-start" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.registration.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    Registration PDF
                                </a>
                            </Button>
                            <Button variant="outline" className="w-full justify-start" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.history.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    History PDF
                                </a>
                            </Button>
                            {can.approve && isPending && (
                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button className="w-full justify-start" size="sm" disabled={approving}>
                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                            Approve Registration
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Approve tyre registration?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will mark the tyre as available and generate its QR code.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction onClick={approve}>Approve</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}
                        </CardContent>
                    </Card>

                    {tyre.qr_public_url && (
                        <Card>
                            <CardHeader className="pb-4">
                                <CardTitle className="text-lg">QR Code</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-col items-center space-y-3">
                                    <img
                                        src={tyre.qr_public_url}
                                        alt={`QR code for ${tyre.tyre_code}`}
                                        className="h-40 w-40 rounded border bg-white p-2"
                                    />
                                    <a
                                        href={tyre.qr_scan_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-sm text-primary hover:underline"
                                    >
                                        Scan profile
                                    </a>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader className="border-b bg-muted/20 pb-4">
                            <CardTitle className="text-lg">Record snapshot</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            <dl className="grid gap-3 text-sm">
                                <DetailLine label="Current placement" value={tyre.vehicle_plate || tyre.current_location_type} />
                                <DetailLine label="Position" value={tyre.current_position_code || "Not mounted"} />
                                <DetailLine label="Baseline" value={tyre.baseline ? formatPercent(tyre.baseline.baseline_percentage) : "Required"} />
                                <DetailLine label="Vehicle KM" value={formatKm(tyre.usage_summary.current_vehicle_odometer)} />
                                <DetailLine label="Last audit" value={tyre.latest_audit?.audit_date || "Not audited"} />
                            </dl>
                        </CardContent>
                    </Card>

                    {tyre.recent_movements.length > 0 && (
                        <Card>
                            <CardHeader className="border-b bg-muted/20 pb-4">
                                <CardTitle className="flex items-center gap-2 text-lg"><ArrowRight className="h-5 w-5" />Recent movements</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Movement</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {tyre.recent_movements.map((movement) => (
                                            <TableRow key={movement.movement_no}>
                                                <TableCell className="font-medium">{movement.movement_no}</TableCell>
                                                <TableCell>{movement.movement_type}</TableCell>
                                                <TableCell>{movement.status}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}
                </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
