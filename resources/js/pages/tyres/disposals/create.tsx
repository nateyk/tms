import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreDisposalFormFields } from "@/components/tyres/tyre-disposal-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";

export default function DisposalsCreate({
    tyres,
    disposalReasons,
}: {
    tyres: Parameters<typeof TyreDisposalFormFields>[0]["tyres"];
    disposalReasons: Parameters<typeof TyreDisposalFormFields>[0]["disposalReasons"];
}) {
    const { data, setData, post, processing, errors } = useForm({
        tyre_id: null as number | null,
        disposal_reason: "",
        final_km_used: null as number | null,
        final_condition: "",
        estimated_scrap_value: null as number | null,
        sold_amount: null as number | null,
        notes: "",
    });

    return (
        <AuthenticatedLayout header="New Disposal">
            <Head title="New Disposal" />
            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Tyre disposal voucher</CardTitle>
                    <CardDescription>
                        Draft → Submit → Check → Approve → Complete. Tyre is marked disposed only on
                        completion.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            post(route("tyres.disposals.store"));
                        }}
                        className="space-y-6"
                    >
                        <TyreDisposalFormFields
                            data={data}
                            setData={setData}
                            errors={errors}
                            tyres={tyres}
                            disposalReasons={disposalReasons}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>Save draft</Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.disposals.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
