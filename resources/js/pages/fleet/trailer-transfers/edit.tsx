import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TrailerTransferFormFields } from "@/components/fleet/trailer-transfer-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type FormOptions = {
    trailers: Parameters<typeof TrailerTransferFormFields>[0]["trailers"];
    powerVehicles: Parameters<typeof TrailerTransferFormFields>[0]["powerVehicles"];
    locations: Parameters<typeof TrailerTransferFormFields>[0]["locations"];
    transfer: {
        id: number;
        trailer_vehicle_id: number;
        from_power_vehicle_id: number | null;
        to_power_vehicle_id: number | null;
        transfer_date: string;
        from_odometer: number | null;
        to_odometer: number | null;
        location_id: number | null;
        reason: string;
        notes: string;
    };
};

export default function TrailerTransfersEdit({
    trailers,
    powerVehicles,
    locations,
    transfer,
}: FormOptions) {
    const { data, setData, put, processing, errors } = useForm({
        trailer_vehicle_id: transfer.trailer_vehicle_id,
        from_power_vehicle_id: transfer.from_power_vehicle_id,
        to_power_vehicle_id: transfer.to_power_vehicle_id,
        transfer_date: transfer.transfer_date,
        from_odometer: transfer.from_odometer,
        to_odometer: transfer.to_odometer,
        location_id: transfer.location_id,
        reason: transfer.reason,
        notes: transfer.notes,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("fleet.trailer-transfers.update", transfer.id));
    };

    return (
        <AuthenticatedLayout header="Edit Trailer Transfer">
            <Head title="Edit Trailer Transfer" />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Edit transfer draft</CardTitle>
                    <CardDescription>
                        Update transfer details before submitting the voucher.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <TrailerTransferFormFields
                            data={data}
                            setData={setData}
                            errors={errors}
                            trailers={trailers}
                            powerVehicles={powerVehicles}
                            locations={locations}
                            readOnlyTrailer
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("fleet.trailer-transfers.show", transfer.id)}>
                                    Cancel
                                </Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
