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

type Baseline = {
    id: number;
    tyre_id: number;
    tyre_code: string;
    baseline_location_type: string;
    baseline_location_id: number;
    baseline_position_code: string | null;
    baseline_odometer: number | null;
    current_vehicle_odometer?: number | null;
    baseline_percentage: number;
    expected_life_km: number;
    baseline_date: string;
    notes: string | null;
};

export default function BaselineEdit({ baseline }: { baseline: Baseline }) {
    const { data, setData, put, processing, errors } = useForm<BaselineFormData>({
        tyre_id: baseline.tyre_id,
        baseline_location_type: baseline.baseline_location_type,
        baseline_location_id: baseline.baseline_location_id,
        baseline_position_code: baseline.baseline_position_code,
        baseline_percentage: baseline.baseline_percentage,
        expected_life_km: baseline.expected_life_km,
        baseline_odometer: baseline.baseline_odometer ?? baseline.current_vehicle_odometer ?? "",
        baseline_date: baseline.baseline_date,
        notes: baseline.notes || "",
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route("tyres.baselines.update", baseline.id));
    };

    const handleChange = (field: string, value: string | number | null) => {
        setData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <AuthenticatedLayout header="Edit Baseline">
            <Head title={`Edit Baseline - ${baseline.tyre_code}`} />

            <form onSubmit={handleSubmit}>
                <TyreFormShell
                    title={`Edit baseline - ${baseline.tyre_code}`}
                    description="Adjust tyre condition percentage, expected life, date, and notes."
                    backHref={route("tyres.baselines.show", baseline.id)}
                    backLabel="Back to Baseline"
                    footer={(
                        <>
                            <Button type="button" variant="outline" asChild>
                                <Link href={route("tyres.baselines.show", baseline.id)}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Updating..." : "Update Baseline"}
                            </Button>
                        </>
                    )}
                >
                    <TyreBaselineFormFields
                        data={data}
                        errors={errors}
                        tyres={[]}
                        prefilled={{
                            id: baseline.tyre_id,
                            tyre_code: baseline.tyre_code,
                            current_location_type: baseline.baseline_location_type,
                            current_location_id: baseline.baseline_location_id,
                            current_position_code: baseline.baseline_position_code,
                            location_display: `${baseline.baseline_location_type} - ${baseline.baseline_position_code || 'N/A'}`,
                            current_vehicle_odometer: baseline.current_vehicle_odometer,
                        }}
                        onDataChange={handleChange}
                    />
                </TyreFormShell>
            </form>
        </AuthenticatedLayout>
    );
}
