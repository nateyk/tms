import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Head, Link } from "@inertiajs/react";
import { Eye, Truck, ArrowRight } from "lucide-react";

type Vehicle = {
    type: 'combination' | 'standalone_power' | 'standalone_trailer';
    id: number;
    vehicle_code: string;
    plate_number: string;
    display_code: string;
    vehicle_type_name: string | null;
    odometer?: number | null;
    power_vehicle?: {
        id: number;
        vehicle_code: string;
        plate_number: string;
        display_code: string;
        vehicle_type_name: string | null;
        odometer: number | null;
    };
    trailer?: {
        id: number;
        vehicle_code: string;
        plate_number: string;
        display_code: string;
        vehicle_type_name: string | null;
    };
};

export default function ReadingMonitoringIndex({
    vehicles,
}: {
    vehicles: Vehicle[];
}) {
    return (
        <AuthenticatedLayout header="Reading Monitoring">
            <Head title="Reading Monitoring" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Vehicle Selection</h2>
                        <p className="text-muted-foreground">Select a vehicle to view tyre readings and usage</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {vehicles.map((vehicle) => (
                        <Link
                            key={vehicle.id}
                            href={route("tyres.reading-monitoring.show", vehicle.id)}
                            className="group"
                        >
                            <Card className="h-full transition-all hover:shadow-md hover:border-primary/50">
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex items-center gap-2">
                                            <Truck className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <h3 className="font-semibold text-sm">
                                                    {vehicle.type === 'combination' 
                                                        ? vehicle.power_vehicle?.display_code 
                                                        : vehicle.display_code}
                                                </h3>
                                                <p className="text-xs text-muted-foreground">
                                                    {vehicle.type === 'combination' 
                                                        ? vehicle.power_vehicle?.vehicle_type_name 
                                                        : vehicle.vehicle_type_name}
                                                </p>
                                            </div>
                                        </div>
                                        <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                    
                                    <div className="space-y-1 text-xs">
                                        {vehicle.type === 'combination' && vehicle.power_vehicle && (
                                            <>
                                                <div className="flex justify-between">
                                                    <span className="text-muted-foreground">Odometer:</span>
                                                    <span className="font-medium">
                                                        {vehicle.power_vehicle.odometer 
                                                            ? vehicle.power_vehicle.odometer.toLocaleString() + " km"
                                                            : "—"}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-muted-foreground">Trailer:</span>
                                                    <span className="font-medium">
                                                        {vehicle.trailer?.display_code || "—"}
                                                    </span>
                                                </div>
                                            </>
                                        )}
                                        {vehicle.type === 'standalone_power' && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Odometer:</span>
                                                <span className="font-medium">
                                                    {vehicle.odometer 
                                                        ? vehicle.odometer.toLocaleString() + " km"
                                                        : "—"}
                                                </span>
                                            </div>
                                        )}
                                        {vehicle.type === 'standalone_trailer' && (
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Type:</span>
                                                <span className="font-medium">Trailer</span>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {vehicles.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Truck className="h-12 w-12 text-muted-foreground mb-4" />
                            <p className="text-muted-foreground">No active vehicles found</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
