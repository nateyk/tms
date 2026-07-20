import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreMovementFormFields } from "@/components/tyres/tyre-movement-form-fields";
import { TyreFormShell } from "@/components/tyres/tyre-form-shell";
import { Button } from "@/components/ui/button";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

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
        from_odometer: number | null;
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
        from_odometer: prefilled.from_odometer ?? (null as number | null),
        to_location_type: prefilled.to_location_type ?? "",
        to_location_id: prefilled.to_location_id ?? (null as number | null),
        to_position_code: prefilled.to_position_code ?? "",
        to_odometer: null as number | null,
        reason: prefilled.reason ?? "",
        notes: "",
    });
    const { data, setData, processing, errors } = form;
    const selectedTyre = tyres.find((tyre) => Number(tyre.id) === Number(data.tyre_id));
    const fromTyreDetail = Boolean(selectedTyre);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route("tyres.movements.store"));
    };

    const sourceInfo = selectedTyre ? {
        location_label: selectedTyre.source_label,
        position_label: selectedTyre.source_position_label ?? selectedTyre.current_position_code ?? "-",
        movement_type_label: "Current source",
    } : undefined;

    return (
        <AuthenticatedLayout header={fromTyreDetail ? `Move ${selectedTyre?.tyre_code}` : "New Tyre Movement"}>
            <Head title={fromTyreDetail ? `Move ${selectedTyre?.tyre_code}` : "New Tyre Movement"} />

            <form onSubmit={submit}>
                <TyreFormShell
                    title={fromTyreDetail ? `Move ${selectedTyre?.tyre_code}` : "New tyre movement"}
                    description={fromTyreDetail
                        ? `Move this tyre from ${selectedTyre?.source_label} at position ${selectedTyre?.source_position_label ?? selectedTyre?.current_position_code ?? "-"}. Choose the destination below.`
                        : "Create a movement voucher. The tyre location changes only after completion."}
                    backHref={fromTyreDetail ? route("tyres.show", selectedTyre?.id) : route("tyres.movements.index")}
                    backLabel={fromTyreDetail ? "Back to tyre" : "Back to Movements"}
                    maxWidth="max-w-5xl"
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
                        compact
                        onTyreSelected={(tyreId) => {
                            if (tyreId !== null) {
                                form.clearErrors("tyre_id");
                            }
                        }}
                        sourceInfo={sourceInfo}
                    />
                </TyreFormShell>
            </form>
        </AuthenticatedLayout>
    );
}
