import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreBaselineFormFields } from "@/components/tyres/tyre-baseline-form-fields";
import { TyreFormShell } from "@/components/tyres/tyre-form-shell";
import { Button } from "@/components/ui/button";
import { Head, Link, useForm } from "@inertiajs/react";

type BaselineFormData = {
    tyre_id?: number | string;
    baseline_location_type?: string | null;
    baseline_location_id?: number | string | null;
    baseline_position_code?: string | null;
    baseline_percentage?: number | string;
    expected_life_km?: number | string;
    baseline_odometer?: number | string;
    baseline_date?: string;
    notes?: string;
};

type TyreOption = {
    id: number;
    tyre_code: string;
    serial_number: string;
    current_location_type: string | null;
    current_location_id: number | null;
    current_position_code: string | null;
    location_display: string;
    current_vehicle_odometer?: number | null;
};

export default function BaselineCreate({
    tyres,
    prefilled,
}: {
    tyres: TyreOption[];
    prefilled: {
        id: number;
        tyre_code: string;
        current_location_type: string | null;
        current_location_id: number | null;
        current_position_code: string | null;
        location_display: string;
        current_vehicle_odometer?: number | null;
    } | null;
}) {
    const { data, setData, post, processing, errors } = useForm<BaselineFormData>({
        tyre_id: prefilled?.id,
        baseline_location_type: prefilled?.current_location_type ?? null,
        baseline_location_id: prefilled?.current_location_id ?? null,
        baseline_position_code: prefilled?.current_position_code ?? null,
        baseline_percentage: 100,
        expected_life_km: 100000,
        baseline_odometer: prefilled?.current_vehicle_odometer ?? "",
        baseline_date: new Date().toISOString().split('T')[0],
        notes: "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("tyres.baselines.store"));
    };

    const handleChange = (field: string, value: string | number | null) => {
        setData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <AuthenticatedLayout header="Create Baseline">
            <Head title="Create Baseline" />

            <form onSubmit={handleSubmit}>
                <TyreFormShell
                    title="Create tyre baseline"
                    description="Set tyre condition percentage and expected life. Truck KM is used automatically when the tyre is already mounted."
                    backHref={route("tyres.baselines.index")}
                    backLabel="Back to Baselines"
                    footer={(
                        <>
                            <Button type="button" variant="outline" asChild>
                                <Link href={route("tyres.baselines.index")}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Creating..." : "Create Baseline"}
                            </Button>
                        </>
                    )}
                >
                    <TyreBaselineFormFields
                        data={data}
                        errors={errors}
                        tyres={tyres}
                        prefilled={prefilled}
                        onDataChange={handleChange}
                    />
                </TyreFormShell>
            </form>
        </AuthenticatedLayout>
    );
}
