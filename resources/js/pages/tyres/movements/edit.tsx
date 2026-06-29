import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreMovementFormFields } from "@/components/tyres/tyre-movement-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type FormOptions = {
    tyres: Parameters<typeof TyreMovementFormFields>[0]["tyres"];
    stores: Parameters<typeof TyreMovementFormFields>[0]["stores"];
    powerVehicles: Parameters<typeof TyreMovementFormFields>[0]["powerVehicles"];
    trailers: Parameters<typeof TyreMovementFormFields>[0]["trailers"];
    destinationTypes: Parameters<typeof TyreMovementFormFields>[0]["destinationTypes"];
    movement: {
        id: number;
        tyre_id: number;
        movement_date: string;
        to_location_type: string;
        to_location_id: number | null;
        to_position_code: string;
        from_odometer: number | null;
        to_odometer: number | null;
        reason: string;
        notes: string;
        from_location_type: string | null;
        from_location_id: number | null;
        from_position_code: string | null;
        movement_type_label: string;
    };
};

export default function MovementsEdit({
    tyres,
    stores,
    powerVehicles,
    trailers,
    destinationTypes,
    movement,
}: FormOptions) {
    const selectedTyre = tyres.find((tyre) => tyre.id === movement.tyre_id);

    const { data, setData, put, processing, errors } = useForm({
        movement_date: movement.movement_date,
        to_location_type: movement.to_location_type,
        to_location_id: movement.to_location_id,
        to_position_code: movement.to_position_code,
        from_odometer: movement.from_odometer,
        to_odometer: movement.to_odometer,
        reason: movement.reason,
        notes: movement.notes,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("tyres.movements.update", movement.id));
    };

    return (
        <AuthenticatedLayout header="Edit Movement">
            <Head title="Edit Movement" />

            <Card className="max-w-4xl">
                <CardHeader>
                    <CardTitle>Edit movement draft</CardTitle>
                    <CardDescription>
                        Update destination details before submitting the voucher.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <TyreMovementFormFields
                            data={{
                                tyre_id: movement.tyre_id,
                                ...data,
                            }}
                            setData={setData}
                            errors={errors}
                            tyres={tyres}
                            stores={stores}
                            powerVehicles={powerVehicles}
                            trailers={trailers}
                            destinationTypes={destinationTypes}
                            readOnlyTyre
                            sourceInfo={{
                                location_label: selectedTyre?.source_label ?? "—",
                                position_label: movement.from_position_code ?? "—",
                                movement_type_label: movement.movement_type_label,
                            }}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.movements.show", movement.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
