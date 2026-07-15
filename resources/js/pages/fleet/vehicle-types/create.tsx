import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import {
    AssetTypeOption,
    defaultVehicleTypeForm,
    LayoutPresetOption,
    VehicleTypeFormFields,
} from "@/components/fleet/vehicle-type-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function VehicleTypesCreate({
    assetTypes,
    layoutPresets,
    defaultPreset,
}: {
    assetTypes: AssetTypeOption[];
    layoutPresets: LayoutPresetOption[];
    defaultPreset: string;
}) {
    const { data, setData, post, processing, errors } = useForm(
        defaultVehicleTypeForm(layoutPresets, defaultPreset),
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("fleet.vehicle-types.store"));
    };

    return (
        <AuthenticatedLayout header="Create Vehicle Type">
            <Head title="Create Vehicle Type" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Create Vehicle Type"
                    description="Pick the closest tyre layout preset first, then name it for your fleet."
                    backHref={route("fleet.vehicle-types.index")}
                    backLabel="Back to Vehicle Types"
                />

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Create vehicle type</CardTitle>
                        <CardDescription>
                            Define asset type and tyre layout preset for fleet vehicles.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <VehicleTypeFormFields
                                errors={errors}
                                data={data}
                                setData={setData}
                                assetTypes={assetTypes}
                                layoutPresets={layoutPresets}
                            />
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create vehicle type
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={route("fleet.vehicle-types.index")}>Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
