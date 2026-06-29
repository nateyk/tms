import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { TyreFormFields } from "@/components/tyres/tyre-form-fields";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

type FormOptions = {
    brands: { id: number; name: string }[];
    sizes: { id: number; size_label: string }[];
    sources: { value: string; label: string }[];
};

export default function TyresCreate({ brands, sizes, sources }: FormOptions) {
    const defaultSource = sources[0]?.value ?? "purchased_new_tyre";

    const { data, setData, post, processing, errors } = useForm({
        tyre_code: "",
        serial_number: "",
        brand_id: null as number | null,
        size_id: null as number | null,
        pattern: "",
        supplier: "",
        source: defaultSource,
        purchase_date: new Date().toISOString().slice(0, 10),
        purchase_price: 0,
        invoice_number: "",
        initial_tread_depth: null as number | null,
        current_tread_depth: null as number | null,
        notes: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("tyres.store"));
    };

    return (
        <AuthenticatedLayout header="Register Tyre">
            <Head title="Register Tyre" />

            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Register tyre</CardTitle>
                    <CardDescription>
                        New tyres are saved as pending approval until an approver confirms
                        registration and generates a QR code.
                    </CardDescription>
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
                                Register tyre
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route("tyres.index")}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
