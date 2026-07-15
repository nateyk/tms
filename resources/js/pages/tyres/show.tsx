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
import { Separator } from "@/components/ui/separator";
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
    CheckCircle2,
    ExternalLink,
    FileText,
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
        audited_by: string | null;
        audit_date: string | null;
        notes: string | null;
    } | null;
    audit_history: {
        id: number;
        date: string | null;
        odometer: number | null;
        calculated_remaining_percentage: number | null;
        audited_remaining_percentage: number | null;
        variance_percentage: number | null;
        tread_depth_mm: number | null;
        status: string | null;
        audited_by: string | null;
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

function formatPercent(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toFixed(1)}%` : "-";
}

function formatKm(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toLocaleString()} KM` : "-";
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

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Left Column - Main Info */}
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0 pb-4">
                            <div>
                                <CardTitle className="flex flex-wrap items-center gap-2 text-xl">
                                    {tyre.tyre_code}
                                    <TyreStatusBadge
                                        label={tyre.status_label}
                                        color={tyre.status_color}
                                    />
                                </CardTitle>
                                <p className="text-sm text-muted-foreground mt-1">{tyre.serial_number}</p>
                            </div>
                            <div className="flex gap-2">
                                {can.update && (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={route("tyres.edit", tyre.id)}>
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <DetailItem label="Brand" value={tyre.brand_name} />
                                <DetailItem label="Size" value={tyre.size_label} />
                                <DetailItem label="Status" value={tyre.status_label} />
                                <DetailItem label="Location" value={tyre.vehicle_plate || tyre.current_location_type} />
                                <DetailItem label="Position" value={tyre.current_position_code} />
                                <DetailItem label="Total km" value={tyre.total_km.toLocaleString()} />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-4">
                            <CardTitle className="text-lg">Health Status</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-4 sm:grid-cols-3">
                                <HealthMetric label="Effective Remaining" value={formatPercent(tyre.usage_summary.effective_remaining_percentage)} strong />
                                <HealthMetric label="Calculated Remaining" value={formatPercent(tyre.usage_summary.calculated_remaining_percentage)} />
                                <HealthMetric label="Used KM" value={formatKm(tyre.usage_summary.used_km)} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <section className="space-y-3">
                                    <h3 className="text-sm font-semibold">Baseline</h3>
                                    {tyre.baseline ? (
                                        <dl className="grid gap-2 text-sm">
                                            <DetailLine label="Baseline %" value={formatPercent(tyre.baseline.baseline_percentage)} />
                                            <DetailLine label="Expected life" value={formatKm(tyre.baseline.expected_life_km)} />
                                            <DetailLine label="Baseline date" value={tyre.baseline.baseline_date || "-"} />
                                        </dl>
                                    ) : (
                                        <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                            Baseline required
                                        </div>
                                    )}
                                </section>

                                <section className="space-y-3">
                                    <h3 className="text-sm font-semibold">Latest Audit</h3>
                                    {tyre.latest_audit ? (
                                        <dl className="grid gap-2 text-sm">
                                            <DetailLine label="Audited %" value={formatPercent(tyre.latest_audit.audited_remaining_percentage)} />
                                            <DetailLine label="Tread" value={tyre.latest_audit.tread_depth_mm !== null ? `${tyre.latest_audit.tread_depth_mm} mm` : "-"} />
                                            <DetailLine label="Date" value={tyre.latest_audit.audit_date || "-"} />
                                        </dl>
                                    ) : (
                                        <div className="rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                                            No audit recorded
                                        </div>
                                    )}
                                </section>
                            </div>
                        </CardContent>
                    </Card>

                    {tyre.recent_movements.length > 0 && (
                        <Card>
                            <CardHeader className="pb-4">
                                <CardTitle className="text-lg">Recent Movements</CardTitle>
                            </CardHeader>
                            <CardContent>
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
                            <Button asChild variant="outline" className="w-full justify-start" size="sm">
                                <Link href={tyre.action_urls.create_movement}>
                                    <Gauge className="mr-2 h-4 w-4" />
                                    Create Movement
                                </Link>
                            </Button>
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
