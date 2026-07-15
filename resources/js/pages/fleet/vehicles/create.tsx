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
    attachablePowerVehicles: { id: number; label: string }[];
    attachableTrailers: { id: number; label: string }[];
};

export default function VehiclesCreate({
    assetTypes,
    vehicleStatuses,
    vehicleTypes,
    locations,
    attachablePowerVehicles,
    attachableTrailers,
}: FormOptions) {
    const defaultType = vehicleTypes[0];

    const { data, setData, post, processing, errors } = useForm({
        vehicle_code: "",
        plate_number: "",
        chassis_number: "",
        engine_number: "",
        asset_type: defaultType?.asset_type ?? "power_vehicle",
        vehicle_type_id: defaultType?.id ?? null,
        status: "active",
        current_location_id: null as number | null,
        manufacture_year: null as number | null,
        odometer: null as number | null,
        attached_power_vehicle_id: null as number | null,
        attached_trailer_vehicle_id: null as number | null,
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("fleet.vehicles.store"));
    };

    return (
        <AuthenticatedLayout header="Create Vehicle">
            <Head title="Create Vehicle" />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Create vehicle</CardTitle>
                    <CardDescription>Register a fleet asset with type and identity fields.</CardDescription>
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
                            attachablePowerVehicles={attachablePowerVehicles}
                            attachableTrailers={attachableTrailers}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Create vehicle
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("fleet.vehicles.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
