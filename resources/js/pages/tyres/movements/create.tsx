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
    prefilled: Partial<{
        tyre_id: number;
        movement_date: string;
        to_location_type: string;
        to_location_id: number;
        to_position_code: string;
        reason: string;
    }>;
};

export default function MovementsCreate({
    tyres,
    stores,
    powerVehicles,
    trailers,
    destinationTypes,
    prefilled,
}: FormOptions) {
    const { data, setData, post, processing, errors } = useForm({
        tyre_id: prefilled.tyre_id ?? (null as number | null),
        movement_date: prefilled.movement_date ?? new Date().toISOString().slice(0, 10),
        to_location_type: prefilled.to_location_type ?? "",
        to_location_id: prefilled.to_location_id ?? (null as number | null),
        to_position_code: prefilled.to_position_code ?? "",
        from_odometer: null as number | null,
        to_odometer: null as number | null,
        reason: prefilled.reason ?? "",
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("tyres.movements.store"));
    };

    return (
        <AuthenticatedLayout header="New Tyre Movement">
            <Head title="New Tyre Movement" />

            <Card className="max-w-4xl">
                <CardHeader>
                    <CardTitle>Tyre movement request</CardTitle>
                    <CardDescription>
                        Select the tyre, confirm its source, then choose the destination position.
                        Draft → Submit → Check → Approve → Complete. Inventory updates only after
                        completion.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <TyreMovementFormFields
                            data={data}
                            setData={setData}
                            errors={errors}
                            tyres={tyres}
                            stores={stores}
                            powerVehicles={powerVehicles}
                            trailers={trailers}
                            destinationTypes={destinationTypes}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save draft
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.movements.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
