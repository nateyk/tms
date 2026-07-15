import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { WorkflowHeader } from "@/components/workflow/workflow-ui";
import {
    AssetTypeOption,
    LayoutPresetOption,
    VehicleTypeFormFields,
    VehicleTypeFormData,
} from "@/components/fleet/vehicle-type-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function VehicleTypesEdit({
    vehicleType,
    assetTypes,
    layoutPresets,
}: {
    vehicleType: VehicleTypeFormData & { id: number };
    assetTypes: AssetTypeOption[];
    layoutPresets: LayoutPresetOption[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: vehicleType.name,
        asset_type: vehicleType.asset_type,
        status: vehicleType.status,
        layout_preset: vehicleType.layout_preset,
        tyre_count: vehicleType.tyre_count,
        axle_count: vehicleType.axle_count,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("fleet.vehicle-types.update", vehicleType.id));
    };

    return (
        <AuthenticatedLayout header="Edit Vehicle Type">
            <Head title="Edit Vehicle Type" />

            <div className="space-y-6">
                <WorkflowHeader
                    title="Edit Vehicle Type"
                    description="Update the name or regenerate the tyre layout from a preset."
                    backHref={route("fleet.vehicle-types.index")}
                    backLabel="Back to Vehicle Types"
                />

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Edit vehicle type</CardTitle>
                        <CardDescription>
                            Update name, asset type, or regenerate layout from a preset.
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
                                    Save changes
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
