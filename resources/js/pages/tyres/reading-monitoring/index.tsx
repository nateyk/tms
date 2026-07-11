import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/react";
import { Eye } from "lucide-react";

type Vehicle = {
    id: number;
    vehicle_code: string;
    plate_number: string;
    display_code: string;
    asset_type: string | null;
    vehicle_type_name: string | null;
    odometer: number | null;
    attached_trailer: {
        id: number;
        vehicle_code: string;
        display_code: string;
    } | null;
};

export default function ReadingMonitoringIndex({
    vehicles,
}: {
    vehicles: Vehicle[];
}) {
    return (
        <AuthenticatedLayout header="Tyre Reading Monitoring">
            <Head title="Tyre Reading Monitoring" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Reading Monitoring</CardTitle>
                        <CardDescription>
                            Select a vehicle to view tyre readings, usage, and remaining life.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {vehicles.map((vehicle) => (
                            <Card key={vehicle.id} className="hover:bg-accent/50 transition-colors">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-lg">{vehicle.display_code}</CardTitle>
                                    <CardDescription>
                                        {vehicle.vehicle_type_name || "Unknown Type"}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Odometer:</span>
                                            <span className="font-medium">
                                                {vehicle.odometer ? vehicle.odometer.toLocaleString() : "—"}
                                            </span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Asset Type:</span>
                                            <span className="font-medium capitalize">
                                                {vehicle.asset_type?.replace("_", " ") || "—"}
                                            </span>
                                        </div>
                                        {vehicle.attached_trailer && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Trailer:</span>
                                                <span className="font-medium">
                                                    {vehicle.attached_trailer.display_code}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full mt-4"
                                        asChild
                                    >
                                        <Link href={route("tyres.reading-monitoring.show", vehicle.id)}>
                                            <Eye className="h-4 w-4 mr-2" />
                                            View Tyres
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    {vehicles.length === 0 && (
                        <div className="text-center py-8 text-muted-foreground">
                            No active vehicles found.
                        </div>
                    )}
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
