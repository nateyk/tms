import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Truck, AlertTriangle, CheckCircle, AlertCircle, Clock, Gauge, Plus, Settings, Save } from "lucide-react";
import { useState, useEffect } from "react";
import { ModernTyreMap, type KonvaSlot } from "@/components/fleet/modern-tyre-map";
import { cn } from "@/lib/utils";

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
            }>;
        } | null;
    } | null;
};

type AttachedTrailer = {
    id: number;
    vehicle_code: string;
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
    expected_life_km: number | null;
    total_used_km: number | null;
    usage_percentage: number | null;
    estimated_remaining_percentage: number | null;
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
    attached_trailer,
    tyres,
    summary,
}: {
    vehicle: Vehicle;
    attached_trailer: AttachedTrailer | null;
    tyres: Tyre[];
    summary: Summary;
}) {
    const [selectedPosition, setSelectedPosition] = useState<string | null>(null);
    const [selectedTyre, setSelectedTyre] = useState<Tyre | null>(null);
    const [trailerTyres, setTrailerTyres] = useState<Tyre[]>([]);
    const [loadingTrailer, setLoadingTrailer] = useState(false);
    const [editingOdometer, setEditingOdometer] = useState(false);
    const [odometerValue, setOdometerValue] = useState(vehicle.odometer?.toString() || '');

    const odometerForm = useForm({
        odometer: vehicle.odometer || 0,
    });

    const handleOdometerUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        odometerForm.put(route('vehicles.odometer.update', vehicle.id), {
            onSuccess: () => {
                setEditingOdometer(false);
                window.location.reload();
            },
        });
    };

    useEffect(() => {
        if (attached_trailer) {
            setLoadingTrailer(true);
            fetch(route('api.trailers.reading-monitoring-map', attached_trailer.id))
                .then(res => res.json())
                .then(data => {
                    setTrailerTyres(data.tyres || []);
                })
                .finally(() => setLoadingTrailer(false));
        }
    }, [attached_trailer]);

    const handleSelectPosition = (code: string) => {
        setSelectedPosition(code);
        const tyre = tyres.find(t => t.current_position_code === code) || 
                     trailerTyres.find(t => t.current_position_code === code);
        setSelectedTyre(tyre || null);
    };

    const runningTyres = tyres.filter(t => t.position_type === 'running');
    const spareTyres = tyres.filter(t => t.position_type === 'spare');
    
    const vehicleSlots = convertToKonvaSlots(runningTyres, vehicle.vehicle_type, vehicle.asset_type);
    const trailerSlots = attached_trailer ? convertToKonvaSlots(trailerTyres, attached_trailer.vehicle_type, attached_trailer.asset_type) : [];

    return (
        <AuthenticatedLayout header={`Reading Monitoring - ${vehicle.display_code}`}>
            <Head title={`Reading Monitoring - ${vehicle.display_code}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={route('tyres.reading-monitoring.index')}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to Vehicles
                            </Link>
                        </Button>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('fleet.vehicles.odometer', vehicle.id)}>
                                <Gauge className="mr-2 h-4 w-4" />
                                Update Odometer
                            </Link>
                        </Button>
                        {summary.baseline_required > 0 && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('tyres.baselines.create')}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Set Missing Baselines ({summary.baseline_required})
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                    <SummaryCard
                        label="Total Tyres"
                        value={summary.total}
                        icon={<Truck className="h-4 w-4" />}
                        variant="default"
                    />
                    <SummaryCard
                        label="Healthy"
                        value={summary.healthy}
                        icon={<CheckCircle className="h-4 w-4" />}
                        variant="success"
                    />
                    <SummaryCard
                        label="Warning"
                        value={summary.warning}
                        icon={<AlertTriangle className="h-4 w-4" />}
                        variant="warning"
                    />
                    <SummaryCard
                        label="Critical"
                        value={summary.critical}
                        icon={<AlertCircle className="h-4 w-4" />}
                        variant="danger"
                    />
                    <SummaryCard
                        label="Baseline Required"
                        value={summary.baseline_required}
                        icon={<Clock className="h-4 w-4" />}
                        variant="outline"
                    />
                    <SummaryCard
                        label="Avg Remaining"
                        value={summary.average_remaining_percentage ? `${summary.average_remaining_percentage}%` : 'N/A'}
                        icon={<Gauge className="h-4 w-4" />}
                        variant="default"
                    />
                </div>

                {/* Main Content */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Tyre Map */}
                    <div className="lg:col-span-2 space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle Tyre Map - {vehicle.display_code}</CardTitle>
                                <CardDescription>
                                    {vehicle.vehicle_type_name} • 
                                    {editingOdometer ? (
                                        <form onSubmit={handleOdometerUpdate} className="inline-flex items-center gap-2 ml-2">
                                            <Input
                                                type="number"
                                                value={odometerForm.data.odometer}
                                                onChange={(e) => odometerForm.setData('odometer', parseInt(e.target.value) || 0)}
                                                className="w-32 h-8"
                                                placeholder="Enter KM"
                                            />
                                            <Button type="submit" size="sm" disabled={odometerForm.processing}>
                                                <Save className="h-3 w-3 mr-1" />
                                                Save
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => setEditingOdometer(false)}>
                                                Cancel
                                            </Button>
                                        </form>
                                    ) : (
                                        <span className="inline-flex items-center gap-2">
                                            Odometer: {vehicle.odometer ? `${vehicle.odometer.toLocaleString()} km` : 'Not set'}
                                            <Button variant="ghost" size="sm" onClick={() => setEditingOdometer(true)} className="h-6 px-2">
                                                <Settings className="h-3 w-3" />
                                            </Button>
                                        </span>
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ModernTyreMap
                                    mapId={`vehicle-${vehicle.id}`}
                                    assetType={vehicle.asset_type}
                                    slots={vehicleSlots}
                                    selectedPosition={selectedPosition}
                                    onSelect={handleSelectPosition}
                                />
                            </CardContent>
                        </Card>

                        {attached_trailer && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Trailer Tyre Map - {attached_trailer.display_code}</CardTitle>
                                    <CardDescription>
                                        {attached_trailer.vehicle_type_name} • Odometer: {attached_trailer.odometer ? `${attached_trailer.odometer.toLocaleString()} km` : 'Not set'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {loadingTrailer ? (
                                        <div className="py-8 text-center text-muted-foreground">Loading trailer tyres...</div>
                                    ) : trailerSlots.length > 0 ? (
                                        <ModernTyreMap
                                            mapId={`trailer-${attached_trailer.id}`}
                                            assetType={attached_trailer.asset_type}
                                            slots={trailerSlots}
                                            selectedPosition={selectedPosition}
                                            onSelect={handleSelectPosition}
                                        />
                                    ) : (
                                        <div className="py-8 text-center text-muted-foreground">No tyres on trailer</div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Spare Tyres Section */}
                        {spareTyres.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Spare Tyres</CardTitle>
                                    <CardDescription>
                                        Spare tyres (W/X) - Do not accumulate running KM
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        {spareTyres.map((tyre) => (
                                            <div key={tyre.id} className="rounded-md border p-4">
                                                <div className="flex items-center justify-between mb-2">
                                                    <Badge variant="outline">{tyre.spare_label}</Badge>
                                                    <Badge variant={getStatusVariant(tyre.status_color)}>
                                                        {tyre.status}
                                                    </Badge>
                                                </div>
                                                <div className="space-y-1 text-sm">
                                                    <div className="flex justify-between">
                                                        <span className="text-muted-foreground">Position:</span>
                                                        <span className="font-medium">{tyre.position_display}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-muted-foreground">Tyre Code:</span>
                                                        <span className="font-medium">{tyre.tyre_code}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-muted-foreground">Brand:</span>
                                                        <span>{tyre.brand_name || '—'}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-muted-foreground">Serial:</span>
                                                        <span className="font-mono text-xs">{tyre.serial_number || '—'}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-muted-foreground">Remaining:</span>
                                                        <span className="font-medium">
                                                            {tyre.estimated_remaining_percentage !== null 
                                                                ? `${tyre.estimated_remaining_percentage.toFixed(1)}%` 
                                                                : '—'}
                                                        </span>
                                                    </div>
                                                    {tyre.latest_inspection && (
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">Inspection:</span>
                                                            <span>{tyre.latest_inspection.inspection_date || '—'}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Detail Panel */}
                    <div className="lg:col-span-1">
                        <Card className="sticky top-4">
                            <CardHeader>
                                <CardTitle>Selected Position</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {selectedTyre ? (
                                    <TyreDetailPanel tyre={selectedTyre} />
                                ) : (
                                    <div className="py-8 text-center text-muted-foreground">
                                        Click a tyre position on the map to view details
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Reading Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tyre Reading Summary</CardTitle>
                        <CardDescription>All tyres on this vehicle with their current status</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-2 text-left text-sm font-medium">Position</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Tyre Code</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Brand</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Used KM</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Remaining %</th>
                                        <th className="px-4 py-2 text-left text-sm font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tyres.map((tyre) => (
                                        <tr key={tyre.id} className="border-b hover:bg-muted/50">
                                            <td className="px-4 py-2 text-sm">{tyre.position_display}</td>
                                            <td className="px-4 py-2 text-sm font-medium">{tyre.tyre_code}</td>
                                            <td className="px-4 py-2 text-sm">{tyre.brand_name || '—'}</td>
                                            <td className="px-4 py-2 text-sm">
                                                {tyre.total_used_km !== null ? tyre.total_used_km.toLocaleString() : '—'}
                                            </td>
                                            <td className="px-4 py-2 text-sm">
                                                {tyre.estimated_remaining_percentage !== null 
                                                    ? `${tyre.estimated_remaining_percentage.toFixed(1)}%` 
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-2 text-sm">
                                                <Badge variant={getStatusVariant(tyre.status_color)}>
                                                    {tyre.status}
                                                </Badge>
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

function SummaryCard({ 
    label, 
    value, 
    icon, 
    variant 
}: { 
    label: string; 
    value: string | number; 
    icon: React.ReactNode; 
    variant: 'default' | 'success' | 'warning' | 'danger' | 'outline';
}) {
    const variantStyles = {
        default: 'border-border bg-card',
        success: 'border-green-200 bg-green-50',
        warning: 'border-yellow-200 bg-yellow-50',
        danger: 'border-red-200 bg-red-50',
        outline: 'border-gray-200 bg-gray-50',
    };

    return (
        <Card className={cn('border', variantStyles[variant])}>
            <CardContent className="flex items-center gap-3 p-4">
                <div className={cn(
                    'rounded-full p-2',
                    variant === 'success' && 'bg-green-100 text-green-600',
                    variant === 'warning' && 'bg-yellow-100 text-yellow-600',
                    variant === 'danger' && 'bg-red-100 text-red-600',
                    variant === 'default' && 'bg-blue-100 text-blue-600',
                    variant === 'outline' && 'bg-gray-100 text-gray-600'
                )}>
                    {icon}
                </div>
                <div>
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="text-lg font-semibold">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function TyreDetailPanel({ tyre }: { tyre: Tyre }) {
    return (
        <div className="space-y-4">
            {/* Tyre Identity */}
            <div className="space-y-2">
                <h4 className="text-sm font-semibold">Tyre Identity</h4>
                <div className="space-y-1 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Code:</span>
                        <span className="font-medium">{tyre.tyre_code}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Serial:</span>
                        <span className="font-medium">{tyre.serial_number}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Brand:</span>
                        <span>{tyre.brand_name || '—'}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Size:</span>
                        <span>{tyre.size_label || '—'}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Position:</span>
                        <span className="font-medium">{tyre.position_display}</span>
                    </div>
                </div>
            </div>

            {/* Baseline Status */}
            <div className="space-y-2">
                <h4 className="text-sm font-semibold">Baseline Status</h4>
                {tyre.has_baseline ? (
                    <div className="space-y-2">
                        <Badge variant="outline" className="w-full justify-center bg-green-50 text-green-700 border-green-200">
                            <CheckCircle className="mr-2 h-3 w-3" />
                            Baseline Set
                        </Badge>
                        <div className="space-y-1 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Baseline %:</span>
                                <span className="font-medium">{tyre.baseline_percentage}%</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Expected Life:</span>
                                <span>{tyre.expected_life_km?.toLocaleString()} KM</span>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-2">
                        <Badge variant="outline" className="w-full justify-center bg-yellow-50 text-yellow-700 border-yellow-200">
                            <AlertTriangle className="mr-2 h-3 w-3" />
                            Baseline Required
                        </Badge>
                        <p className="text-xs text-muted-foreground">
                            Set baseline to track tyre usage
                        </p>
                    </div>
                )}
            </div>

            {/* Usage Status */}
            <div className="space-y-2">
                <h4 className="text-sm font-semibold">Usage Status</h4>
                <Badge variant={getStatusVariant(tyre.status_color)} className="w-full justify-center">
                    {tyre.status}
                </Badge>
                <div className="space-y-1 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Used KM:</span>
                        <span>{tyre.total_used_km !== null ? tyre.total_used_km.toLocaleString() : '—'}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Remaining:</span>
                        <span className="font-medium">
                            {tyre.estimated_remaining_percentage !== null 
                                ? `${tyre.estimated_remaining_percentage.toFixed(1)}%` 
                                : '—'}
                        </span>
                    </div>
                </div>
            </div>

            {/* Latest Inspection */}
            {tyre.latest_inspection && (
                <div className="space-y-2">
                    <h4 className="text-sm font-semibold">Latest Inspection</h4>
                    <div className="space-y-1 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Tread Depth:</span>
                            <span>{tyre.latest_inspection.tread_depth || '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Pressure:</span>
                            <span>{tyre.latest_inspection.pressure || '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Condition:</span>
                            <span>{tyre.latest_inspection.condition || '—'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Date:</span>
                            <span>{tyre.latest_inspection.inspection_date || '—'}</span>
                        </div>
                    </div>
                </div>
            )}

            {/* Actions */}
            <div className="space-y-2 pt-2">
                {!tyre.has_baseline && (
                    <Button asChild className="w-full" size="sm">
                        <Link href={tyre.create_baseline_url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Set Baseline KM
                        </Link>
                    </Button>
                )}
                {tyre.has_baseline && (
                    <Button asChild className="w-full" size="sm" variant="outline">
                        <Link href={route('tyres.baselines.show', tyre.id)}>
                            <Settings className="mr-2 h-4 w-4" />
                            View/Edit Baseline
                        </Link>
                    </Button>
                )}
                <Button asChild className="w-full" variant="outline" size="sm">
                    <Link href={tyre.view_url}>
                        View Tyre Details
                    </Link>
                </Button>
                <Button asChild className="w-full" size="sm" variant="outline">
                    <Link href={tyre.create_movement_url}>
                        Create Movement
                    </Link>
                </Button>
            </div>
        </div>
    );
}

function convertToKonvaSlots(tyres: Tyre[], vehicleType: Vehicle['vehicle_type'], assetType: string): KonvaSlot[] {
    // If vehicle type has layout_json with positions, use that
    if (vehicleType?.layout_json?.positions && vehicleType.layout_json.positions.length > 0) {
        return vehicleType.layout_json.positions.map(position => {
            const tyre = tyres.find(t => t.current_position_code === position.code);
            
            return {
                code: position.code,
                display_code: position.display_code,
                label: position.label,
                axle: position.axle,
                side: position.side,
                tyre_code: tyre?.tyre_code || null,
                color: tyre?.status_color || 'gray',
                estimated_remaining_percentage: tyre?.estimated_remaining_percentage,
                usage_status: tyre?.status,
                baseline_required: !tyre?.has_baseline,
            };
        });
    }

    // Otherwise, use the existing Konva base slots structure
    // This is a fallback for vehicle types without layout_json
    const isTrailer = assetType === 'trailer';
    
    // Map tyres to their position codes
    const tyreByPosition = new Map(tyres.map(t => [t.current_position_code, t]));
    
    // Generate base slot codes based on asset type
    const baseSlotCodes = isTrailer 
        ? ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'X']
        : ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'W', 'K', 'L', 'M', 'N', 'X', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];
    
    return baseSlotCodes.map(code => {
        const tyre = tyreByPosition.get(code);
        
        return {
            code,
            display_code: code,
            label: code,
            axle: null,
            side: null,
            tyre_code: tyre?.tyre_code || null,
            color: tyre?.status_color || 'gray',
            estimated_remaining_percentage: tyre?.estimated_remaining_percentage,
            usage_status: tyre?.status,
            baseline_required: !tyre?.has_baseline,
        };
    });
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
