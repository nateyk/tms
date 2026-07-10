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
};

export default function TrailerTransfersCreate({
    trailers,
    powerVehicles,
    locations,
}: FormOptions) {
    const { data, setData, post, processing, errors } = useForm({
        trailer_vehicle_id: null as number | null,
        from_power_vehicle_id: null as number | null,
        to_power_vehicle_id: null as number | null,
        transfer_date: new Date().toISOString().slice(0, 10),
        from_odometer: null as number | null,
        to_odometer: null as number | null,
        location_id: null as number | null,
        reason: "",
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("fleet.trailer-transfers.store"));
    };

    return (
        <AuthenticatedLayout header="New Trailer Transfer">
            <Head title="New Trailer Transfer" />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Trailer transfer request</CardTitle>
                    <CardDescription>
                        Reassign a trailer from one power unit to another. Tyres remain on the
                        trailer — only the power–trailer combination changes on completion.
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
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save draft
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("fleet.trailer-transfers.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
