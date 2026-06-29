import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreFormFields } from "@/components/tyres/tyre-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type TyrePayload = {
    id: number;
    tyre_code: string;
    serial_number: string;
    brand_id: number | null;
    size_id: number | null;
    pattern: string;
    supplier: string;
    source: string;
    purchase_date: string;
    purchase_price: number;
    invoice_number: string;
    initial_tread_depth: number | null;
    current_tread_depth: number | null;
    notes: string;
};

type FormOptions = {
    brands: { id: number; name: string }[];
    sizes: { id: number; size_label: string }[];
    sources: { value: string; label: string }[];
    tyre: TyrePayload;
};

export default function TyresEdit({ brands, sizes, sources, tyre }: FormOptions) {
    const { data, setData, put, processing, errors } = useForm({
        tyre_code: tyre.tyre_code,
        serial_number: tyre.serial_number,
        brand_id: tyre.brand_id,
        size_id: tyre.size_id,
        pattern: tyre.pattern,
        supplier: tyre.supplier,
        source: tyre.source,
        purchase_date: tyre.purchase_date,
        purchase_price: tyre.purchase_price,
        invoice_number: tyre.invoice_number,
        initial_tread_depth: tyre.initial_tread_depth,
        current_tread_depth: tyre.current_tread_depth,
        notes: tyre.notes,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route("tyres.update", tyre.id));
    };

    return (
        <AuthenticatedLayout header={`Edit ${tyre.tyre_code}`}>
            <Head title={`Edit ${tyre.tyre_code}`} />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Edit tyre</CardTitle>
                    <CardDescription>Update registration and purchase details.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <TyreFormFields
                            errors={errors}
                            data={data}
                            setData={setData}
                            brands={brands}
                            sizes={sizes}
                            sources={sources}
                        />
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save changes
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.show", tyre.id)}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
