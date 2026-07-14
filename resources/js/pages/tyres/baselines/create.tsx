import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreBaselineFormFields } from "@/components/tyres/tyre-baseline-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft } from "lucide-react";

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

            <div className="space-y-6">
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route("tyres.baselines.index")}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold">Create Tyre Baseline</h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Baseline Information</CardTitle>
                        <CardDescription>
                            Set the initial baseline for tracking tyre usage and remaining life.
                            Only tyres without an existing baseline can be selected.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <TyreBaselineFormFields
                                data={data}
                                errors={errors}
                                tyres={tyres}
                                prefilled={prefilled}
                                onDataChange={handleChange}
                            />

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" asChild>
                                    <Link href={route("tyres.baselines.index")}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Creating..." : "Create Baseline"}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
