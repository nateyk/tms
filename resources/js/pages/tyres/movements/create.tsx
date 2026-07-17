import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreMovementFormFields } from "@/components/tyres/tyre-movement-form-fields";
import { TyreFormShell } from "@/components/tyres/tyre-form-shell";
import { Button } from "@/components/ui/button";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { useState } from "react";

type FormOptions = {
    tyres: Parameters<typeof TyreMovementFormFields>[0]["tyres"];
    stores: Parameters<typeof TyreMovementFormFields>[0]["stores"];
    powerVehicles: Parameters<typeof TyreMovementFormFields>[0]["powerVehicles"];
    trailers: Parameters<typeof TyreMovementFormFields>[0]["trailers"];
    destinationTypes: Parameters<typeof TyreMovementFormFields>[0]["destinationTypes"];
    destinationTargets: Parameters<typeof TyreMovementFormFields>[0]["destinationTargets"];
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
    destinationTargets,
    prefilled,
}: FormOptions) {
    const form = useForm({
        tyre_id: prefilled.tyre_id ? Number(prefilled.tyre_id) : (null as number | null),
        movement_date: prefilled.movement_date ?? new Date().toISOString().slice(0, 10),
        to_location_type: prefilled.to_location_type ?? "",
        to_location_id: prefilled.to_location_id ?? (null as number | null),
        to_position_code: prefilled.to_position_code ?? "",
        from_odometer: null as number | null,
        to_odometer: null as number | null,
        reason: prefilled.reason ?? "",
        notes: "",
    });
    const [selectedTyreId, setSelectedTyreId] = useState<number | null>(form.data.tyre_id);
    const { data, setData, processing, errors } = form;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.transform((payload) => ({
            ...payload,
            tyre_id: selectedTyreId ? Number(selectedTyreId) : null,
        }));
        form.post(route("tyres.movements.store"));
    };

    return (
        <AuthenticatedLayout header="New Tyre Movement">
            <Head title="New Tyre Movement" />

            <form onSubmit={submit}>
                <TyreFormShell
                    title="New tyre movement"
                    description="Create a movement voucher. The tyre location changes only after completion."
                    backHref={route("tyres.movements.index")}
                    backLabel="Back to Movements"
                    footer={(
                        <>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.movements.index")}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Saving..." : "Save draft"}
                            </Button>
                        </>
                    )}
                >
                    <TyreMovementFormFields
                        data={data}
                        setData={setData}
                        errors={errors}
                        tyres={tyres}
                        stores={stores}
                        powerVehicles={powerVehicles}
                        trailers={trailers}
                        destinationTypes={destinationTypes}
                        destinationTargets={destinationTargets}
                        onTyreSelected={setSelectedTyreId}
                    />
                </TyreFormShell>
            </form>
        </AuthenticatedLayout>
    );
}
