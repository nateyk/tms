import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VehicleOdometerForm } from "@/components/fleet/vehicle-odometer-form";
import { OdometerReadingHistory } from "@/components/fleet/odometer-reading-history";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";

type VehicleOdometerData = {
    id: number;
    vehicle_code: string;
    plate_number: string | null;
    display_code: string;
    current_odometer: number | null;
    latest_reading: {
        id: number;
        odometer: number;
        reading_date: string;
        source: string;
        recorded_by_name: string | null;
        notes: string | null;
        created_at: string;
    } | null;
    readings: Array<{
        id: number;
        odometer: number;
        reading_date: string;
        source: string;
        source_id: number | null;
        recorded_by: number | null;
        recorded_by_name: string | null;
        notes: string | null;
        created_at: string;
    }>;
};

export default function VehicleOdometer({ vehicle }: { vehicle: VehicleOdometerData }) {
    const { data, setData, put, processing, errors } = useForm({
        odometer: vehicle.current_odometer || "",
        notes: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route("fleet.vehicles.odometer.update", vehicle.id));
    };

    const handleChange = (field: string, value: string | number) => {
        setData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <AuthenticatedLayout header={`Vehicle Odometer - ${vehicle.display_code}`}>
            <Head title={`Vehicle Odometer - ${vehicle.display_code}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route("fleet.vehicles.show", vehicle.id)}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Vehicle Odometer</h1>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Update Odometer</CardTitle>
                            <CardDescription>
                                Record the current odometer reading for {vehicle.display_code}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <VehicleOdometerForm
                                    data={data}
                                    errors={errors}
                                    currentOdometer={vehicle.current_odometer}
                                    onDataChange={handleChange}
                                />

                                <div className="flex justify-end gap-2">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={route("fleet.vehicles.show", vehicle.id)}>Cancel</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Updating..." : "Update Odometer"}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <OdometerReadingHistory readings={vehicle.readings} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
