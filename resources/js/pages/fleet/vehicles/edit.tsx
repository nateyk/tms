import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { VehicleFormFields } from "@/components/fleet/vehicle-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type FormOptions = {
    assetTypes: { value: string; label: string }[];
    vehicleStatuses: { value: string; label: string }[];
    vehicleTypes: { id: number; name: string; asset_type: string }[];
    locations: { id: number; label: string }[];
    vehicle: {
        id: number;
        vehicle_code: string;
        plate_number: string;
        chassis_number: string;
        engine_number: string;
        asset_type: string;
        vehicle_type_id: number;
        status: string;
        current_location_id: number | null;
        manufacture_year: number | null;
        odometer: number | null;
        notes: string;
    };
};

export default function VehiclesEdit({
    assetTypes,
    vehicleStatuses,
    vehicleTypes,
    locations,
    vehicle,
}: FormOptions) {
    const { data, setData, put, processing, errors } = useForm({
        vehicle_code: vehicle.vehicle_code,
        plate_number: vehicle.plate_number,
        chassis_number: vehicle.chassis_number,
        engine_number: vehicle.engine_number,
        asset_type: vehicle.asset_type,
        vehicle_type_id: vehicle.vehicle_type_id,
        status: vehicle.status,
        current_location_id: vehicle.current_location_id,
        manufacture_year: vehicle.manufacture_year,
        odometer: vehicle.odometer,
        notes: vehicle.notes,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("fleet.vehicles.update", vehicle.id));
    };

    return (
        <AuthenticatedLayout header="Edit Vehicle">
            <Head title="Edit Vehicle" />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Edit vehicle</CardTitle>
                    <CardDescription>Update asset identity and setup details.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <VehicleFormFields
                            errors={errors}
                            data={data}
                            setData={setData}
                            assetTypes={assetTypes}
                            vehicleStatuses={vehicleStatuses}
                            vehicleTypes={vehicleTypes}
                            locations={locations}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("fleet.vehicles.show", vehicle.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
