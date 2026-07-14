import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import { Textarea } from "@/components/ui/textarea";
import { ModernTyreMap, type KonvaSlot } from "@/components/fleet/modern-tyre-map";
import { cn } from "@/lib/utils";
import { Head, Link, useForm } from "@inertiajs/react";
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    CheckCircle,
    ClipboardCheck,
    Eye,
    Gauge,
    MoveRight,
    Plus,
    Settings,
    Table2,
    Truck,
} from "lucide-react";
import { useMemo, useState, type ReactNode } from "react";

type Vehicle = {
    id: number;
    vehicle_code: string;
    plate_number: string | null;
    display_code: string;
    asset_type: string;
    vehicle_type_name: string | null;
    odometer: number | null;
    vehicle_type: {
        id: number;
        name: string;
        asset_type: string;
        layout_json: {
            positions: Array<{
                code: string;
                display_code: string;
                label: string;
                axle?: number;
                side?: string;
                dual?: string;
            }>;
        } | null;
    } | null;
};

type Tyre = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_name: string | null;
    size_label: string | null;
    pattern: string | null;
    current_position_code: string | null;
    position_display: string;
    position_type: string;
    spare_label: string | null;
    has_baseline: boolean;
    baseline_percentage: number | null;
    baseline_id: number | null;
    baseline_odometer: number | null;
    baseline_date: string | null;
    expected_life_km: number | null;
    total_used_km: number | null;
    used_km: number | null;
    km_since_baseline: number | null;
    km_since_latest_audit: number | null;
    usage_percentage: number | null;
    estimated_remaining_percentage: number | null;
    calculated_remaining_percentage: number | null;
    latest_audited_remaining_percentage: number | null;
    effective_remaining_percentage: number | null;
    audit_variance_percentage: number | null;
    latest_audit_date: string | null;
    latest_audit_odometer: number | null;
    tread_depth_mm: number | null;
    audit_status: string | null;
    is_audited: boolean;
    calculated_status: string;
    effective_status: string;
    current_vehicle_odometer: number | null;
    status: string;
    status_color: string;
    installed_odometer: number | null;
    installed_date: string | null;
    latest_inspection: {
        tread_depth: number | null;
        pressure: number | null;
        condition: string | null;
        inspection_date: string | null;
        inspector: string | null;
    } | null;
    view_url: string;
    create_baseline_url: string;
    view_baseline_url: string | null;
    record_audit_url: string | null;
    create_movement_url: string;
};

type Summary = {
    total: number;
    healthy: number;
    warning: number;
    critical: number;
    baseline_required: number;
    average_remaining_percentage: number | null;
};

