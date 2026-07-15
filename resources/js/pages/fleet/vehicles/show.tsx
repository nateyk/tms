import { VehicleTyreMapPanel, TyreMapPayload } from "@/components/fleet/tyre-map-panel";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { WorkflowActionCard, WorkflowHeader, WorkflowSteps } from "@/components/workflow/workflow-ui";
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head, Link } from "@inertiajs/react";
import { ClipboardCheck, FileText, Gauge, Pencil, Route } from "lucide-react";

type VehicleSummary = {
    id: number;
    vehicle_code: string;
    display_code: string;
    plate_number: string | null;
    asset_type_label: string;
    vehicle_type_name: string | null;
    status_label: string;
    current_location_name: string | null;
    odometer: number | null;
    attached_trailer_code?: string | null;
    attached_power_code?: string | null;
};

export default function VehiclesShow({
    vehicle,
    tyreMap,
}: {
    vehicle: VehicleSummary;
    tyreMap: TyreMapPayload;
}) {
    const counts = tyreMap.counts;
    const hasCurrentKm = vehicle.odometer !== null && vehicle.odometer !== undefined;
    const mountedCount = counts?.mounted ?? 0;
    const totalPositions = counts?.total ?? 0;
    const emptyCount = counts?.empty ?? 0;
    const vehicleDescription = `${vehicle.vehicle_type_name ?? "Vehicle"}${vehicle.plate_number ? ` - ${vehicle.plate_number}` : ""}`;

    return (
        <AuthenticatedLayout header={vehicle.display_code}>
            <Head title={vehicle.display_code} />

            <div className="space-y-6">
                <WorkflowHeader
                    title={vehicle.display_code}
                    description={vehicleDescription}
                    backHref={route("fleet.vehicles.index")}
                    backLabel="Back to Vehicles"
                    badge={vehicle.status_label}
                    actions={
                        <>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route("fleet.vehicles.edit", vehicle.id)}>
                                    <Pencil className="mr-2 h-4 w-4" />
                                    Edit
                                </Link>
                            </Button>
                            <Button size="sm" asChild>
                                <Link href={route("fleet.vehicles.odometer", vehicle.id)}>
                                    <Gauge className="mr-2 h-4 w-4" />
                                    Record KM
                                </Link>
                            </Button>
                        </>
                    }
                />

                <Card>
                    <CardContent className="space-y-4 p-4 sm:p-5">
                        <div className="flex flex-wrap gap-2">
                            <Badge>{vehicle.asset_type_label}</Badge>
                            {hasCurrentKm ? (
                                <Badge variant="secondary">{vehicle.odometer?.toLocaleString()} KM</Badge>
                            ) : (
                                <Badge variant="destructive">KM needed</Badge>
                            )}
                            {vehicle.attached_trailer_code && (
                                <Badge variant="outline">Trailer: {vehicle.attached_trailer_code}</Badge>
                            )}
                            {vehicle.attached_power_code && (
                                <Badge variant="outline">Power: {vehicle.attached_power_code}</Badge>
                            )}
                        </div>

                        <WorkflowSteps
                            steps={[
                                { label: "Record KM", done: hasCurrentKm },
                                { label: "Inspect map", done: mountedCount > 0 },
                                { label: "Set baselines", done: emptyCount === 0 },
                                { label: "Move or report", done: false },
                            ]}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-3">
                    <WorkflowActionCard
                        title="Tyre Positions"
                        description="Mounted tyres on this unit."
                        value={`${mountedCount}/${totalPositions}`}
                        href={route("tyres.reading-monitoring.show", vehicle.id)}
                        actionLabel="Open monitor"
                        tone={emptyCount > 0 ? "warning" : "success"}
                        icon={ClipboardCheck}
                    />
                    <WorkflowActionCard
                        title="Current KM"
                        description="Used for tyre usage and movement completion."
                        value={hasCurrentKm ? vehicle.odometer?.toLocaleString() : "Needed"}
                        href={route("fleet.vehicles.odometer", vehicle.id)}
                        actionLabel="Update KM"
                        tone={hasCurrentKm ? "success" : "warning"}
                        icon={Gauge}
                    />
                    <WorkflowActionCard
                        title="Tyre Work"
                        description="Inspect positions, mount tyres, and review status."
                        href={route("tyres.reading-monitoring.show", vehicle.id)}
                        actionLabel="Open tyre work"
                        tone="info"
                        icon={Route}
                    />
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <a
                            href={route("vouchers.vehicle.tyre-status.pdf", vehicle.id)}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <FileText className="mr-2 h-4 w-4" />
                            Tyre status PDF
                        </a>
                    </Button>
                </div>

                <VehicleTyreMapPanel
                    vehicle={vehicle}
                    tyreMap={tyreMap}
                />
            </div>
        </AuthenticatedLayout>
    );
}
