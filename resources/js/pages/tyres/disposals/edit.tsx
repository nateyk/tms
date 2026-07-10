import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreDisposalFormFields } from "@/components/tyres/tyre-disposal-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";

export default function DisposalsEdit({
    tyres,
    disposalReasons,
    disposal,
}: {
    tyres: Parameters<typeof TyreDisposalFormFields>[0]["tyres"];
    disposalReasons: Parameters<typeof TyreDisposalFormFields>[0]["disposalReasons"];
    disposal: Parameters<typeof TyreDisposalFormFields>[0]["data"] & { id: number; tyre_id: number };
}) {
    const { data, setData, put, processing, errors } = useForm({
        disposal_reason: disposal.disposal_reason,
        final_km_used: disposal.final_km_used,
        final_condition: disposal.final_condition,
        estimated_scrap_value: disposal.estimated_scrap_value,
        sold_amount: disposal.sold_amount,
        notes: disposal.notes,
    });

    return (
        <AuthenticatedLayout header="Edit Disposal">
            <Head title="Edit Disposal" />
            <Card className="max-w-3xl">
                <CardHeader><CardTitle>Edit disposal draft</CardTitle></CardHeader>
                <CardContent>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            put(route("tyres.disposals.update", disposal.id));
                        }}
                        className="space-y-6"
                    >
                        <TyreDisposalFormFields
                            data={{ tyre_id: disposal.tyre_id, ...data }}
                            setData={setData}
                            errors={errors}
                            tyres={tyres}
                            disposalReasons={disposalReasons}
                            readOnlyTyre
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>Save changes</Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.disposals.show", disposal.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
