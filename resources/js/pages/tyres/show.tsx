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

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle className="flex flex-wrap items-center gap-2">
                                {tyre.tyre_code}
                                <TyreStatusBadge
                                    label={tyre.status_label}
                                    color={tyre.status_color}
                                />
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">{tyre.serial_number}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {can.approve && isPending && (
                                <AlertDialog>
                                    <AlertDialogTrigger asChild>
                                        <Button size="sm" disabled={approving}>
                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                            Approve registration
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>
                                                Approve tyre registration?
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This will mark the tyre as available and generate its
                                                QR code.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction onClick={approve}>
                                                Approve
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            )}

                            {!isPending && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={regenerateQr}
                                    disabled={regenerating}
                                >
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Regenerate QR
                                </Button>
                            )}

                            <Button variant="outline" size="sm" asChild>
                                <a href={tyre.qr_scan_url} target="_blank" rel="noreferrer">
                                    <QrCode className="mr-2 h-4 w-4" />
                                    QR profile
                                </a>
                            </Button>

                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.registration.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    Registration PDF
                                </a>
                            </Button>

                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={route("vouchers.tyre.history.pdf", tyre.id)}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    History PDF
                                </a>
                            </Button>

                            {can.update && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route("tyres.edit", tyre.id)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <DetailItem label="Brand" value={tyre.brand_name} />
                            <DetailItem label="Size" value={tyre.size_label} />
                            <DetailItem label="Pattern" value={tyre.pattern} />
                            <DetailItem label="Supplier" value={tyre.supplier} />
                            <DetailItem label="Source" value={tyre.source_label} />
                            <DetailItem label="Purchase date" value={tyre.purchase_date} />
                            <DetailItem
                                label="Purchase price"
                                value={`ETB ${tyre.purchase_price.toLocaleString()}`}
                            />
                            <DetailItem label="Invoice" value={tyre.invoice_number} />
                            <DetailItem
                                label="Initial tread"
                                value={
                                    tyre.initial_tread_depth
                                        ? `${tyre.initial_tread_depth} mm`
                                        : null
                                }
                            />
                            <DetailItem
                                label="Current tread"
                                value={
                                    tyre.current_tread_depth
                                        ? `${tyre.current_tread_depth} mm`
                                        : null
                                }
                            />
                            <DetailItem label="Location type" value={tyre.current_location_type} />
                            <DetailItem label="Vehicle / plate" value={tyre.vehicle_plate} />
                            <DetailItem label="Position" value={tyre.current_position_code} />
                            <DetailItem
                                label="Total km"
                                value={tyre.total_km.toLocaleString()}
                            />
                            <DetailItem
                                label="Cost per km"
                                value={tyre.cost_per_km ? tyre.cost_per_km.toFixed(4) : null}
                            />
                        </dl>

                        {tyre.notes && (
                            <>
                                <Separator className="my-4" />
                                <DetailItem label="Notes" value={tyre.notes} />
                            </>
                        )}

                        {tyre.qr_public_url && (
                            <>
                                <Separator className="my-4" />
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <img
                                        src={tyre.qr_public_url}
                                        alt={`QR code for ${tyre.tyre_code}`}
                                        className="h-44 w-44 rounded border bg-white p-2"
                                    />
                                    <div className="space-y-2 text-sm">
                                        <p className="font-medium">Scan profile</p>
                                        <a
                                            href={tyre.qr_scan_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1 text-primary hover:underline"
                                        >
                                            {tyre.qr_scan_url}
                                            <ExternalLink className="h-3 w-3" />
                                        </a>
                                    </div>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle>Tyre Health</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Baseline, calculated usage, manual audit checkpoint, and effective status.
                            </p>
                        </div>
                        <div className="flex gap-2">
                            {tyre.action_urls.record_audit && (
                                <Button asChild size="sm">
                                    <Link href={tyre.action_urls.record_audit}>
                                        <Activity className="mr-2 h-4 w-4" />
                                        Record Audit
                                    </Link>
                                </Button>
                            )}
                            <Button asChild variant="outline" size="sm">
                                <Link href={tyre.action_urls.create_movement}>Create Movement</Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid gap-3 md:grid-cols-4">
                            <HealthMetric label="Effective Remaining" value={formatPercent(tyre.usage_summary.effective_remaining_percentage)} strong />
                            <HealthMetric label="Calculated Remaining" value={formatPercent(tyre.usage_summary.calculated_remaining_percentage)} />
                            <HealthMetric label="Latest Audited" value={formatPercent(tyre.usage_summary.latest_audited_remaining_percentage)} />
                            <HealthMetric label="Used KM" value={formatKm(tyre.usage_summary.used_km)} />
                        </div>

                        <div className="grid gap-4 lg:grid-cols-3">
                            <section className="space-y-3">
                                <h3 className="text-sm font-semibold">Tyre Identity</h3>
                                <dl className="grid gap-2 text-sm">
                                    <DetailLine label="Code" value={tyre.tyre_code} />
                                    <DetailLine label="Serial" value={tyre.serial_number} />
                                    <DetailLine label="Brand" value={tyre.brand_name || "-"} />
                                    <DetailLine label="Size" value={tyre.size_label || "-"} />
                                    <DetailLine label="Status" value={tyre.status_label} />
                                    <DetailLine label="Location" value={tyre.vehicle_plate} />
                                    <DetailLine label="Position" value={tyre.current_position_code} />
                                </dl>
                            </section>

                            <section className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-semibold">Baseline</h3>
                                    {tyre.baseline ? (
                                        <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700">Baseline Set</Badge>
                                    ) : (
                                        <Badge variant="outline" className="border-amber-200 bg-amber-50 text-amber-700">Required</Badge>
                                    )}
                                </div>
                                {tyre.baseline ? (
                                    <dl className="grid gap-2 text-sm">
                                        <DetailLine label="Baseline %" value={formatPercent(tyre.baseline.baseline_percentage)} />
                                        <DetailLine label="Baseline odometer" value={formatKm(tyre.baseline.baseline_odometer)} />
                                        <DetailLine label="Expected life" value={formatKm(tyre.baseline.expected_life_km)} />
                                        <DetailLine label="Baseline date" value={tyre.baseline.baseline_date || "-"} />
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={tyre.baseline.edit_url}>Edit baseline</Link>
                                        </Button>
                                    </dl>
                                ) : (
                                    <div className="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                        Baseline is required before calculated remaining can be trusted.
                                        <Button asChild size="sm">
                                            <Link href={tyre.action_urls.set_baseline}>Set Baseline</Link>
                                        </Button>
                                    </div>
                                )}
                            </section>

                            <section className="space-y-3">
                                <h3 className="text-sm font-semibold">Usage Calculation</h3>
                                <dl className="grid gap-2 text-sm">
                                    <DetailLine label="Baseline %" value={formatPercent(tyre.usage_summary.baseline_percentage)} />
                                    <DetailLine label="Expected life" value={formatKm(tyre.usage_summary.expected_life_km)} />
                                    <DetailLine label="Used KM" value={formatKm(tyre.usage_summary.used_km)} />
                                    <DetailLine label="Calculated remaining" value={formatPercent(tyre.usage_summary.calculated_remaining_percentage)} />
                                </dl>
                                <p className="text-xs text-muted-foreground">
                                    Calculated remaining is estimated from odometer usage and expected tyre life.
                                </p>
                            </section>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <section className="space-y-3">
                                <h3 className="text-sm font-semibold">Latest Condition Audit</h3>
                                {tyre.latest_audit ? (
                                    <dl className="grid gap-2 text-sm">
                                        <DetailLine label="Audited remaining" value={formatPercent(tyre.latest_audit.audited_remaining_percentage)} />
                                        <DetailLine label="Calculated at audit" value={formatPercent(tyre.latest_audit.calculated_remaining_percentage)} />
                                        <DetailLine label="Variance" value={formatPercent(tyre.latest_audit.variance_percentage)} />
                                        <DetailLine label="Tread depth" value={tyre.latest_audit.tread_depth_mm !== null ? `${tyre.latest_audit.tread_depth_mm} mm` : "-"} />
                                        <DetailLine label="Condition" value={tyre.latest_audit.condition_status || "-"} />
                                        <DetailLine label="Audit odometer" value={formatKm(tyre.latest_audit.audit_odometer)} />
                                        <DetailLine label="Audited by" value={tyre.latest_audit.audited_by || "-"} />
                                        <DetailLine label="Audit date" value={tyre.latest_audit.audit_date || "-"} />
                                    </dl>
                                ) : (
                                    <div className="rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                                        No condition audit recorded yet.
                                    </div>
                                )}
                            </section>

                            <section className="space-y-3">
                                <h3 className="text-sm font-semibold">Audit History</h3>
                                <div className="max-h-72 overflow-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Calc %</TableHead>
                                                <TableHead>Audit %</TableHead>
                                                <TableHead>Tread</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {tyre.audit_history.length > 0 ? tyre.audit_history.map((audit) => (
                                                <TableRow key={audit.id}>
                                                    <TableCell>{audit.date || "-"}</TableCell>
                                                    <TableCell>{formatPercent(audit.calculated_remaining_percentage)}</TableCell>
                                                    <TableCell>{formatPercent(audit.audited_remaining_percentage)}</TableCell>
                                                    <TableCell>{audit.tread_depth_mm !== null ? `${audit.tread_depth_mm} mm` : "-"}</TableCell>
                                                    <TableCell>{audit.status || "-"}</TableCell>
                                                </TableRow>
                                            )) : (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                                                        No audit history recorded.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </section>
                        </div>
                    </CardContent>
                </Card>

                {tyre.recent_movements.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recent movements</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Movement no.</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tyre.recent_movements.map((movement) => (
                                        <TableRow key={movement.movement_no}>
                                            <TableCell>{movement.movement_no}</TableCell>
                                            <TableCell>{movement.movement_type}</TableCell>
                                            <TableCell>{movement.status}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {tyre.recent_maintenance.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recent maintenance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Maintenance no.</TableHead>
                                        <TableHead>Problem</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tyre.recent_maintenance.map((record) => (
                                        <TableRow key={record.maintenance_no}>
                                            <TableCell>{record.maintenance_no}</TableCell>
                                            <TableCell>{record.problem_type}</TableCell>
                                            <TableCell>{record.status}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
