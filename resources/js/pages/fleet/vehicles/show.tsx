import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VehicleTyreMapPanel, TyreMapPayload } from "@/components/fleet/tyre-map-panel";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/react";
import { FileText, Pencil } from "lucide-react";

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
    trailer,
    trailerTyreMap,
}: {
    vehicle: VehicleSummary;
    tyreMap: TyreMapPayload;
    trailer?: VehicleSummary | null;
    trailerTyreMap?: TyreMapPayload | null;
}) {
    return (
        <AuthenticatedLayout header={vehicle.display_code}>
            <Head title={vehicle.display_code} />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                        <div>
                            <CardTitle>{vehicle.display_code}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {vehicle.vehicle_type_name}
                                {vehicle.plate_number ? ` · ${vehicle.plate_number}` : ""}
                                {vehicle.odometer
                                    ? ` · ${vehicle.odometer.toLocaleString()} km`
                                    : ""}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge>{vehicle.asset_type_label}</Badge>
                            <Badge variant="secondary">{vehicle.status_label}</Badge>
                            {vehicle.attached_trailer_code && (
                                <Badge variant="outline">
                                    Trailer: {vehicle.attached_trailer_code}
                                </Badge>
                            )}
                            {vehicle.attached_power_code && (
                                <Badge variant="outline">
                                    Power: {vehicle.attached_power_code}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route("fleet.vehicles.edit", vehicle.id)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
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
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route("fleet.trailer-transfers.index")}>
                                Trailer transfers
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <VehicleTyreMapPanel
                    vehicle={vehicle}
                    tyreMap={tyreMap}
                    trailer={trailer}
                    trailerTyreMap={trailerTyreMap}
                />
            </div>
        </AuthenticatedLayout>
    );
}