export default function ReadingMonitoringVehicle({
    vehicle,
    tyres,
    summary,
}: {
    vehicle: Vehicle;
    tyres: Tyre[];
    summary: Summary;
}) {
    const [selectedPosition, setSelectedPosition] = useState<string | null>(null);
    const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
    const [selectedTyre, setSelectedTyre] = useState<Tyre | null>(null);

    const vehicleSlots = useMemo(
        () => convertToKonvaSlots(tyres, vehicle.vehicle_type, vehicle.asset_type, vehicle.id),
        [tyres, vehicle],
    );
    const auditedCount = tyres.filter((tyre) => tyre.latest_audited_remaining_percentage !== null).length;
    const baselineSetCount = tyres.filter((tyre) => tyre.has_baseline).length;
    const actionCount = summary.warning + summary.critical + summary.baseline_required;
    const healthTone = summary.critical > 0 ? "Critical attention" : actionCount > 0 ? "Review needed" : "Fleet healthy";
    const selectedRecordKmUrl = selectedVehicleId ? route("fleet.vehicles.odometer", selectedVehicleId) : null;
    const selectedEmptyMovementUrl =
        selectedVehicleId && selectedPosition
            ? movementUrlForPosition(selectedVehicleId, selectedPosition)
            : null;

    const handleSelectPosition = (code: string, vehicleId: number) => {
        setSelectedPosition(code);
        setSelectedVehicleId(vehicleId);
        setSelectedTyre(findTyreForPosition(tyres, code) || null);
    };

    return (
        <AuthenticatedLayout header={`Reading Monitoring - ${vehicle.display_code}`}>
            <Head title={`Reading Monitoring - ${vehicle.display_code}`} />

            <div className="space-y-6">
                <section className="rounded-lg border bg-card p-4 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-3">
                            <Button variant="ghost" size="sm" asChild className="-ml-2">
                                <Link href={route("tyres.reading-monitoring.index")}>
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Back to Vehicles
                                </Link>
                            </Button>
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-2xl font-semibold tracking-tight">{vehicle.display_code}</h1>
                                    <Badge variant="outline">{vehicle.vehicle_type_name || "Vehicle"}</Badge>
                                    <Badge variant={summary.critical > 0 ? "destructive" : actionCount > 0 ? "secondary" : "default"}>
                                        {healthTone}
                                    </Badge>
                                </div>
                                <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                    Use the map for a visual health overview, the side panel for quick decisions, and the report table for audit review.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2 lg:min-w-[360px]">
                            <MiniStat label="Current vehicle KM" value={formatKm(vehicle.odometer)} />
                            <MiniStat label="Audit coverage" value={`${auditedCount}/${summary.total}`} />
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route("fleet.vehicles.odometer", vehicle.id)}>
                                    <Gauge className="mr-2 h-4 w-4" />
                                    Record Vehicle KM
                                </Link>
                            </Button>
                            {summary.baseline_required > 0 ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route("tyres.baselines.create")}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Set Baselines
                                    </Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" disabled>
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Baselines Set
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <SummaryCard label="Total Tyres" value={summary.total} helper={`${baselineSetCount} with baseline`} icon={<Truck className="h-4 w-4" />} variant="default" />
                    <SummaryCard label="Good" value={summary.healthy} helper="Effective status" icon={<CheckCircle className="h-4 w-4" />} variant="success" />
                    <SummaryCard label="Watch / Low" value={summary.warning} helper="Needs review" icon={<AlertTriangle className="h-4 w-4" />} variant="warning" />
                    <SummaryCard label="Critical" value={summary.critical} helper="Act first" icon={<AlertCircle className="h-4 w-4" />} variant="danger" />
                    <SummaryCard label="Audited" value={auditedCount} helper="Manual checkpoints" icon={<ClipboardCheck className="h-4 w-4" />} variant="info" />
                    <SummaryCard label="Avg Effective" value={summary.average_remaining_percentage ? `${summary.average_remaining_percentage}%` : "N/A"} helper="Remaining life" icon={<Gauge className="h-4 w-4" />} variant="default" />
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <Card className="overflow-hidden">
                        <CardHeader className="border-b bg-muted/20 pb-3">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Truck className="h-4 w-4" />
                                        Tyre Map
                                    </CardTitle>
                                    <CardDescription>Visual health overview by axle and position.</CardDescription>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant="outline">{summary.total} positions</Badge>
                                    <Badge variant="outline">{auditedCount} audited</Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-3 sm:p-4">
                            <ModernTyreMap
                                mapId={`vehicle-${vehicle.id}`}
                                assetType={vehicle.asset_type}
                                slots={vehicleSlots}
                                selectedPosition={selectedVehicleId === vehicle.id ? selectedPosition : null}
                                onSelect={(code) => handleSelectPosition(code, vehicle.id)}
                                className="mx-auto max-w-[700px] border-0 shadow-none"
                            />
                        </CardContent>
                    </Card>

                    <Card className="h-fit xl:sticky xl:top-4">
                        <CardHeader className="border-b bg-muted/20 pb-3">
                            <CardTitle className="text-base">Selected Position</CardTitle>
                            <CardDescription>Quick decision panel for one tyre position.</CardDescription>
                        </CardHeader>
                        <CardContent className="p-4">
                            {selectedTyre ? (
                                <TyreHealthPanel key={selectedTyre.id} tyre={selectedTyre} vehicle={vehicle} recordKmUrl={selectedRecordKmUrl} />
                            ) : selectedPosition ? (
                                <EmptyPositionPanel position={selectedPosition} movementUrl={selectedEmptyMovementUrl} recordKmUrl={selectedRecordKmUrl} />
                            ) : (
                                <div className="rounded-lg border border-dashed p-6 text-center">
                                    <Truck className="mx-auto h-8 w-8 text-muted-foreground" />
                                    <p className="mt-3 text-sm font-medium">Select a tyre position</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Click any map position to see identity, baseline, usage, audit, and actions.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <Card>
                    <CardHeader className="border-b bg-muted/20">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Table2 className="h-4 w-4" />
                                    Reading Monitoring Report
                                </CardTitle>
                                <CardDescription>Audit-aware tyre usage report for this vehicle.</CardDescription>
                            </div>
                            <Badge variant="outline">{summary.total} rows</Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[980px]">
                                <thead>
                                    <tr className="border-b bg-muted/40">
                                        <ReportHead>Tyre</ReportHead>
                                        <ReportHead>Position</ReportHead>
                                        <ReportHead>Baseline</ReportHead>
                                        <ReportHead>Used KM</ReportHead>
                                        <ReportHead>Calculated</ReportHead>
                                        <ReportHead>Audited</ReportHead>
                                        <ReportHead>Effective</ReportHead>
                                        <ReportHead>Status</ReportHead>
                                        <ReportHead align="right">Actions</ReportHead>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tyres.map((tyre) => (
                                        <tr key={tyre.id} className="border-b hover:bg-muted/30">
                                            <td className="px-4 py-3 text-sm">
                                                <div className="font-medium">{tyre.tyre_code}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {tyre.brand_name || "-"} {tyre.size_label || ""}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Badge variant="outline">{tyre.position_display}</Badge>
                                            </td>
                                            <td className="px-4 py-3 text-sm">{formatPercent(tyre.baseline_percentage)}</td>
                                            <td className="px-4 py-3 text-sm">{formatKm(tyre.used_km)}</td>
                                            <td className="px-4 py-3 text-sm">{tyre.has_baseline ? formatPercent(tyre.calculated_remaining_percentage) : "Baseline Required"}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <span>{formatPercent(tyre.latest_audited_remaining_percentage)}</span>
                                                    {tyre.is_audited && <span className="h-2 w-2 rounded-full bg-blue-600" />}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="min-w-28">
                                                    <div className="font-semibold">{tyre.has_baseline ? formatPercent(tyre.effective_remaining_percentage) : "No Base"}</div>
                                                    <HealthBar value={tyre.effective_remaining_percentage} color={tyre.status_color} />
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Badge variant={getStatusVariant(tyre.status_color)}>{tyre.effective_status}</Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm">
                                                <div className="flex justify-end gap-1">
                                                    <IconLink href={tyre.view_url} label="View tyre"><Eye className="h-4 w-4" /></IconLink>
                                                    {tyre.record_audit_url && <IconLink href={tyre.record_audit_url} label="Record audit"><Activity className="h-4 w-4" /></IconLink>}
                                                    {!tyre.has_baseline && <IconLink href={tyre.create_baseline_url} label="Set baseline"><Plus className="h-4 w-4" /></IconLink>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function MiniStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border bg-muted/20 p-3">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-lg font-semibold">{value}</p>
        </div>
    );
}

function SummaryCard({
    label,
    value,
    helper,
    icon,
    variant,
}: {
    label: string;
    value: string | number;
    helper: string;
    icon: ReactNode;
    variant: "default" | "success" | "warning" | "danger" | "outline" | "info";
}) {
    const variantStyles = {
        default: "border-border bg-card",
        success: "border-green-200 bg-green-50",
        warning: "border-amber-200 bg-amber-50",
        danger: "border-red-200 bg-red-50",
        outline: "border-slate-200 bg-slate-50",
        info: "border-blue-200 bg-blue-50",
    };
    const iconStyles = {
        default: "bg-blue-100 text-blue-700",
        success: "bg-green-100 text-green-700",
        warning: "bg-amber-100 text-amber-700",
        danger: "bg-red-100 text-red-700",
        outline: "bg-slate-100 text-slate-700",
        info: "bg-blue-100 text-blue-700",
    };

    return (
        <Card className={cn("border", variantStyles[variant])}>
            <CardContent className="flex items-center gap-3 p-4">
                <div className={cn("rounded-full p-2", iconStyles[variant])}>{icon}</div>
                <div className="min-w-0">
                    <p className="truncate text-xs text-muted-foreground">{label}</p>
                    <p className="text-lg font-semibold">{value}</p>
                    <p className="truncate text-[11px] text-muted-foreground">{helper}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function EmptyPositionPanel({
    position,
    movementUrl,
    recordKmUrl,
}: {
    position: string;
    movementUrl: string | null;
    recordKmUrl: string | null;
}) {
    return (
        <div className="space-y-4">
            <div className="rounded-lg border border-dashed p-4">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Position</span>
                    <Badge variant="outline">{position}</Badge>
                </div>
                <p className="mt-3 text-sm font-medium">Empty Position</p>
                <p className="text-xs text-muted-foreground">Mount a tyre here through the movement workflow.</p>
            </div>
            <div className="grid gap-2">
                {movementUrl && (
                    <Button asChild size="sm">
                        <Link href={movementUrl}>Mount Tyre Here</Link>
                    </Button>
                )}
                {recordKmUrl && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={recordKmUrl}>Record Vehicle KM</Link>
                    </Button>
                )}
            </div>
        </div>
    );
}

function TyreHealthPanel({ tyre, vehicle, recordKmUrl }: { tyre: Tyre; vehicle: Vehicle; recordKmUrl: string | null }) {
    const positionKind = tyre.spare_label ? "Spare" : tyre.position_type || "Running";
    const status = tyre.has_baseline ? tyre.effective_status : "Baseline Required";

    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-muted/20 p-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge className="text-sm">{tyre.position_display}</Badge>
                    <Badge variant="outline">{positionKind}</Badge>
                    <Badge variant={getStatusVariant(tyre.status_color)}>{status}</Badge>
                    {tyre.is_audited && <Badge variant="secondary">Audited</Badge>}
                </div>
                <div className="mt-3 grid grid-cols-[1fr_auto] items-end gap-3">
                    <div>
                        <p className="text-xs text-muted-foreground">Effective Remaining</p>
                        <p className="text-3xl font-semibold">{tyre.has_baseline ? formatPercent(tyre.effective_remaining_percentage) : "No Base"}</p>
                    </div>
                    <HealthBar value={tyre.effective_remaining_percentage} color={tyre.status_color} large />
                </div>
            </div>

            {tyre.spare_label && (
                <div className="rounded-md border bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                    Spare position does not gain running KM.
                </div>
            )}

            <PanelSection title="Tyre Identity">
                <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <InfoRow label="Code" value={tyre.tyre_code} />
                    <InfoRow label="Serial" value={tyre.serial_number} />
                    <InfoRow label="Brand" value={tyre.brand_name} />
                    <InfoRow label="Size" value={tyre.size_label} />
                    <InfoRow label="Vehicle" value={vehicle.display_code} />
                    <InfoRow label="Position" value={tyre.position_display} />
                </div>
            </PanelSection>

            <Separator />

            <PanelSection title="Baseline">
                {tyre.has_baseline ? (
                    <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <InfoRow label="Baseline %" value={formatPercent(tyre.baseline_percentage)} />
                        <InfoRow label="Baseline KM" value={formatKm(tyre.baseline_odometer)} />
                        <InfoRow label="Expected Life" value={formatKm(tyre.expected_life_km)} />
                        <InfoRow label="Date" value={tyre.baseline_date || "-"} />
                    </div>
                ) : (
                    <QuickBaselineForm tyre={tyre} vehicle={vehicle} />
                )}
            </PanelSection>

            <PanelSection title="Usage Snapshot">
                <div className="grid grid-cols-3 gap-2">
                    <MetricTile label="Calculated" value={tyre.has_baseline ? formatPercent(tyre.calculated_remaining_percentage) : "No Base"} />
                    <MetricTile label="Audited" value={formatPercent(tyre.latest_audited_remaining_percentage)} />
                    <MetricTile label="Effective" value={tyre.has_baseline ? formatPercent(tyre.effective_remaining_percentage) : "No Base"} strong />
                </div>
                <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <InfoRow label="Used KM" value={formatKm(tyre.used_km)} />
                    <InfoRow label="Since audit" value={formatKm(tyre.km_since_latest_audit)} />
                    <InfoRow label="Vehicle KM" value={formatKm(tyre.current_vehicle_odometer)} />
                    <InfoRow label="Audit date" value={tyre.latest_audit_date || "-"} />
                </div>
            </PanelSection>

            <PanelSection title="Audit Variance">
                {tyre.latest_audited_remaining_percentage !== null ? (
                    <div className={cn(
                        "rounded-md border px-3 py-2 text-sm",
                        (tyre.audit_variance_percentage ?? 0) < 0
                            ? "border-amber-200 bg-amber-50 text-amber-800"
                            : "border-blue-200 bg-blue-50 text-blue-800",
                    )}>
                        Manual audit is {Math.abs(tyre.audit_variance_percentage ?? 0).toFixed(1)}%
                        {(tyre.audit_variance_percentage ?? 0) < 0 ? " lower" : " higher"} than system estimate.
                    </div>
                ) : (
                    <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                        No manual condition audit percentage recorded yet.
                    </div>
                )}
            </PanelSection>

            <div className="grid gap-2 pt-1">
                {tyre.record_audit_url && (
                    <Button asChild size="sm">
                        <Link href={tyre.record_audit_url}>
                            <Activity className="mr-2 h-4 w-4" />
                            Record Condition Audit
                        </Link>
                    </Button>
                )}
                <div className="grid grid-cols-2 gap-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={tyre.view_url}>
                            <Eye className="mr-2 h-4 w-4" />
                            View
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={tyre.create_movement_url}>
                            <MoveRight className="mr-2 h-4 w-4" />
                            Move
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={tyre.view_baseline_url || tyre.create_baseline_url}>
                            <Settings className="mr-2 h-4 w-4" />
                            Baseline
                        </Link>
                    </Button>
                    {recordKmUrl && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={recordKmUrl}>
                                <Gauge className="mr-2 h-4 w-4" />
                                KM
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

function QuickBaselineForm({ tyre, vehicle }: { tyre: Tyre; vehicle: Vehicle }) {
    const isMounted = Boolean(tyre.current_position_code);
    const requiresOdometer = isMounted && !isSparePosition(tyre.current_position_code || "");
    const { data, setData, post, processing, errors } = useForm({
        tyre_id: tyre.id,
        baseline_location_type: vehicle.asset_type === "trailer" ? "trailer" : "power_vehicle",
        baseline_location_id: vehicle.id,
        baseline_position_code: tyre.current_position_code,
        baseline_odometer: requiresOdometer ? tyre.current_vehicle_odometer ?? vehicle.odometer ?? "" : "",
        baseline_percentage: 100,
        expected_life_km: 100000,
        baseline_date: new Date().toISOString().split("T")[0],
        notes: "",
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(route("tyres.baselines.store"), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3">
            <div>
                <p className="text-sm font-semibold text-amber-900">Baseline required</p>
                <p className="text-xs text-amber-800">
                    Save the starting point for this tyre before relying on calculated usage.
                </p>
            </div>

            <div className="grid grid-cols-2 gap-2 text-xs text-amber-900">
                <InfoRow label="Tyre" value={tyre.tyre_code} />
                <InfoRow label="Position" value={tyre.position_display} />
                <InfoRow label="Vehicle" value={vehicle.display_code} />
                <InfoRow label="Latest KM" value={formatKm(tyre.current_vehicle_odometer ?? vehicle.odometer)} />
            </div>

            {requiresOdometer && (
                <div className="space-y-1">
                    <Label htmlFor={`baseline_odometer_${tyre.id}`} className="text-xs">
                        Baseline Odometer (KM)
                    </Label>
                    <Input
                        id={`baseline_odometer_${tyre.id}`}
                        type="number"
                        min={0}
                        step={1}
                        value={data.baseline_odometer}
                        onChange={(event) => setData("baseline_odometer", event.target.value === "" ? "" : Number(event.target.value))}
                        className={errors.baseline_odometer ? "border-destructive bg-white" : "bg-white"}
                    />
                    {errors.baseline_odometer && <p className="text-xs text-destructive">{errors.baseline_odometer}</p>}
                </div>
            )}

            <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1">
                    <Label htmlFor={`baseline_percentage_${tyre.id}`} className="text-xs">
                        Baseline %
                    </Label>
                    <Input
                        id={`baseline_percentage_${tyre.id}`}
                        type="number"
                        min={0}
                        max={100}
                        step="0.01"
                        value={data.baseline_percentage}
                        onChange={(event) => setData("baseline_percentage", Number(event.target.value))}
                        className={errors.baseline_percentage ? "border-destructive bg-white" : "bg-white"}
                    />
                    {errors.baseline_percentage && <p className="text-xs text-destructive">{errors.baseline_percentage}</p>}
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`expected_life_km_${tyre.id}`} className="text-xs">
                        Expected Life KM
                    </Label>
                    <Input
                        id={`expected_life_km_${tyre.id}`}
                        type="number"
                        min={1}
                        step={1}
                        value={data.expected_life_km}
                        onChange={(event) => setData("expected_life_km", Number(event.target.value))}
                        className={errors.expected_life_km ? "border-destructive bg-white" : "bg-white"}
                    />
                    {errors.expected_life_km && <p className="text-xs text-destructive">{errors.expected_life_km}</p>}
                </div>
            </div>

            <div className="space-y-1">
                <Label htmlFor={`baseline_date_${tyre.id}`} className="text-xs">
                    Baseline Date
                </Label>
                <Input
                    id={`baseline_date_${tyre.id}`}
                    type="date"
                    value={data.baseline_date}
                    onChange={(event) => setData("baseline_date", event.target.value)}
                    className={errors.baseline_date ? "border-destructive bg-white" : "bg-white"}
                />
                {errors.baseline_date && <p className="text-xs text-destructive">{errors.baseline_date}</p>}
            </div>

            <div className="space-y-1">
                <Label htmlFor={`baseline_notes_${tyre.id}`} className="text-xs">
                    Notes
                </Label>
                <Textarea
                    id={`baseline_notes_${tyre.id}`}
                    rows={2}
                    value={data.notes}
                    onChange={(event) => setData("notes", event.target.value)}
                    className="bg-white"
                    placeholder="Optional baseline note"
                />
            </div>

            <div className="grid grid-cols-2 gap-2">
                <Button type="submit" size="sm" disabled={processing}>
                    {processing ? "Saving..." : "Save Baseline"}
                </Button>
                <Button asChild type="button" variant="outline" size="sm">
                    <Link href={tyre.create_baseline_url}>Full Form</Link>
                </Button>
            </div>
        </form>
    );
}

function PanelSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-2">
            <h4 className="text-sm font-semibold">{title}</h4>
            {children}
        </section>
    );
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="min-w-0">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="truncate font-medium">{value ?? "-"}</p>
        </div>
    );
}

function MetricTile({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) {
    return (
        <div className={cn("rounded-md border p-2 text-center", strong && "border-primary bg-primary/5")}>
            <p className="text-[11px] text-muted-foreground">{label}</p>
            <p className={cn("text-sm font-semibold", strong && "text-primary")}>{value}</p>
        </div>
    );
}

function ReportHead({ children, align = "left" }: { children: ReactNode; align?: "left" | "right" }) {
    return (
        <th className={cn("px-4 py-3 text-xs font-semibold uppercase text-muted-foreground", align === "right" ? "text-right" : "text-left")}>
            {children}
        </th>
    );
}

function IconLink({ href, label, children }: { href: string; label: string; children: ReactNode }) {
    return (
        <Button asChild variant="ghost" size="sm" title={label}>
            <Link href={href}>{children}</Link>
        </Button>
    );
}

function HealthBar({ value, color, large = false }: { value: number | null | undefined; color: string; large?: boolean }) {
    const safeValue = Math.max(0, Math.min(100, value ?? 0));
    const barColor = {
        green: "bg-green-600",
        yellow: "bg-amber-500",
        orange: "bg-orange-500",
        red: "bg-red-600",
        gray: "bg-slate-400",
    }[color] || "bg-slate-400";

    return (
        <div className={cn("mt-1 rounded-full bg-muted", large ? "h-3 w-24" : "h-1.5 w-full")}>
            <div className={cn("h-full rounded-full", barColor)} style={{ width: `${safeValue}%` }} />
        </div>
    );
}

function formatPercent(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toFixed(1)}%` : "-";
}

function formatKm(value: number | null | undefined): string {
    return typeof value === "number" ? `${value.toLocaleString()} KM` : "-";
}

function findTyreForPosition(tyres: Tyre[], code: string): Tyre | undefined {
    return tyres.find((tyre) => {
        const current = tyre.current_position_code;

        return current === code || current?.endsWith(`-${code}`);
    });
}

function isSparePosition(code: string): boolean {
    return code === "W" || code === "X" || code.startsWith("SPARE-");
}

function movementUrlForPosition(vehicleId: number, positionCode: string, tyre?: Tyre | null): string {
    if (tyre) {
        return route("tyres.movements.create", {
            tyre_id: tyre.id,
            source_vehicle_id: vehicleId,
            source_position: positionCode,
            source_location_type: "vehicle",
            movement_context: "vehicle_map",
        });
    }

    return route("tyres.movements.create", {
        vehicle_id: vehicleId,
        position: positionCode,
        destination_vehicle_id: vehicleId,
        destination_position: positionCode,
        movement_context: "vehicle_map",
    });
}

function convertToKonvaSlots(tyres: Tyre[], vehicleType: Vehicle["vehicle_type"], assetType: string, vehicleId: number): KonvaSlot[] {
    if (vehicleType?.layout_json?.positions && vehicleType.layout_json.positions.length > 0) {
        return vehicleType.layout_json.positions.map((position) => {
            const tyre = tyres.find((candidate) => {
                const current = candidate.current_position_code;

                return (
                    current === position.code ||
                    current === position.display_code ||
                    current === `SPARE-${position.display_code}` ||
                    current?.endsWith(`-${position.display_code}`)
                );
            });

            return slotFromTyre(position.display_code, position.code, position.label, vehicleId, tyre, {
                axle: position.axle,
                side: position.side,
                dual: position.dual,
            });
        });
    }

    const baseSlotCodes =
        assetType === "trailer"
            ? ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "X"]
            : ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "W", "K", "L", "M", "N", "X", "O", "P", "Q", "R", "S", "T", "U", "V"];

    return baseSlotCodes.map((code) => {
        const tyre = tyres.find((candidate) => {
            const current = candidate.current_position_code;

            return current === code || current === `SPARE-${code}` || current?.endsWith(`-${code}`);
        });

        return slotFromTyre(code, code, code, vehicleId, tyre);
    });
}

function slotFromTyre(
    displayCode: string,
    code: string,
    label: string,
    vehicleId: number,
    tyre?: Tyre,
    geometry: { axle?: number | null; side?: string | null; dual?: string | null } = {},
): KonvaSlot {
    return {
        code: tyre?.current_position_code || code,
        display_code: displayCode,
        label,
        axle: geometry.axle,
        side: geometry.side,
        dual: geometry.dual,
        tyre_code: tyre?.tyre_code || null,
        tyre_id: tyre?.id || null,
        vehicle_id: vehicleId,
        is_spare_position: isSparePosition(displayCode),
        color: tyre?.status_color || "gray",
        estimated_remaining_percentage: tyre?.effective_remaining_percentage ?? tyre?.estimated_remaining_percentage,
        calculated_remaining_percentage: tyre?.calculated_remaining_percentage,
        latest_audited_remaining_percentage: tyre?.latest_audited_remaining_percentage,
        effective_remaining_percentage: tyre?.effective_remaining_percentage,
        is_audited: tyre?.is_audited,
        usage_status: tyre?.effective_status,
        baseline_required: !tyre?.has_baseline,
        view_tyre_url: tyre?.view_url || null,
        create_movement_url: movementUrlForPosition(vehicleId, displayCode, tyre),
        create_baseline_url: tyre?.create_baseline_url || null,
        view_baseline_url: tyre?.view_baseline_url || null,
        record_audit_url: tyre?.record_audit_url || null,
        record_km_url: route("fleet.vehicles.odometer", vehicleId),
    };
}

function getStatusVariant(color: string): "default" | "secondary" | "destructive" | "outline" {
    const colorMap: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
        green: "default",
        yellow: "secondary",
        orange: "secondary",
        red: "destructive",
        gray: "outline",
    };

    return colorMap[color] || "outline";
}
